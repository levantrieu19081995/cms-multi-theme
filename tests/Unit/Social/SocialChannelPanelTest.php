<?php

declare(strict_types=1);

namespace Tests\Unit\Social;

use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Modules\CmsBackend\DataProviders\SocialPublicationDataProvider;
use Modules\CmsBackend\Enums\Social\SocialChannel;
use Modules\CmsBackend\Services\Social\SocialChannelRegistry;
use Modules\CmsBackend\Services\Social\SocialStatistics;
use Tests\TestCase;

/**
 * SocialStatistics::channelPanel() narrows the figures to one platform tab. No database: the data provider is a double.
 */
final class SocialChannelPanelTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function statistics(array $byChannel, ?SocialChannel $expectedRecent = null): SocialStatistics
    {
        $data = Mockery::mock(SocialPublicationDataProvider::class);
        $data->shouldReceive('countByChannelAndStatus')->andReturn($byChannel);
        $data->shouldReceive('recentByChannel')->andReturnUsing(function (SocialChannel $channel, int $limit) use ($expectedRecent) {
            if ($expectedRecent !== null) {
                $this->assertSame($expectedRecent, $channel);
            }

            return new Collection;
        });

        return new SocialStatistics($data, new SocialChannelRegistry);
    }

    public function test_panel_counts_only_the_requested_channel(): void
    {
        $stats = $this->statistics([
            'facebook' => ['published' => 3, 'failed' => 1, 'draft' => 2],
            'zalo' => ['published' => 9],
        ], SocialChannel::FACEBOOK);

        $panel = $stats->channelPanel(SocialChannel::FACEBOOK);

        $this->assertSame(SocialChannel::FACEBOOK, $panel['channel']);
        $this->assertSame(6, $panel['total']);
        $this->assertSame(3, $panel['published']);
        $this->assertSame(1, $panel['failed']);
        $this->assertSame(['published' => 3, 'failed' => 1, 'draft' => 2], $panel['by_status']);
        $this->assertTrue($panel['recent']->isEmpty());
    }

    public function test_channel_without_deliveries_has_zero_figures_and_only_its_own_metrics(): void
    {
        $panel = $this->statistics(['facebook' => ['published' => 4]], SocialChannel::ZALO)->channelPanel(SocialChannel::ZALO);

        $this->assertSame(0, $panel['total']);
        $this->assertSame(0, $panel['published']);
        $this->assertSame(0, $panel['failed']);
        $this->assertSame([], $panel['by_status']);
        // Zalo reports views and shares of an article; it must not show Facebook's reactions, comments or reach.
        $this->assertSame(['views', 'shares'], array_column($panel['metrics'], 'key'));
        foreach ($panel['metrics'] as $metric) {
            $this->assertSame(SocialChannel::ZALO, $metric['channel']);
            $this->assertFalse($metric['available'], 'No adapter is registered in this test.');
        }
    }

    public function test_metrics_belong_to_the_channel_of_the_panel(): void
    {
        $panel = $this->statistics([])->channelPanel(SocialChannel::FACEBOOK);

        $this->assertNotEmpty($panel['metrics']);
        foreach ($panel['metrics'] as $metric) {
            $this->assertSame(SocialChannel::FACEBOOK, $metric['channel']);
            $this->assertFalse($metric['available']);
        }
    }
}
