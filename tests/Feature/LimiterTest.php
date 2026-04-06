<?php

namespace Quill\Limiter\Tests\Feature;

use Quill\Limiter\Tests\TestCase;
use Quill\Limiter\Limiter;
use Quill\Http\HttpResponse;
use Quill\Contracts\StorageInterface;

class LimiterTest extends TestCase
{
    private array $memoryStorage = [];

    /**
     * Create a simple in-memory mock for StorageInterface.
     */
    protected function getStorageMock(): StorageInterface
    {
        return new class($this->memoryStorage) implements StorageInterface {
            private array $db;
            public function __construct(array &$db) { $this->db = &$db; }
            public function get(string $key): ?string { return $this->db[$key] ?? null; }
            public function set(string $key, string $value, int $ttl = 0): void { 
                $this->db[$key] = $value; 
            }
            public function delete(string $key): void { unset($this->db[$key]); }
            public function reset(): void { $this->db = []; }
            public function close(): void {}
        };
    }

    /**
     * Verify that requests within the configured limit are allowed.
     */
    public function test_within_limit_passes_through(): void
    {
        $storage = $this->getStorageMock();
        $middleware = Limiter::new([
            'limit' => 5,
            'storage' => $storage,
            'window' => 60
        ]);
        
        $request = $this->createRequest('GET', '/api/data');
        $response = $middleware->handle($request, $this->getNextHandler());
        
        $this->assertEquals(200, $response->status);
        $this->assertArrayHasKey('X-RateLimit-Limit', $response->headers);
        $this->assertEquals('5', $response->headers['X-RateLimit-Limit']);
        $this->assertEquals('4', $response->headers['X-RateLimit-Remaining']);
    }

    /**
     * Verify that exceeding the limit returns a 429 Too Many Requests response.
     */
    public function test_over_limit_returns_429(): void
    {
        $storage = $this->getStorageMock();
        $middleware = Limiter::new([
            'limit' => 1,
            'storage' => $storage
        ]);
        
        $request = $this->createRequest('GET', '/api/data');
        
        // First request - PASS
        $middleware->handle($request, $this->getNextHandler());
        
        // Second request - FAIL
        $response = $middleware->handle($request, $this->getNextHandler());
        
        $this->assertEquals(429, $response->status);
        $this->assertEquals('0', $response->headers['X-RateLimit-Remaining']);
        $this->assertStringContainsString('Too Many Requests', json_encode($response->data));
    }
}
