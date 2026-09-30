<?php

namespace Tests\Concurrency;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CareScenario;
use Tests\TestCase;

class CareOccurrenceConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public static function actions(): array
    {
        return [['generation'], ['execution'], ['approval']];
    }

    #[DataProvider('actions')]
    public function test_concurrent_occurrence_actions_do_not_duplicate(string $action): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Exige pcntl e MySQL.');
        }
        $this->seed();
        ['group' => $group, 'owner' => $owner, 'recipient' => $recipient] = CareScenario::create('child');
        $peer = null;
        if ($action === 'approval') {
            $peer = CareScenario::member($group, 'responsavel');
            CareScenario::grant($recipient, $peer, ['routine']);
        }
        $this->actingAs($owner, 'api')->withHeader('X-Tenant', $group->slug);
        $day = now()->addDays(2)->format('Y-m-d');
        $path = '/api/recipients/'.$recipient->id.'/entries';
        $entry = $this->postJson($path, ['kind' => 'task', 'title' => 'Série concorrente', 'publish_to_agenda' => true, 'assigned_user_id' => $owner->id, 'schedule' => ['frequency' => 'daily', 'timezone' => 'UTC', 'local_start' => $day.'T10:00', 'until' => $day, 'duration_minutes' => 30]])->assertCreated()->json();
        $url = '/api/agenda?from='.$day.'&to='.$day;
        if ($action === 'execution') {
            $id = $this->getJson($url)->assertOk()->json('data.0.id');
            $url = '/api/occurrences/'.$id.'/execution';
        } elseif ($action === 'approval') {
            $url = $path.'/'.$entry['id'].'/proposals/'.$entry['proposals'][0]['id'].'/decision';
        }
        $files = [tempnam(sys_get_temp_dir(), 'conviva-occurrence-'), tempnam(sys_get_temp_dir(), 'conviva-occurrence-')];
        $start = microtime(true) + 0.3;
        DB::disconnect();
        $children = [];
        try {
            foreach ($files as $file) {
                $pid = pcntl_fork();
                if ($pid === -1) {
                    $this->fail('Falha ao iniciar processo.');
                }
                if ($pid === 0) {
                    DB::purge();
                    while (microtime(true) < $start) {
                        usleep(1000);
                    }
                    try {
                        $this->actingAs(($peer ?? $owner)->fresh(), 'api');
                        $response = match ($action) {
                            'generation' => $this->getJson($url),
                            'execution' => $this->postJson($url, ['occurred_at' => now()->subMinute()->toISOString()]),
                            'approval' => $this->postJson($url, ['decision' => 'accepted']),
                        };
                        file_put_contents($file, (string) $response->status().($response->status() >= 500 ? ': '.$response->json('message') : ''));
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
            $this->assertSame(match ($action) {
                'generation' => ['200', '200'], 'execution' => ['201', '409'], 'approval' => ['200', '409']
            }, $statuses);
            $this->assertDatabaseCount('care_schedules', 1);
            if ($action !== 'approval') {
                $this->assertDatabaseCount('care_occurrences', 1);
            }
            if ($action === 'execution') {
                $this->assertSame(1, DB::table('care_entries')->where('related_entry_id', $entry['id'])->count());
            }
        } finally {
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }
}
