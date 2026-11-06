<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social;

use Modules\CmsBackend\DataProviders\SocialPublicationDataProvider;
use Modules\CmsBackend\Enums\Social\SocialChannel;
use Modules\CmsBackend\Enums\Social\SocialChannelStatus;
use Modules\CmsBackend\Enums\Social\SocialPublicationStatus;

/**
 * Figures of the overview and statistics screens. Only real rows are counted. Interaction metrics of a platform
 * (reactions, comments ...) are declared in metricCatalog(): a metric is `available` only when the channel has an adapter,
 * so Phase 2 adds metrics by extending the catalog, without touching the screens.
 */
final class SocialStatistics
{
    public function __construct(
        private SocialPublicationDataProvider $data,
        private SocialChannelRegistry $registry,
    ) {}

    /** @return array<string, mixed> */
    public function overview(): array
    {
        $byStatus = $this->data->countByStatus();
        $sum = fn (SocialPublicationStatus ...$statuses) => array_sum(array_map(fn ($status) => $byStatus[$status->value] ?? 0, $statuses));

        $channels = [];
        foreach (SocialChannel::cases() as $channel) {
            $account = $this->data->connectedAccount($channel);
            $channels[$channel->value] = ['channel' => $channel, 'connected' => $account !== null, 'account' => $account?->name];
        }

        return [
            'total' => array_sum($byStatus),
            'draft' => $sum(SocialPublicationStatus::DRAFT, SocialPublicationStatus::READY),
            'in_progress' => $sum(...SocialPublicationStatus::inProgress()),
            'published' => $sum(SocialPublicationStatus::PUBLISHED),
            'failed' => $sum(SocialPublicationStatus::FAILED),
            'connected_accounts' => $this->data->connectedAccountCount(),
            'channels' => $channels,
        ];
    }

    /** @return array<string, mixed> */
    public function statistics(): array
    {
        $byStatus = $this->data->countByStatus();
        $byChannel = $this->data->countByChannelAndStatus();

        $statuses = [];
        foreach (SocialPublicationStatus::cases() as $status) {
            $statuses[] = ['status' => $status, 'total' => $byStatus[$status->value] ?? 0];
        }

        $channels = [];
        foreach (SocialChannel::cases() as $channel) {
            $rows = $byChannel[$channel->value] ?? [];
            $channels[] = [
                'channel' => $channel,
                'total' => array_sum($rows),
                'published' => $rows[SocialChannelStatus::PUBLISHED->value] ?? 0,
                'failed' => $rows[SocialChannelStatus::FAILED->value] ?? 0,
                'by_status' => $rows,
            ];
        }

        return [
            'total' => array_sum($byStatus),
            'statuses' => $statuses,
            'channels' => $channels,
            'creators' => $this->data->countByCreator(),
            'metrics' => $this->metricCatalog(),
        ];
    }

    /**
     * Interaction metrics a channel can report. `available` follows the adapter: none exists in Phase 1, so no number is shown.
     *
     * @return array<int, array{channel: SocialChannel, key: string, available: bool}>
     */
    public function metricCatalog(): array
    {
        $metrics = [];
        foreach (['reactions', 'comments', 'shares', 'reach'] as $key) {
            // The Facebook adapter exists since Phase 2A but syncMetrics() is not implemented yet: no figure is shown.
            $metrics[] = ['channel' => SocialChannel::FACEBOOK, 'key' => $key, 'available' => false];
        }

        return $metrics;
    }
}
