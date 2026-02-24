<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\CrawledDataLedger;
use App\Jobs\CrawlXReplies;
use Illuminate\Support\Facades\Log;

class CrawlXPosts extends Command
{
    protected $signature = 'crawl:x {--limit=100}';
    protected $description = 'Melakukan crawling post utama dari X menggunakan Cookies';

    public function handle()
    {
        $limit = $this->option('limit');
        $this->info("Memulai crawling postingan X... Limit: {$limit}");

        // Cookies Auth
        $cookies = [
            'auth_token' => env('X_AUTH_TOKEN'),
            'ct0' => env('X_CT0')
        ];

        // Simulasi Endpoint API Internal X (Sesuaikan dengan endpoint GraphQL X yang aktif)
        $endpoint = 'https://twitter.com/i/api/graphql/SearchTimeline'; 
        
        try {
            // Gunakan proxy atau penundaan jika diperlukan untuk menghindari rate limit
            $response = Http::withCookies($cookies, '.twitter.com')
                ->withHeaders([
                    'x-csrf-token' => env('X_CT0'),
                    'authorization' => 'Bearer AAAAAAAAAAAAAAAAAAAAANRILgAAAAAAnNwIzU...', // Bearer statis X frontend
                    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                ])
                ->get($endpoint, [
                    'variables' => json_encode(['rawQuery' => 'polda jateng', 'count' => $limit])
                ]);

            if ($response->successful()) {
                $tweets = $this->parseXTweets($response->json()); // Fungsi ekstraksi data

                foreach ($tweets as $tweet) {
                    // Simpan ke Ledger
                    $data = CrawledDataLedger::firstOrCreate(
                        ['url' => $tweet['url']],
                        [
                            'type' => 'post',
                            'source' => 'X',
                            'username' => $tweet['username'],
                            'posted_at' => $tweet['posted_at'],
                            'content' => $tweet['content'],
                            'raw_payload' => $tweet['raw'] // Simpan raw JSON untuk forensik
                        ]
                    );

                    $this->line("Disimpan: " . $tweet['url']);

                    // Lempar tugas mengambil balasan ke antrean (Queue) agar tidak menunggu
                    // Beri delay acak antara 5-15 detik per job untuk menghindari deteksi bot
                    CrawlXReplies::dispatch($tweet['url'], $cookies)
                                 ->delay(now()->addSeconds(rand(5, 15)));
                }

                $this->info("Crawling postingan selesai.");
            } else {
                $this->error("Gagal HTTP Status: " . $response->status());
                Log::error("X Crawl Error", ['body' => $response->body()]);
            }
        } catch (\Exception $e) {
            $this->error("Terjadi kesalahan: " . $e->getMessage());
        }
    }

    private function parseXTweets($json)
    {
        // Logika ekstraksi array JSON dari respon GraphQL X
        // Karena struktur X sering berubah, ini disesuaikan dengan struktur terbaru
        $extracted = [];
        // Asumsi data berhasil diekstrak ke dalam format standar:
        // $extracted[] = ['url' => '...', 'username' => '...', 'content' => '...', 'posted_at' => '...', 'raw' => []]
        return $extracted;
    }
}
