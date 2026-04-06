<?php

declare(strict_types=1);

namespace Quill\Limiter;

use Quill\Contracts\ConfigurableMiddleware;
use Quill\Contracts\MiddlewareInterface;
use Quill\Contracts\StorageInterface;
use Quill\Http\Request;
use Quill\Http\HttpResponse;

/**
 * Rate Limiting middleware for Quill.
 * Protects APIs from brute-force and DDoS by throttling requests.
 */
class Limiter implements MiddlewareInterface
{
    use ConfigurableMiddleware;

    /** @var array<string, mixed> */
    protected array $config = [];

    private ?StorageInterface $storage;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge(self::defaults(), $config);
        $this->storage = $this->config['storage'];
        
        if (!$this->storage) {
            throw new \InvalidArgumentException('Limiter middleware requires a StorageInterface implementation.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected static function defaults(): array
    {
        return [
            'storage'           => null, // REQUIRED: StorageInterface
            'limit'             => 100,  // Max requests per window
            'window'            => 60,   // Window size in seconds
            'error_code'        => 429,
            'error_message'     => 'Too Many Requests',
            'key_generator'     => function (Request $request) {
                return $request->ip(); // Default to client IP
            },
            'skip'              => null, // Optional closure fn(Request) => bool
        ];
    }

    public function handle(Request $request, callable $next): mixed
    {
        if (is_callable($this->config['skip']) && ($this->config['skip'])($request)) {
            return $next($request);
        }

        $key = 'limiter:' . ($this->config['key_generator'])($request);
        $current = $this->storage->get($key);
        
        $count = $current !== null ? (int)$current : 0;
        
        if ($count >= $this->config['limit']) {
            return new HttpResponse(
                ['error' => $this->config['error_message']],
                $this->config['error_code'],
                [
                    'X-RateLimit-Limit'     => (string)$this->config['limit'],
                    'X-RateLimit-Remaining' => '0',
                    'Retry-After'           => (string)$this->config['window'],
                ]
            );
        }

        // Increment (simple implementation via StorageInterface)
        $this->storage->set($key, (string)($count + 1), $this->config['window']);

        $response = $next($request);

        if ($response instanceof HttpResponse) {
            $response->headers['X-RateLimit-Limit'] = (string)$this->config['limit'];
            $response->headers['X-RateLimit-Remaining'] = (string)($this->config['limit'] - ($count + 1));
        }

        return $response;
    }
}
