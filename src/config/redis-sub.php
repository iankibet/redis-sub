<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Redis Channel Listeners
    |--------------------------------------------------------------------------
    |
    | Define the Redis channels you want to listen to and their corresponding
    | handlers. Handlers can be a single handler or an array of handlers.
    |
    */
    'channels' => [
//        'members' => [
//            \App\Jobs\ProcessMemberMessage::class,
//            \App\Listeners\MemberListener::class,
//        ],
//        'notifications' => \App\Events\MemberMessageReceived::class, // Single handler
    ],
    'connection' => 'pubsub',
];
