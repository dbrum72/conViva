<?php

namespace Tests\Feature;

use App\Models\ExpensePaymentReceipt;
use App\Models\ExpenseShare;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CareScenario;
use Tests\TestCase;

class ExpensePaymentReceiptTest extends TestCase
{
    use DatabaseTransactions;

    private function expense(): array
    {
        $this->seed();
        Storage::fake('local');
        $scenario = CareScenario::create('child');
        $path = '/api/recipients/'.$scenario['recipient']->id.'/entries';
        $entry = $this->actingAs($scenario['owner'], 'api')->withHeader('X-Tenant', $scenario['group']->slug)
            ->postJson($path, ['kind' => 'expense', 'title' => 'Despesa de teste', 'amount_cents' => 12345])->assertCreated()->json();
        $share = $entry['shares'][0];
        $url = $path.'/'.$entry['id'].'/shares/'.$share['id'];

        return [...$scenario, 'path' => $path, 'entry' => $entry, 'share' => $share, 'url' => $url];
    }

    private function receipt(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('recibo.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF");
    }

    public function test_payment_saves_private_receipt_and_download_reauthorizes_both_areas(): void
    {
        ['group' => $group, 'recipient' => $recipient, 'url' => $url, 'share' => $share, 'path' => $path] = $this->expense();
        $this->postJson($url.'/pay', ['receipt' => $this->receipt()])->assertOk();
        $receipt = ExpensePaymentReceipt::where('expense_share_id', $share['id'])->sole();
        Storage::disk('local')->assertExists($receipt->path);
        $this->assertNotNull(ExpenseShare::findOrFail($share['id'])->paid_at);
        $this->getJson($path)->assertOk()->assertJsonPath('0.shares.0.receipt.filename', 'recibo.pdf')->assertJsonMissingPath('0.shares.0.receipt.path');
        $this->get($url.'/receipt')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Cache-Control', 'no-store, private');
        $viewer = CareScenario::member($group, 'observador');
        $access = CareScenario::grant($recipient, $viewer, ['finance'], false);
        $this->actingAs($viewer, 'api')->getJson($path)->assertOk()->assertJsonPath('0.shares.0.receipt', null);
        $this->get($url.'/receipt')->assertForbidden();
        $access->update(['areas' => ['documents']]);
        $this->get($url.'/receipt')->assertForbidden();
        $access->update(['areas' => ['finance', 'documents']]);
        $this->get($url.'/receipt')->assertOk();
        $access->update(['expires_at' => now()]);
        $this->get($url.'/receipt')->assertForbidden();
    }

    public function test_optional_receipt_can_be_added_later_but_never_replaces_original(): void
    {
        ['url' => $url, 'share' => $share] = $this->expense();
        $paid = $this->postJson($url.'/pay')->assertOk()->json('paid_at');
        $this->postJson($url.'/pay', ['receipt' => $this->receipt()])->assertOk()->assertJsonPath('paid_at', $paid);
        $receipt = ExpensePaymentReceipt::where('expense_share_id', $share['id'])->sole();
        $this->postJson($url.'/pay', ['receipt' => $this->receipt()])->assertConflict();
        $this->postJson($url.'/pay')->assertOk()->assertJsonPath('paid_at', $paid);
        $this->assertSame(1, ExpensePaymentReceipt::where('expense_share_id', $share['id'])->count());
        Storage::disk('local')->assertExists($receipt->path);
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_other_participants_and_other_groups_cannot_attach_or_download(): void
    {
        ['group' => $group, 'recipient' => $recipient, 'owner' => $owner, 'url' => $url, 'entry' => $entry, 'share' => $share] = $this->expense();
        $peer = CareScenario::member($group, 'responsavel');
        CareScenario::grant($recipient, $peer, ['finance', 'documents']);
        $this->actingAs($peer, 'api')->postJson($url.'/pay', ['receipt' => $this->receipt()])->assertForbidden();
        $this->actingAs($owner, 'api')->postJson($url.'/pay', ['receipt' => $this->receipt()])->assertOk();
        ['group' => $other, 'owner' => $stranger, 'recipient' => $otherRecipient] = CareScenario::create('pet');
        $this->actingAs($stranger, 'api')->withHeader('X-Tenant', $other->slug)->get($url.'/receipt')->assertNotFound();
        $this->get('/api/recipients/'.$otherRecipient->id.'/entries/'.$entry['id'].'/shares/'.$share['id'].'/receipt')->assertNotFound();
    }

    public function test_invalid_file_and_pending_proposal_leave_payment_unrecorded(): void
    {
        ['url' => $url, 'share' => $share, 'path' => $path, 'entry' => $entry, 'group' => $group, 'recipient' => $recipient] = $this->expense();
        foreach ([UploadedFile::fake()->create('fake.pdf', 1, 'text/plain'), UploadedFile::fake()->create('large.pdf', 20481, 'application/pdf')] as $file) {
            $this->postJson($url.'/pay', ['receipt' => $file])->assertUnprocessable()->assertJsonValidationErrors('receipt');
        }
        $peer = CareScenario::member($group, 'responsavel');
        CareScenario::grant($recipient, $peer, ['finance']);
        $this->putJson($path.'/'.$entry['id'], ['kind' => 'expense', 'title' => 'Alteração pendente', 'amount_cents' => 12345, 'affected_user_ids' => [$peer->id]])->assertOk();
        $this->postJson($url.'/pay', ['receipt' => $this->receipt()])->assertConflict();
        $this->assertDatabaseHas('expense_shares', ['id' => $share['id'], 'paid_at' => null]);
        $this->assertSame(0, ExpensePaymentReceipt::where('expense_share_id', $share['id'])->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_persistence_failure_removes_uploaded_file_and_rolls_back_payment(): void
    {
        ['url' => $url, 'share' => $share] = $this->expense();
        ExpensePaymentReceipt::creating(function () {
            throw new \RuntimeException('Simulated persistence failure');
        });
        try {
            $this->withoutExceptionHandling()->postJson($url.'/pay', ['receipt' => $this->receipt()]);
            $this->fail('Expected persistence failure.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Simulated persistence failure', $error->getMessage());
        } finally {
            ExpensePaymentReceipt::flushEventListeners();
        }
        $this->assertDatabaseHas('expense_shares', ['id' => $share['id'], 'paid_at' => null]);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_finance_only_participant_can_pay_but_cannot_upload(): void
    {
        ['group' => $group, 'recipient' => $recipient, 'path' => $path] = $this->expense();
        $carer = CareScenario::member($group, 'cuidador');
        CareScenario::grant($recipient, $carer, ['finance']);
        $entry = $this->actingAs($carer, 'api')->postJson($path, ['kind' => 'expense', 'title' => 'Parcela própria', 'amount_cents' => 1000])->assertCreated()->json();
        $url = $path.'/'.$entry['id'].'/shares/'.$entry['shares'][0]['id'].'/pay';
        $this->postJson($url, ['receipt' => $this->receipt()])->assertForbidden();
        $this->assertDatabaseHas('expense_shares', ['id' => $entry['shares'][0]['id'], 'paid_at' => null]);
        $this->postJson($url)->assertOk();
    }

    public function test_storage_failure_does_not_record_payment(): void
    {
        ['url' => $url, 'share' => $share] = $this->expense();
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        try {
            $this->withoutExceptionHandling()->postJson($url.'/pay', ['receipt' => $this->receipt()]);
            $this->fail('Expected storage failure.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('não foi registrado', $error->getMessage());
        }
        $this->assertDatabaseHas('expense_shares', ['id' => $share['id'], 'paid_at' => null]);
        $this->assertSame(0, ExpensePaymentReceipt::where('expense_share_id', $share['id'])->count());
    }
}
