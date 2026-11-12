<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social;

use Illuminate\Support\Collection;
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
     * Everything one platform tab shows: its delivery figures, its interaction metrics and its latest publications.
     * Read only; the figures are the ones of statistics(), narrowed to the channel.
     *
     * @return array{channel: SocialChannel, total: int, published: int, failed: int, by_status: array<string, int>, metrics: array<int, array{channel: SocialChannel, key: string, available: bool, value: ?int}>, recent: Collection}
     */
    public function channelPanel(SocialChannel $channel, int $recentLimit = 5): array
    {
        $rows = $this->data->countByChannelAndStatus()[$channel->value] ?? [];

        return [
            'channel' => $channel,
            'total' => array_sum($rows),
            'published' => $rows[SocialChannelStatus::PUBLISHED->value] ?? 0,
            'failed' => $rows[SocialChannelStatus::FAILED->value] ?? 0,
            'by_status' => $rows,
            'metrics' => array_values(array_filter(
                $this->metricCatalog(),
                fn (array $metric) => $metric['channel'] === $channel,
            )),
            'recent' => $this->data->recentByChannel($channel, $recentLimit),
        ];
    }

    /**
     * Interaction metrics each channel can report. A metric is `available` when the channel has an adapter that reads it; `value` is
     * then the total last read from the platform (null while nothing was synchronized).
     *  - Facebook: reactions, comments, shares. Reach is listed but never available: Meta removed the post reach metrics.
     *  - Zalo OA: views and shares of an article (article/getslice). Zalo documents no likes or comments.
     *
     * @return array<int, array{channel: SocialChannel, key: string, available: bool, value: ?int}>
     */
    public function metricCatalog(): array
    {
        $declared = [
            SocialChannel::FACEBOOK->value => ['reactions', 'comments', 'shares', 'reach'],
            SocialChannel::ZALO->value => ['views', 'shares'],
        ];
        $neverAvailable = ['reach'];

        $metrics = [];
        foreach ($declared as $channelValue => $keys) {
            $channel = SocialChannel::from($channelValue);
            $connectable = $this->registry->has($channel);
            $totals = $connectable ? $this->data->metricTotals($channel) : [];
            foreach ($keys as $key) {
                $available = $connectable && ! in_array($key, $neverAvailable, true);
                $metrics[] = ['channel' => $channel, 'key' => $key, 'available' => $available, 'value' => $available ? ($totals[$key] ?? null) : null];
            }
        }

        return $metrics;
    }
}
