<?php

namespace App\Observers;

use App\Models\CrawledData;
use App\Jobs\ProcessMLSentiment;

class CrawledDataObserver
{
    public function created(CrawledData $crawledData): void
    {
        ProcessMLSentiment::dispatch($crawledData->id)->delay(now()->addSeconds(2));
    }
}
