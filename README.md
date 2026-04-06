# Quill Rate Limiter Middleware

High-performance rate limiting middleware for the [Quill PHP Framework](https://quillphp.com). Protects your API from brute-force and DDoS attacks.

## Installation

```bash
composer require quillphp/limiter
```

## Usage

```php
use Quill\Limiter\Limiter;
use Quill\Storage\RedisStorage;

$app->use(Limiter::new([
    'storage' => new RedisStorage(['host' => '127.0.0.1']),
    'limit'   => 100, // 100 requests
    'window'  => 60,  // per 60 seconds
]));
```

## Configuration

| Option | Default | Description |
|---|---|---|
| \`limit\` | \`100\` | Maximum number of requests allowed in the window. |
| \`window\` | \`60\` | Time window in seconds. |
| \`key_generator\` | \`fn(Request \$r) => \$r->ip()\` | Custom key generator for throttling. |
| \`error_code\` | \`429\` | HTTP status code for rate limit exceeded. |
| \`error_message\` | \`'Too Many Requests'\` | Error message for rate limit exceeded. |

## License

MIT
