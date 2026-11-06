<?php

declare(strict_types=1);

namespace Tests\Unit\Homepage;

use Modules\CmsFrontend\Homepage\HomepageDataNeeds;
use PHPUnit\Framework\TestCase;

final class HomepageDataNeedsTest extends TestCase
{
    public function test_no_plan_loads_every_group(): void
    {
        $this->assertSame(array_keys(HomepageDataNeeds::GROUPS), array_keys(HomepageDataNeeds::forPlan(null)));
    }

    public function test_only_groups_of_enabled_blocks_are_loaded(): void
    {
        $plan = ['zones' => ['body' => [['definition' => 'media'], ['definition' => 'cta']]]];

        $this->assertSame(['library', 'videos'], array_keys(HomepageDataNeeds::forPlan($plan)));
    }

    public function test_unknown_definition_loads_every_group(): void
    {
        $plan = ['zones' => ['body' => [['definition' => 'brand-new-block']]]];

        $this->assertSame(array_keys(HomepageDataNeeds::GROUPS), array_keys(HomepageDataNeeds::forPlan($plan)));
    }

    public function test_empty_plan_loads_nothing(): void
    {
        $this->assertSame([], HomepageDataNeeds::forPlan(['zones' => []]));
    }
}
