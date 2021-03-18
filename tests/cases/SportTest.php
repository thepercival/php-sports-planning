<?php

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\GameMode;
use SportsPlanning\Sport;
use SportsPlanning\TestHelper\PlanningCreator;

class SportTest extends TestCase
{
    use PlanningCreator;

    public function testConstruct(): void
    {
        $planning = $this->createPlanning($this->createInputNew([3]));
        $sport = new Sport($planning, 1, GameMode::AGAINST, 2, 1);
        self::assertSame($planning, $sport->getPlanning());
        self::assertSame(1, $sport->getNumber());
        self::assertSame(2, $sport->getNrOfGamePlaces());
        self::assertSame(1, $sport->getGameAmount());
    }
}
