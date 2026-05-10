<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\CrawledData;
use Carbon\Carbon;

class RunDataRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_delete_archives_old_data_and_force_delete_cleans_trashed()
    {
        // Create an old record (created more than 6 months ago)
        $old = new CrawledData([
            'type' => 'post',
            'source' => 'X',
            'username' => 'tester',
            'posted_at' => Carbon::now()->subMonths(7),
            'content' => 'old content',
            'url' => 'https://x.example/old',
            'raw_payload' => [],
        ]);
        $old->created_at = Carbon::now()->subMonths(7);
        $old->save();

        // Create a trashed record older than 3 months
        $trash = new CrawledData([
            'type' => 'post',
            'source' => 'X',
            'username' => 'tester2',
            'posted_at' => Carbon::now()->subMonths(5),
            'content' => 'to be destroyed',
            'url' => 'https://x.example/trash',
            'raw_payload' => [],
        ]);
        $trash->save();
        $trash->delete();
        // backdate deleted_at to older than 3 months
        \DB::table('crawled_data')->where('id', $trash->id)->update(['deleted_at' => Carbon::now()->subMonths(4)]);

        // Run the retention command
        $this->artisan('db:retention')->assertExitCode(0);

        // Old record should be soft deleted
        $this->assertSoftDeleted('crawled_data', ['id' => $old->id]);

        // Trashed old record should be force deleted
        $this->assertDatabaseMissing('crawled_data', ['id' => $trash->id]);
    }
}
