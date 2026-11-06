<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Services\Social;

use Modules\CmsBackend\Enums\Social\SocialChannel;
use Modules\CmsBackend\Models\Social\SocialAccount;
use Modules\CmsBackend\Models\Social\SocialPublicationChannel;

/**
 * What a social channel must do, independent of the platform. Phase 2 adds one adapter per channel (Facebook first) and
 * registers it in SocialChannelRegistry; the publication service never changes.
 *
 * Every method runs on the server and talks to the platform server-to-server. Nothing here may be called from the browser, and
 * an adapter must never put a token or secret in a result, a message or a log line.
 */
interface SocialChannelAdapter
{
    public function channel(): SocialChannel;

    /** Check that the stored credentials of the account still work (token valid, page reachable). */
    public function validateConnection(SocialAccount $account): SocialOperationResult;

    /** Send the snapshot of the delivery's publication to the channel. */
    public function publish(SocialPublicationChannel $delivery, SocialAccount $account): SocialOperationResult;

    /** Take a delivery that was published down from the channel. */
    public function remove(SocialPublicationChannel $delivery, SocialAccount $account): SocialOperationResult;

    /** Read back whether the remote post still exists / its state. */
    public function syncStatus(SocialPublicationChannel $delivery, SocialAccount $account): SocialOperationResult;

    /** Read the interaction metrics of the remote post (reactions, comments, shares, reach ...). Keys are decided by the adapter. */
    public function syncMetrics(SocialPublicationChannel $delivery, SocialAccount $account): SocialOperationResult;
}
