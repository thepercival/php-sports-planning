<?php

namespace SportsPlanning\Tests\Sport;

use PHPUnit\Framework\TestCase;
use SportsHelpers\GameMode;
use SportsPlanning\Sport;
use SportsPlanning\TestHelper\PlanningCreator;

class CounterTest extends TestCase
{
    // probably deprecated, first test planning of multiple sports

    public function testConstruct()
    {
        $nrOfGamesToGo = 3;
        $minNrOfGamesMap = [];
        $nrOfGamesDoneMap = [];
        $nrOfSportsToGo = 3;
        $sportsCounter = new Sport\Counter(
            $nrOfGamesToGo,
            $minNrOfGamesMap,
            $nrOfGamesDoneMap,
            $nrOfSportsToGo
        );
        self::assertSame(3, $sportsCounter->getNrOfSportsToGo());
    }
}
