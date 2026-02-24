<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use App\Models\CrawledDataLedger;

class CrawlXReplies implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $parentUrl;
    protected $cookies;

    public function __construct($parentUrl, $cookies)
    {
        $this->parentUrl = $parentUrl;
        $this->cookies = $cookies;
    }

    public function handle(): void
    {
        // Simulasi Endpoint Tweet Detail GraphQL
        $endpoint = 'https://twitter.com/i/api/graphql/TweetDetail';
        
        $response = Http::withCookies($this->cookies, '.twitter.com')
            ->withHeaders([
                'x-csrf-token' => $this->cookies['ct0'],
                'authorization' => 'Bearer AAAAAAAAAAAAAAAAAAAAANRILgAAAAAAnNwIzU...',
                'user-agent' => 'Mozilla/5.0'
            ])
            ->get($endpoint, [
                // Extract ID tweet dari URL untuk dimasukkan ke query
                'variables' => json_encode(['focalTweetId' => $this->extractId($this->parentUrl)])
            ]);

        if ($response->successful()) {
            $replies = $this->parseXReplies($response->json());

            foreach ($replies as $reply) {
                CrawledDataLedger::firstOrCreate(
                    ['url' => $reply['url']],
                    [
                        'type' => 'reply',
                        'source' => 'X',
                        'username' => $reply['username'],
                        'posted_at' => $reply['posted_at'],
                        'content' => $reply['content'],
                        'parent_url' => $this->parentUrl,
                        'raw_payload' => $reply['raw']
                    ]
                );
            }
        }
    }

    private function extractId($url)
    {
        $parts = explode('/', parse_url($url, PHP_URL_PATH));
        return end($parts);
    }

    private function parseXReplies($json)
    {
        // Logika parsing respons balasan JSON X
        return [];
    }
}