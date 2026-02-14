<?php

namespace App\Jobs;

use App\Services\Crawlers\TwitterCrawlerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CrawlTwitterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $keyword;
    public $limit;

    public function __construct($keyword, $limit = 100)
    {
        $this->keyword = $keyword;
        $this->limit = $limit;
    }

    public function handle(TwitterCrawlerService $crawler)
    {
        try {
            Log::info("Starting crawl for: {$this->keyword}");
            
            // 1. Crawl Posts
            $posts = $crawler->searchPosts($this->keyword, $this->limit);
            
            // 2. Crawl Comments/Replies for each post
            foreach ($posts as $postData) {
                // Dispatch job baru untuk komen supaya tidak blocking
                CrawlCommentsJob::dispatch($postData['id_str'], $postData['url']);
            }
            
            Log::info("Crawl finished for: {$this->keyword}");
        } catch (\Exception $e) {
            Log::error("Crawl Failed: " . $e->getMessage());
            // Retry logic otomatis bawaan Laravel Queue
            $this->release(60); 
        }
    }
}
