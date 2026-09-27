<?php

namespace Tests\Feature;

use App\Models\CareProfileProposal;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CareScenario;
use Tests\TestCase;

class DecisionConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public static function proposalTypes(): array
    {
        return [['profile'], ['entry']];
    }

    #[DataProvider('proposalTypes')]
    public function test_simultaneous_acceptances_apply_once_and_keep_one_vote(string $type): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Este ensaio exige pcntl e MySQL.');
        }
        $this->seed();
        ['group' => $group, 'owner' => $owner, 'recipient' => $recipient] = CareScenario::create('child');
        $peer = CareScenario::member($group, 'responsavel');
        CareScenario::grant($recipient, $peer, ['routine']);
        $this->actingAs($owner, 'api')->withHeader('X-Tenant', $group->slug);
        if ($type === 'profile') {
            $proposal = $this->postJson('/api/recipients/'.$recipient->id.'/profile-proposals', ['operation' => 'save', 'version' => 0, 'name' => 'Aceito uma vez', 'kind' => 'child'])->assertCreated()->json('id');
            $url = '/api/recipients/'.$recipient->id.'/profile-proposals/'.$proposal.'/decision';
        } else {
            $entry = $this->postJson('/api/recipients/'.$recipient->id.'/entries', ['kind' => 'task', 'title' => 'Aceito uma vez'])->assertCreated()->json();
            $proposal = $entry['proposals'][0]['id'];
            $url = '/api/recipients/'.$recipient->id.'/entries/'.$entry['id'].'/proposals/'.$proposal.'/decision';
        }
        $files = [tempnam(sys_get_temp_dir(), 'conviva-decision-'), tempnam(sys_get_temp_dir(), 'conviva-decision-')];
        $start = microtime(true) + 0.5;
        DB::disconnect();
        $children = [];
        try {
            foreach ($files as $file) {
                $pid = pcntl_fork();
                if ($pid === -1) {
                    $this->fail('Não foi possível criar processo para o ensaio.');
                }
                if ($pid === 0) {
                    DB::purge();
                    while (microtime(true) < $start) {
                        usleep(1000);
                    }
                    try {
                        $response = $this->actingAs($peer->fresh(), 'api')->postJson($url, ['decision' => 'accepted']);
                        file_put_contents($file, (string) $response->status());
                    } catch (\Throwable $error) {
                        file_put_contents($file, 'error: '.$error->getMessage());
                    }
                    exit(0);
                }
                $children[] = $pid;
            }
            foreach ($children as $pid) {
                pcntl_waitpid($pid, $status);
                $this->assertSame(0, pcntl_wexitstatus($status));
            }
            DB::purge();
            $statuses = array_map(fn ($file) => file_get_contents($file), $files);
            sort($statuses);
            $this->assertSame(['200', '409'], $statuses);
            if ($type === 'profile') {
                $this->assertSame('Aceito uma vez', $recipient->fresh()->name);
                $this->assertSame('accepted', CareProfileProposal::findOrFail($proposal)->status);
                $this->assertDatabaseCount('care_profile_decisions', 1);
            } else {
                $this->assertDatabaseHas('care_entries', ['id' => $entry['id'], 'revision' => 1, 'status' => 'pending']);
                $this->assertDatabaseHas('care_proposals', ['id' => $proposal, 'status' => 'accepted']);
                $this->assertDatabaseCount('care_decisions', 1);
            }
        } finally {
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }
}
