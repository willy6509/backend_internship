<?php

namespace App\Services\Crawlers;

use Illuminate\Support\Facades\Http;
use App\Models\SocialPost;
use App\Models\SocialAuthor;

class TwitterCrawlerService
{
    protected $headers;

    public function __construct()
    {
        // Ambil dari .env, jangan hardcode!
        $this->headers = [
            'authorization' => 'Bearer ' . config('services.twitter.bearer_token'),
            'x-csrf-token' => config('services.twitter.ct0'),
            'cookie' => 'auth_token=' . config('services.twitter.auth_token') . '; ct0=' . config('services.twitter.ct0'),
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ];
    }

    public function searchPosts($keyword, $limit)
    {
        // URL endpoint GraphQL Twitter (Harus sering diupdate manual jika Twitter ubah API)
        $url = 'https://twitter.com/i/api/graphql/.../SearchTimeline'; 
        
        $results = [];
        $cursor = null;

        // Loop paginasi manual
        do {
            $response = Http::withHeaders($this->headers)
                ->get($url, [
                    'variables' => json_encode([
                        'rawQuery' => $keyword,
                        'count' => 20,
                        'cursor' => $cursor
                    ]),
                    'features' => json_encode(['responsive_web_graphql_timeline_navigation_enabled' => true])
                ]);

            if ($response->failed()) {
                throw new \Exception("Twitter Blocked/Error: " . $response->status());
            }

            $data = $response->json();
            
            // Parsing Logic (Sangat bergantung struktur JSON Twitter)
            // ... (Kode parsing JSON Twitter yang kompleks disini) ...
            
            // SIMPAN KE DB (Contoh sederhana)
            foreach ($parsedTweets as $tweet) {
                $this->saveToDb($tweet);
                $results[] = $tweet;
            }
            
            // Random Delay agar tidak terdeteksi bot (Sangat Penting!)
            sleep(rand(3, 7));

        } while (count($results) < $limit);

        return $results;
    }

    private function saveToDb($data)
    {
        // 1. Simpan/Update Author
        $author = SocialAuthor::firstOrCreate(
            ['platform' => 'twitter', 'platform_user_id' => $data['user_id']],
            ['username' => $data['screen_name']]
        );

        // 2. Simpan Post
        SocialPost::updateOrCreate(
            ['social_post_id' => $data['id_str']],
            [
                'author_id' => $author->id,
                'content' => $data['full_text'],
                'url_permalink' => "https://twitter.com/{$data['screen_name']}/status/{$data['id_str']}",
                'posted_at' => date('Y-m-d H:i:s', strtotime($data['created_at'])),
                'type' => 'post'
            ]
        );
    }
}