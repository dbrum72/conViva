<?php

namespace Tests\Feature;

use App\Modules\History\GenerateHistoryExport;
use App\Modules\History\HistoryExport;
use App\Modules\History\HistoryExportWriter;
use App\Modules\History\PurgeHistoryExports;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Support\CareScenario;
use Tests\TestCase;

class HistoryTest extends TestCase
{
    use DatabaseTransactions;

    public static bool $rejectTemporaryFile = false;

    protected function setUp(): void
    {
        parent::setUp();
        require_once __DIR__.'/../Support/history-temporary-file-failure.php';
    }

    private function scenario(): array
    {
        $this->travelTo(now()->setDate(2026, 11, 18)->setTime(15, 0));
        $this->seed();
        Storage::fake('local');
        Queue::fake();
        $scenario = CareScenario::create('child');
        $scenario['group']->update(['timezone' => 'America/Sao_Paulo']);
        $this->actingAs($scenario['owner'], 'api')->withHeader('X-Tenant', $scenario['group']->slug);

        return $scenario;
    }

    private function entry(array $scenario, string $kind, string $title, array $extra = []): int
    {
        return DB::table('care_entries')->insertGetId([
            'organization_id' => $scenario['group']->id, 'care_recipient_id' => $scenario['recipient']->id,
            'created_by' => $scenario['owner']->id, 'kind' => $kind, 'title' => $title,
            'created_at' => now(), 'updated_at' => now(), ...$extra,
        ]);
    }

    private function proposal(array $scenario, int $entry, string $title, array $extra = []): int
    {
        return DB::table('care_proposals')->insertGetId([
            'care_entry_id' => $entry, 'created_by' => $scenario['owner']->id,
            'version' => 1, 'status' => 'accepted', 'payload' => json_encode(['data' => ['title' => $title]]),
            'created_at' => now(), 'updated_at' => now(), ...$extra,
        ]);
    }

    private function generate(int $id): void
    {
        app()->call([new GenerateHistoryExport($id), 'handle']);
    }

    private function legacyExport(array $scenario, int $userId, string $area, string $format): int
    {
        return HistoryExport::create([
            'organization_id' => $scenario['group']->id,
            'recipient_id' => $scenario['recipient']->id,
            'user_id' => $userId,
            'areas' => [$area],
            'filters' => ['area' => $area, 'from' => '2026-11-18', 'to' => '2026-11-18', 'timezone' => 'America/Sao_Paulo'],
            'format' => $format,
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ])->id;
    }

