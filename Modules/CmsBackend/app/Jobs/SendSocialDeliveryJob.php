<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\CmsBackend\Services\Social\SocialPublicationService;

/**
 * Sends ONE delivery (one channel of one publication) to its platform. It runs once only: a post that may already be on the
 * platform must never be sent again by an automatic retry. A failed delivery is retried by a person ("Đăng lại").
 * With QUEUE_CONNECTION=sync it runs inside the request; with the database queue it needs `php artisan queue:work`.
 */
final class SendSocialDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /** A video upload to Facebook can take minutes (the client waits at most 280 s); config/queue.php retry_after is longer. */
    public int $timeout = 300;

    public function __construct(public readonly int $deliveryId) {}

    public function handle(SocialPublicationService $service): void
    {
        $service->deliver($this->deliveryId);
    }

    /** Anything that escapes deliver() (it catches its own errors) must not leave the delivery "processing" forever. */
    public function failed(?\Throwable $exception): void
    {
        app(SocialPublicationService::class)->markDeliveryFailed($this->deliveryId);
    }
}
