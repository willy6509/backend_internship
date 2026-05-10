<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class CheckApiKeyTest extends TestCase
{
    public function test_allows_valid_ip_and_api_key()
    {
        Config::set('sentinel.allowed_ips', ['127.0.0.1']);
        Config::set('sentinel.api_key', 'secret-key');

        $request = Request::create('/api/internal/ingest', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        $request->headers->set('x-api-key', 'secret-key');

        $middleware = new \App\Http\Middleware\CheckApiKey();
        $response = $middleware->handle($request, function ($req) {
            return response('ok', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_blocks_request_from_unallowed_ip()
    {
        Config::set('sentinel.allowed_ips', ['10.0.0.1']);
        Config::set('sentinel.api_key', 'secret-key');

        $request = Request::create('/api/internal/ingest', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        $request->headers->set('x-api-key', 'secret-key');

        $middleware = new \App\Http\Middleware\CheckApiKey();
        $response = $middleware->handle($request, function ($req) {
            return response('ok', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('Akses Ditolak', $response->getContent());
    }

    public function test_blocks_request_with_invalid_api_key()
    {
        Config::set('sentinel.allowed_ips', ['127.0.0.1']);
        Config::set('sentinel.api_key', 'expected-key');

        $request = Request::create('/api/internal/ingest', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        $request->headers->set('x-api-key', 'wrong-key');

        $middleware = new \App\Http\Middleware\CheckApiKey();
        $response = $middleware->handle($request, function ($req) {
            return response('ok', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('API Key', $response->getContent());
    }
}