    public function test_history_preserves_versions_decisions_and_corrections_with_area_and_group_isolation(): void
    {
        $scenario = $this->scenario();
        $observer = CareScenario::member($scenario['group'], 'observador');
        $access = CareScenario::grant($scenario['recipient'], $observer, ['routine'], false);
        $entry = $this->entry($scenario, 'task', 'Título atual');
        $proposal = $this->proposal($scenario, $entry, 'Título da versão');
        $this->proposal($scenario, $entry, 'Versão retirada', ['version' => 2, 'status' => 'withdrawn']);
        DB::table('care_decisions')->insert([
            ['care_proposal_id' => $proposal, 'user_id' => $observer->id, 'status' => 'rejected', 'decided_at' => now(), 'reason' => 'Motivo'],
            ['care_proposal_id' => $proposal, 'user_id' => $scenario['owner']->id, 'status' => 'pending', 'decided_at' => null, 'reason' => null],
        ]);
        $health = $this->entry($scenario, 'medication', 'Informação de saúde');
        $this->proposal($scenario, $health, 'Informação de saúde');
        $execution = $this->entry($scenario, 'medication', 'Execução', [
            'related_entry_id' => $health, 'due_at' => now()->subDays(2), 'status' => 'completed',
            'details' => json_encode(['outcome' => 'administered']),
        ]);
        $this->entry($scenario, 'medication', 'Correção', [
            'related_entry_id' => $execution, 'due_at' => now()->subDays(2), 'status' => 'completed',
            'description' => 'Observação', 'details' => json_encode([
                'corrects_execution_id' => $execution, 'correction_reason' => 'Dado corrigido', 'outcome' => 'not_administered',
            ]),
        ]);
        $profile = DB::table('care_profile_proposals')->insertGetId([
            'care_recipient_id' => $scenario['recipient']->id, 'created_by' => $scenario['owner']->id,
            'version' => 1, 'operation' => 'save', 'status' => 'withdrawn', 'payload' => '{"private":"ficha restrita"}',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('care_profile_decisions')->insert([
            'care_profile_proposal_id' => $profile, 'user_id' => $observer->id, 'status' => 'accepted', 'decided_at' => now(),
        ]);
        $other = CareScenario::create('pet');
        $this->proposal($other, $this->entry($other, 'task', 'Outro grupo'), 'Outro grupo');
        $this->proposal($scenario, $entry, 'Fora no fuso do grupo', [
            'version' => 3, 'created_at' => now()->setTime(2, 59),
        ]);

        $this->actingAs($observer, 'api')->getJson('/api/history?from=2026-11-18&to=2026-11-18')->assertOk()
            ->assertJsonPath('areas', ['routine'])->assertJsonCount(7, 'events')
            ->assertJsonFragment(['title' => 'Título da versão'])->assertJsonFragment(['reason' => 'Motivo'])
            ->assertJsonFragment(['type' => 'profile_withdrawal'])->assertJsonFragment(['type' => 'withdrawal'])
            ->assertDontSee('ficha restrita')->assertDontSee('Outro grupo')->assertDontSee('Informação de saúde')
            ->assertDontSee('Fora no fuso do grupo')->assertDontSee('Título atual');
        $this->getJson('/api/history?area=health')->assertForbidden();
        $this->getJson('/api/history?from=2024-01-01&to=2026-11-18')->assertUnprocessable()->assertJsonValidationErrors('to');
        $this->getJson('/api/history?to=2026-11-01')->assertUnprocessable();
        $this->actingAs($scenario['owner'], 'api')->getJson('/api/history?area=health&from=2026-11-18&to=2026-11-18')
            ->assertOk()->assertJsonCount(3, 'events')->assertJsonFragment([
                'type' => 'correction', 'related_id' => $execution, 'reason' => 'Dado corrigido', 'notes' => 'Observação',
            ]);
        $access->update(['areas' => ['documents']]);
        $this->actingAs($observer, 'api')->getJson('/api/history')->assertForbidden();
    }

    public function test_csv_is_private_neutralizes_formulas_and_revalidates_grants_on_download(): void
    {
        $scenario = $this->scenario();
        $viewer = CareScenario::member($scenario['group'], 'observador');
        $access = CareScenario::grant($scenario['recipient'], $viewer, ['routine'], false);
        $this->proposal($scenario, $this->entry($scenario, 'task', 'Rotina'), ' =1+1');
        $this->proposal($scenario, $this->entry($scenario, 'vaccine', 'Saúde'), 'Informação privada');
        $this->actingAs($viewer, 'api');
        $this->postJson('/api/history/exports', ['format' => 'csv'])->assertUnprocessable()->assertJsonValidationErrors('format');
        $id = $this->legacyExport($scenario, $viewer->id, 'routine', 'csv');
        $url = '/api/history/exports/'.$id;
        $this->getJson($url.'/download')->assertConflict();
        $this->generate($id);
        $this->generate($id);
        $item = HistoryExport::findOrFail($id);
        $this->assertSame($viewer->id, $item->user_id);
        $this->assertSame(['routine'], $item->areas);
        Storage::disk('local')->assertExists($item->path());
        $this->assertNotSame(config('filesystems.disks.local.root'), config('filesystems.disks.public.root'));
        $this->getJson('/api/history')->assertOk()->assertJsonPath('export.id', $id)->assertJsonMissingPath('export.path');
        $this->getJson($url)->assertOk()->assertJsonPath('status', 'ready');
        $download = $this->get($url.'/download')->assertOk()->assertDownload('historico.csv')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringContainsString("' =1+1", $download->streamedContent());
        $this->assertStringNotContainsString('Informação privada', Storage::disk('local')->get($item->path()));

        $this->actingAs($scenario['owner'], 'api')->getJson($url)->assertNotFound();
        $other = CareScenario::create('pet');
        CareScenario::member($other['group'], 'observador', $viewer);
        $this->actingAs($viewer, 'api')->withHeader('X-Tenant', $other['group']->slug)->getJson($url.'/download')->assertNotFound();
        $this->withHeader('X-Tenant', $scenario['group']->slug);
        $access->update(['expires_at' => now()]);
        $this->getJson($url.'/download')->assertForbidden();
        $access->update(['expires_at' => null]);
        $this->travel(24)->hours();
        $this->getJson($url.'/download')->assertGone();
        app(PurgeHistoryExports::class)();
        Storage::disk('local')->assertMissing($item->path());
        $this->assertSame('expired', $item->refresh()->status);
        $this->travel(90)->days();
        app(PurgeHistoryExports::class)();
        $this->assertNull($item->fresh());
    }

    public function test_pdf_is_generated_and_download_requires_current_area_access(): void
    {
        $scenario = $this->scenario();
        $viewer = CareScenario::member($scenario['group'], 'observador');
        $access = CareScenario::grant($scenario['recipient'], $viewer, ['routine'], false);
        $this->proposal($scenario, $this->entry($scenario, 'task', 'Rotina'), 'Anotação de rotina');
        $id = $this->actingAs($viewer, 'api')->postJson('/api/history/exports', [
            'format' => 'pdf', 'area' => 'routine', 'from' => '2026-11-18', 'to' => '2026-11-18',
        ])->assertAccepted()->assertJsonPath('format', 'pdf')->assertJsonPath('status', 'pending')
            ->assertJsonPath('requested_at', now()->toIso8601String())->json('id');
        Queue::assertPushed(GenerateHistoryExport::class, fn ($job) => $job->exportId === $id);
        $this->generate($id);
        $url = '/api/history/exports/'.$id.'/download';
        $download = $this->get($url)->assertOk()->assertDownload("historico-conviva-{$id}.pdf")
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringStartsWith('%PDF-', $download->streamedContent());
        $access->delete();
        $this->getJson($url)->assertForbidden();
    }

    public function test_print_contains_period_and_authorship_and_escapes_markup(): void
    {
        $scenario = $this->scenario();
        $this->proposal($scenario, $this->entry($scenario, 'expense', 'Despesa'), '<script>alert(1)</script>');
        $id = $this->legacyExport($scenario, $scenario['owner']->id, 'expenses', 'html');
        $this->generate($id);
        $url = '/api/history/exports/'.$id.'/download';
        $html = $this->get($url)->assertOk()->assertDownload('historico.html')->streamedContent();
        $this->assertStringContainsString('18/11/2026 a 18/11/2026', $html);
        $this->assertStringContainsString(e($scenario['owner']->name), $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        Storage::disk('local')->delete(HistoryExport::findOrFail($id)->path());
        $this->getJson($url)->assertGone();
    }

    public function test_worker_revalidates_access_and_failure_does_not_expose_export_content(): void
    {
        $scenario = $this->scenario();
        $viewer = CareScenario::member($scenario['group'], 'observador');
        $access = CareScenario::grant($scenario['recipient'], $viewer, ['routine'], false);
        $this->actingAs($viewer, 'api');
        $request = fn () => $this->postJson('/api/history/exports', ['format' => 'pdf'])->assertAccepted()->json('id');
        $denied = $request();
        $access->delete();
        $this->generate($denied);
        $this->assertSame('denied', HistoryExport::findOrFail($denied)->status);
        CareScenario::grant($scenario['recipient'], $viewer, ['routine'], false);
        $failed = $request();
        $this->mock(HistoryExportWriter::class)->shouldReceive('write')->once()->andThrow(new RuntimeException('Conteúdo privado'));
        try {
            $this->generate($failed);
            $this->fail('Esperava falha na geração.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Falha no processamento da exportação de histórico.', $exception->getMessage());
            $this->assertNull($exception->getPrevious());
        }
        (new GenerateHistoryExport($failed))->failed();
        $this->getJson('/api/history/exports/'.$failed)->assertOk()->assertJsonPath('status', 'failed');
        Storage::disk('local')->assertDirectoryEmpty('history-exports');
        $groupUnavailable = $request();
        $scenario['group']->update(['status' => 'inactive']);
        $this->generate($groupUnavailable);
        $this->assertSame('denied', HistoryExport::findOrFail($groupUnavailable)->status);
    }

    public function test_revocation_during_generation_prevents_publication(): void
    {
        $scenario = $this->scenario();
        $viewer = CareScenario::member($scenario['group'], 'observador');
        $access = CareScenario::grant($scenario['recipient'], $viewer, ['routine'], false);
        $id = $this->actingAs($viewer, 'api')->postJson('/api/history/exports', ['format' => 'pdf'])
            ->assertAccepted()->json('id');
        $this->mock(HistoryExportWriter::class)->shouldReceive('write')->once()->andReturnUsing(
            function () use ($access) {
                $access->delete();
            },
        );
        $this->generate($id);
        $item = HistoryExport::findOrFail($id);
        $this->assertSame('denied', $item->status);
        Storage::disk('local')->assertMissing($item->path());
        $this->getJson('/api/history/exports/'.$id.'/download')->assertForbidden();
    }

    public function test_timeline_paginates_without_repeating_events_with_the_same_timestamp(): void
    {
        $scenario = $this->scenario();
        $entry = $this->entry($scenario, 'task', 'Registros');
        foreach (range(1, 26) as $version) {
            $this->proposal($scenario, $entry, 'Versão '.$version, ['version' => $version]);
        }
        $this->getJson('/api/history')->assertOk()->assertJsonCount(25, 'events')
            ->assertJsonPath('events.0.title', 'Versão 26')
            ->assertJsonPath('events.24.title', 'Versão 2')->assertJsonPath('has_more', true);
        $this->getJson('/api/history?page=2')->assertOk()->assertJsonCount(1, 'events')
            ->assertJsonPath('events.0.title', 'Versão 1')->assertJsonPath('has_more', false);
    }

    public function test_worker_does_not_publish_when_the_request_expires_during_generation(): void
    {
        $this->scenario();
        $id = $this->postJson('/api/history/exports', ['format' => 'pdf'])->assertAccepted()->json('id');
        $this->mock(HistoryExportWriter::class)->shouldReceive('write')->once()->andReturnUsing(
            function () {
                $this->travel(24)->hours();
            },
        );
        $this->generate($id);
        $item = HistoryExport::findOrFail($id);
        $this->getJson('/api/history/exports/'.$id)->assertOk()->assertJsonPath('status', 'expired');
        Storage::disk('local')->assertMissing($item->path());
    }

    public function test_worker_reports_storage_failure_and_can_retry(): void
    {
        $this->scenario();
        $id = $this->postJson('/api/history/exports', ['format' => 'pdf'])->assertAccepted()->json('id');
        $disk = Storage::disk('local');
        Storage::shouldReceive('disk')->once()->with('local')->andReturn(
            \Mockery::mock(FilesystemAdapter::class)
                ->shouldReceive('put')->once()->andReturnFalse()->getMock(),
        );
        try {
            $this->generate($id);
            $this->fail('Esperava falha no armazenamento.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Falha no processamento da exportação de histórico.', $exception->getMessage());
        }
        $item = HistoryExport::findOrFail($id);
        $this->assertSame('pending', $item->status);
        Storage::shouldReceive('disk')->once()->with('local')->andReturn($disk);
        $this->generate($id);
        $this->assertSame('ready', $item->refresh()->status);
        $disk->assertExists($item->path());
    }

    public function test_worker_reports_temporary_file_failure_without_publishing(): void
    {
        $this->scenario();
        $id = $this->postJson('/api/history/exports', ['format' => 'pdf'])->assertAccepted()->json('id');
        self::$rejectTemporaryFile = true;
        try {
            $this->expectExceptionMessage('Falha no processamento da exportação de histórico.');
            $this->generate($id);
        } finally {
            self::$rejectTemporaryFile = false;
        }
    }

    public function test_operational_logs_omit_exception_payloads_and_request_secrets(): void
    {
        Route::get('api/history/log-test', function () {
            throw new RuntimeException('conteúdo de saúde e arquivo privado');
        });
        Log::shouldReceive('error')->once()->with('Falha operacional.', \Mockery::on(
            fn ($context) => array_keys($context) === ['type', 'file', 'line']
                && $context['type'] === RuntimeException::class,
        ));
        $this->withToken('token-sintetico')->getJson('/api/history/log-test')->assertServerError();
    }
}
