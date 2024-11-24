<?php

namespace Iankibet\RedisSub;

use Illuminate\Support\Facades\Redis;

class RedisPub
{
    public static function publish(string $channel, $payload): void
    {
        $message = json_encode($payload);
        $redis = Redis::connection(config('redis-sub.connection'));
        $redis->publish($channel, $message);

    }
}
