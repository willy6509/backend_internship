<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\CrawledDataLedger;
use App\Jobs\CrawlXReplies;

class CrawlXPosts extends Command
{
    protected $signature = 'crawl:x {--limit=10}';
    protected $description = 'Melakukan REAL crawling post utama dari X menggunakan API GraphQL';

    public function handle()
    {
        $limit = $this->option('limit');
        $this->info("Memulai REAL crawling postingan X... Limit: {$limit}");

        $cookies = [
            'auth_token' => env('X_AUTH_TOKEN'),
            'ct0' => env('X_CT0')
        ];

        $headers = [
            'authorization' => 'Bearer AAAAAAAAAAAAAAAAAAAAANRILgAAAAAAnNwIzU...', 
            'x-csrf-token' => env('X_CT0'),
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'accept' => '*/*'
        ];

        $variables = [
            'rawQuery' => 'polda jateng',
            'count' => (int) $limit,
            'querySource' => 'typed_query',
            'product' => 'Latest'
        ];

        $url = 'https://twitter.com/i/api/graphql/SearchTimeline?variables=' . urlencode(json_encode($variables));

        try {
            $response = Http::withCookies($cookies, '.twitter.com')
                ->withHeaders($headers)
                ->get($url);

            if ($response->successful()) {
                $json = $response->json();
                
                // Panggil fungsi pembedah JSON X
                $parsedTweets = $this->parseXTweets($json);
                
                $this->info("Berhasil mengekstrak " . count($parsedTweets) . " postingan asli dari X.");

                foreach ($parsedTweets as $tweet) {
                    // Simpan ke Database (Observer Blockchain akan otomatis bekerja di sini)
                    $record = CrawledDataLedger::firstOrCreate(
                        ['url' => $tweet['url']],
                        [
                            'type' => 'post',
                            'source' => 'X',
                            'username' => $tweet['username'],
                            'posted_at' => $tweet['posted_at'],
                            'content' => $tweet['content'],
                            'raw_payload' => $tweet['raw'] // Bukti forensik asli
                        ]
                    );

                    // Cek apakah ini data baru atau data lama yang sudah pernah di-crawl
                    if ($record->wasRecentlyCreated) {
                        $this->line("✅ Disimpan: " . $tweet['url']);
                        
                        // Lempar tugas mencari komentar (replies) ke antrean/Queue
                        // Diberi jeda acak 2-5 detik per request agar akun tidak dibanned X
                        CrawlXReplies::dispatch($tweet['url'], $cookies)
                                     ->delay(now()->addSeconds(rand(2, 5)));
                    } else {
                        $this->line("⏩ Skip (Sudah Ada): " . $tweet['url']);
                    }
                }

                $this->info("Proses Crawling Selesai.");
            } else {
                $this->error("Gagal! HTTP Status: " . $response->status());
            }
        } catch (\Exception $e) {
            $this->error("Terjadi kesalahan sistem: " . $e->getMessage());
        }
    }

    /**
     * Fungsi khusus untuk membedah anatomi JSON GraphQL X yang rumit
     */
    private function parseXTweets($json)
    {
        $tweets = [];
        
        // Ambil array instructions dari dalam JSON
        $instructions = data_get($json, 'data.search_by_raw_query.search_timeline.timeline.instructions', []);
        
        $entries = [];
        foreach ($instructions as $instruction) {
            // Kita hanya mencari instruksi yang berisi daftar Tweet
            if (data_get($instruction, 'type') === 'TimelineAddEntries') {
                $entries = data_get($instruction, 'entries', []);
                break;
            }
        }

        foreach ($entries as $entry) {
            // Pastikan ini adalah tweet, bukan iklan (promoted) atau user profile
            if (strpos(data_get($entry, 'entryId'), 'tweet-') === 0) {
                
                $result = data_get($entry, 'content.itemContent.tweet_results.result');
                
                // Tangani struktur jika cuitan di-retweet atau memiliki visibilitas khusus
                if (data_get($result, '__typename') === 'TweetWithVisibilityResults') {
                    $result = data_get($result, 'tweet');
                }

                $legacy = data_get($result, 'legacy'); // Berisi teks dan tanggal
                $userLegacy = data_get($result, 'core.user_results.result.legacy'); // Berisi username

                if ($legacy && $userLegacy) {
                    $tweets[] = [
                        'url' => 'https://x.com/' . $userLegacy['screen_name'] . '/status/' . $legacy['id_str'],
                        'username' => $userLegacy['screen_name'],
                        'posted_at' => date('Y-m-d H:i:s', strtotime($legacy['created_at'])),
                        'content' => $legacy['full_text'],
                        'raw' => $entry
                    ];
                }
            }
        }

        return $tweets;
    }
}