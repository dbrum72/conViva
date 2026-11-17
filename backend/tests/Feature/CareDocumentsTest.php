<?php

namespace Tests\Feature;

use App\Models\CareDocument;
use Carbon\CarbonImmutable;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\Support\CareScenario;
use Tests\TestCase;

class CareDocumentsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_document_metadata_and_link_keep_both_areas_authorized(): void
    {
        Storage::fake('local');
        $this->travelTo(CarbonImmutable::parse('2026-11-17T02:00:00Z'));
        $this->seed();
        $s = CareScenario::create('child');
        $this->actingAs($s['owner'], 'api')->withHeader('X-Tenant', $s['group']->slug);
        $base = '/api/recipients/'.$s['recipient']->id;
        $health = $this->postJson($base.'/entries', [
            'kind' => 'medication', 'title' => 'Instrução de saúde',
        ])->assertCreated()->json('id');
        $id = $this->post($base.'/documents', [
            'file' => UploadedFile::fake()->create('orientacao.txt', 1, 'text/plain'),
        ], ['Accept' => 'application/json'])->assertCreated()->assertJsonMissingPath('path')->json('id');
        $url = $base.'/documents/'.$id;
        $this->patchJson($url, ['category' => 'Instruções', 'valid_until' => '2027-01-01', 'care_entry_id' => $health])
            ->assertOk()->assertJsonPath('care_entry_id', $health);
        $this->patchJson($url, ['care_entry_id' => null])->assertUnprocessable();
        $this->getJson($base.'/documents?from=2026-11-16&to=2026-11-16')
            ->assertOk()->assertJsonPath('0.entry.title', 'Instrução de saúde')->assertJsonPath('0.valid_until', '2027-01-01');
        $this->getJson($base.'/documents?from=2026-11-17')->assertOk()->assertJsonCount(0);
        $this->getJson($base.'/documents?category=Outro')->assertOk()->assertJsonCount(0);
        $this->get($url.'/download')->assertOk()->assertHeader('Cache-Control', 'no-store, private');

        $observer = CareScenario::member($s['group'], 'observador');
        $grant = CareScenario::grant($s['recipient'], $observer, ['documents'], false);
        $this->actingAs($observer, 'api')->getJson($base.'/documents')->assertOk()->assertJsonCount(0);
        $this->getJson($url.'/download')->assertNotFound();
        $grant->update(['areas' => ['documents', 'health']]);
        $this->getJson($base.'/documents')->assertOk()->assertJsonCount(1);
        $this->patchJson($url, ['category' => 'Outro'])->assertForbidden();
    }

    public function test_storage_failure_preserves_metadata_and_allows_deletion_retry(): void
    {
        $disk = Storage::fake('local');
        $this->seed();
        $s = CareScenario::create('pet');
        $this->actingAs($s['owner'], 'api')->withHeader('X-Tenant', $s['group']->slug);
        $base = '/api/recipients/'.$s['recipient']->id.'/documents';
        $id = $this->post($base, [
            'file' => UploadedFile::fake()->create('arquivo.txt', 1, 'text/plain'),
        ], ['Accept' => 'application/json'])->assertCreated()->json('id');
        $path = CareDocument::withoutGlobalScopes()->findOrFail($id)->path;
        $failingDisk = Mockery::mock(FilesystemAdapter::class);
        $failingDisk->shouldReceive('exists')->with($path)->andReturn(true);
        $failingDisk->shouldReceive('delete')->once()->with($path)->andReturn(false);
        Storage::set('local', $failingDisk);
        $this->deleteJson($base.'/'.$id)->assertStatus(503);
        $this->assertDatabaseHas('care_documents', ['id' => $id]);

        Storage::set('local', $disk);
        $disk->assertExists($path);
        $this->deleteJson($base.'/'.$id)->assertNoContent();
        $disk->assertMissing($path);
        $this->assertDatabaseMissing('care_documents', ['id' => $id]);
    }
}
