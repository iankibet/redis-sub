
# Redis Subscriber for Laravel

[![Latest Stable Version](https://poser.pugx.org/iankibet/redis-sub/v/stable)](https://packagist.org/packages/iankibet/redis-sub)
[![Total Downloads](https://poser.pugx.org/iankibet/redis-sub/downloads)](https://packagist.org/packages/iankibet/redis-sub)
[![License](https://poser.pugx.org/iankibet/redis-sub/license)](https://packagist.org/packages/iankibet/redis-sub)

A Laravel package for listening to Redis published messages and handling them with jobs, events, or other handlers.

## Installation

You can install the package via Composer:

```bash
composer require iankibet/redis-sub
```

### Publish Configuration

After installation, publish the package configuration file:

```bash
php artisan vendor:publish --tag=redis-sub
```

This will create a `config/redis-sub.php` file where you can define the Redis channels and their handlers.

## Configuration

In `config/redis-sub.php`, define the Redis channels and their corresponding handlers:

```php
return [
    'channels' => [
        'members' => [
            \App\Jobs\ProcessMemberMessage::class,
            \App\Listeners\MemberListener::class,
        ],
        'notifications' => [
            \App\Events\NotificationReceived::class,
        ],
    ],
];
```

### Handlers

Handlers can be:
- **Jobs** (e.g., `ProcessMemberMessage` that implements `ShouldQueue`).
- **Events** (e.g., `NotificationReceived` that uses the `Dispatchable` trait).
- **Callable Classes** (e.g., `MemberListener` with an `__invoke` method or `handle` method).

### Example Handlers

#### Job Example

```php
<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessMemberMessage implements ShouldQueue
{
    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function handle()
    {
        logger()->info("Processed job message: {$this->message}");
    }
}
```

#### Event Example

```php
<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class NotificationReceived
{
    use Dispatchable;

    public $message;

    public function __construct($message)
    {
        $this->message = $message;
    }
}
```

#### Listener Example

```php
<?php

namespace App\Listeners;

class MemberListener
{
    public function __invoke($message)
    {
        logger()->info("Handled message with callable: {$message}");
    }
}
```

## Usage

Run the Redis subscriber command to listen to the configured channels:

```bash
php artisan redis:subscribe
```

The command will listen for messages published on the Redis channels and dispatch the configured handlers.

### Example Logs

When a message is published to the `members` channel, you'll see output like this:

```text
[2024-11-24 15:30:15] Dispatched job: App\Jobs\ProcessMemberMessage for channel: members
[2024-11-24 15:30:15] Called handler: App\Listeners\MemberListener for channel: members
```

## Debugging

- Timestamps are included in log messages for better debugging.
- Ensure the Redis service is running and accessible by Laravel.

## Contributing

Feel free to submit issues or pull requests. Contributions are welcome!

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
```

---

### Key Updates:
1. **Added Packagist Badges**: To display version, downloads, and license status.
2. **Installation Command**: Direct install command using `composer require iankibet/redis-sub`.
3. **Configuration Section**: Clear explanation of how to use the published configuration file.
4. **Usage and Debugging**: Detailed examples of expected behavior and logs.
5. **License**: Reference to the `LICENSE` file.

Let me know if you'd like additional refinements!
