<?php

namespace App\Jobs;

use App\Models\CrawledData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

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
                'user-agent' => 'Mozilla/5.0',
            ])
            ->get($endpoint, [
                // Extract ID tweet dari URL untuk dimasukkan ke query
                'variables' => json_encode(['focalTweetId' => $this->extractId($this->parentUrl)]),
            ]);

        if ($response->successful()) {
            $replies = $this->parseXReplies($response->json());

            foreach ($replies as $reply) {
                CrawledData::firstOrCreate(
                    ['url' => $reply['url']],
                    [
                        'type' => 'reply',
                        'source' => 'X',
                        'username' => $reply['username'],
                        'posted_at' => $reply['posted_at'],
                        'content' => $reply['content'],
                        'parent_url' => $this->parentUrl,
                        'raw_payload' => $reply['raw'],
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
        $replies = [];

        $instructions = data_get($json, 'data.threaded_conversation_with_injections_v2.instructions', []);
        $entries = [];

        foreach ($instructions as $instruction) {
            if (data_get($instruction, 'type') === 'TimelineAddEntries') {
                $entries = data_get($instruction, 'entries', []);
                break;
            }
        }

        foreach ($entries as $entry) {
            if (strpos(data_get($entry, 'entryId'), 'tweet-') === 0) {
                $result = data_get($entry, 'content.itemContent.tweet_results.result');

                if (data_get($result, '__typename') === 'TweetWithVisibilityResults') {
                    $result = data_get($result, 'tweet');
                }

                $legacy = data_get($result, 'legacy');
                $userLegacy = data_get($result, 'core.user_results.result.legacy');

                if ($legacy && $userLegacy) {
                    $replies[] = [
                        'url' => 'https://x.com/'.$userLegacy['screen_name'].'/status/'.$legacy['id_str'],
                        'username' => $userLegacy['screen_name'],
                        'posted_at' => \Carbon\Carbon::parse($legacy['created_at'])->format('Y-m-d H:i:s'),
                        'content' => $legacy['full_text'],
                        'raw' => $entry,
                    ];
                }
            }
        }

        return $replies;
    }
}
