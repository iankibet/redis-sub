<?php

namespace Iankibet\RedisSub\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\Console\Command\Command as CommandAlias;
use Throwable;

class RedisSubscriberCommand extends Command
{

    protected $signature = 'redis-sub:subscribe';
    protected $description = 'Subscribe to Redis channels and process messages with configured handlers';

    private const HANDLER_TYPES = [
        'job' => ShouldQueue::class,
        'event' => Dispatchable::class,
        'callable' => 'callable',
    ];

    public function handle(): int
    {
        try {
            $channels = $this->getChannelsToSubscribe();

            if (empty($channels)) {
                $this->error('No channels configured in redis-sub.php.');
                return CommandAlias::FAILURE;
            }

            $this->startSubscription($channels);

            return CommandAlias::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Failed to start Redis subscription: {$e->getMessage()}");
            Log::error('Redis subscription error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }

    protected function getChannelsToSubscribe(): array
    {
        $configuredChannels = config('redis-sub.channels', []);


        // If specific channels are requested, filter the configured channels


        return $configuredChannels;
    }

    protected function startSubscription(array $channels): void
    {
        $this->info('🚀 Starting Redis subscription service...');

        foreach ($channels as $channel => $handlers) {
            $this->info("📡 Listening on channel: {$channel}");
            $this->displayHandlers($handlers);
        }
        $connection = config('redis-sub.connection', 'default');
        $redis = Redis::connection($connection);
        $redis->subscribe(array_keys($channels), function ($message, $channel) use ($channels) {
            $redisDbPrefix = config('database.redis.options.prefix', '');
            $channel = str_replace($redisDbPrefix, '', $channel);
            $this->processMessage($message, $channel, $channels[$channel] ?? []);
        });
    }

    protected function processMessage(string $message, string $channel, array $handlers): void
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $this->warn("⚡ [{$timestamp}] Received message on channel: {$channel}");
        if (empty($handlers)) {
            $this->warn("⚠️ No handlers defined for channel: {$channel}");
            return;
        }

        $handlers = is_array($handlers) ? $handlers : [$handlers];

        foreach ($handlers as $handler) {
            try {
                $this->dispatchHandler($handler, $message, $channel, $timestamp);
            } catch (Throwable $e) {
                $this->error("❌ [{$timestamp}] Failed to process handler {$handler}: {$e->getMessage()}");
                Log::error('Redis handler error', [
                    'handler' => $handler,
                    'channel' => $channel,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }

    protected function dispatchHandler(string $handler, string $message, string $channel, string $timestamp): void
    {
        if (!class_exists($handler)) {
            throw new \RuntimeException("Handler class [{$handler}] does not exist");
        }

        $handlerType = $this->determineHandlerType($handler);
        $payload = json_decode($message);
        match ($handlerType) {
            'job' => $this->dispatchJob($handler, $payload, $channel, $timestamp),
            'event' => $this->dispatchEvent($handler, $payload, $channel, $timestamp),
            'callable' => $this->invokeHandler($handler, $payload, $channel, $timestamp),
            default => throw new \RuntimeException("Invalid handler type for [{$handler}]")
        };
    }

    protected function determineHandlerType(string $handler): string
    {
        if (str_contains($handler, 'App\Events')) {
            return 'event';
        }
        // check if job
        if (str_contains($handler, 'App\Jobs')) {
            return 'job';
        }
        foreach (self::HANDLER_TYPES as $type => $class) {
            if ($class === 'callable') {
                $instance = new $handler();
                if (is_callable($instance) || method_exists($instance, 'handle')) {
                    return $type;
                }
                continue;
            }

            if (is_subclass_of($handler, $class)) {
                return $type;
            }
        }

        throw new \RuntimeException("Cannot determine handler type for [{$handler}]");
    }

    protected function dispatchJob(string $handler, $payload, string $channel, string $timestamp): void
    {
        dispatch(new $handler($payload));
        $this->info("✅ [{$timestamp}] Dispatched job: {$handler}");
    }

    protected function dispatchEvent(string $handler, $payload, string $channel, string $timestamp): void
    {
        event(new $handler($payload));
        $this->info("✅ [{$timestamp}] Dispatched event: {$handler}");
    }

    protected function invokeHandler(string $handler, $payload, string $channel, string $timestamp): void
    {
        $instance = new $handler();

        if (is_callable($instance)) {
            $instance($payload);
            $this->info("✅ [{$timestamp}] Invoked handler: {$handler}");
            return;
        }

        if (method_exists($instance, 'handle')) {
            $instance->handle($payload);
            $this->info("✅ [{$timestamp}] Called handle method: {$handler}");
            return;
        }

        throw new \RuntimeException("Handler [{$handler}] is not properly configured");
    }

    protected function displayHandlers(array $handlers): void
    {
        $handlers = is_array($handlers) ? $handlers : [$handlers];
        foreach ($handlers as $handler) {
            $type = $this->determineHandlerType($handler);
            $this->line("   └─ 🔧 Handler: {$handler} ({$type})");
        }
    }
}
