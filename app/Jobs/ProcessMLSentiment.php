<?php

namespace App\Jobs;

use App\Models\CrawledData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessMLSentiment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 30;

    protected $crawledDataId;

    public function __construct(string $crawledDataId)
    {
        $this->crawledDataId = $crawledDataId;
    }

    public function handle(): void
    {
        $data = CrawledData::find($this->crawledDataId);
        if (!$data || empty($data->content)) return;
        if (!empty($data->ai_sentiment)) return;

        try {
            $response = Http::timeout(25)->post('http://sentinel-api:8000/result/predict', [
                'text' => $data->content,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $ml = $result['data'] ?? $result;

                $data->update([
                    'ai_sentiment' => $ml['sentiment']    ?? null,
                    'main_topic'   => $ml['topic']        ?? null,
                    'location'     => $ml['location']     ?? null,
                    'is_emergency' => $ml['is_emergency'] ?? false,
                ]);

                Log::info('ML processed: ' . $data->id . ' => ' . ($ml['sentiment'] ?? 'unknown'));
            }
        } catch (\Exception $e) {
            Log::error('ML failed for ' . $this->crawledDataId . ': ' . $e->getMessage());
            throw $e;
        }
    }
}
