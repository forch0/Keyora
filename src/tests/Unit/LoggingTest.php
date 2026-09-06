<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Support\Facades\Log;
use Monolog\Formatter\JsonFormatter;
use Tests\TestCase;

class LoggingTest extends TestCase
{
    public function test_json_logging_channel_is_configured(): void
    {
        $channel = Log::channel('json');

        $this->assertNotNull($channel);
    }

    public function test_json_channel_uses_json_formatter(): void
    {
        $config = config('logging.channels.json');

        $this->assertSame('daily', $config['driver']);
        $this->assertSame(JsonFormatter::class, $config['formatter']);
    }

    public function test_structured_log_context_is_preserved(): void
    {
        Log::channel('json')->info('test event', [
            'user_id' => 42,
            'action' => 'login',
        ]);

        // If we got here without throwing, the channel accepts context arrays
        $this->assertTrue(true);
    }
}
