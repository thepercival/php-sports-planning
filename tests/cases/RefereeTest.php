<?php

namespace RefereesPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsPlanning\TestHelper\PlanningCreator;
use SportsPlanning\Referee;

class RefereeTest extends TestCase
{
    use PlanningCreator;

    public function testConstruct()
    {
        $planning = $this->createPlanning($this->createInputNew([3]));
        $referee = new Referee($planning, 1);
        $referee->setPriority(2);
        self::assertSame($planning, $referee->getPlanning());
        self::assertSame(1, $referee->getNumber());
        self::assertSame(2, $referee->getPriority());
    }
}
