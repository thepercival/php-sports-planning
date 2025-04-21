<?php

namespace SportsPlanning\Tests\Schedules\Games;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Schedules\CycleParts\ScheduleCyclePartAgainst;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainst;
use SportsPlanning\Schedules\GamePlaces\ScheduleGamePlaceAgainst;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstOneVsOne;

class ScheduleGameAgainstOneVsOneTest extends TestCase
{
    public function testGetSidePlaceNrs(): void
    {
        $nrOfPlaces = 5;
        $cycle = new ScheduleCycleAgainst($nrOfPlaces);

        $cyclePart = new ScheduleCyclePartAgainst($cycle);
        $game = new ScheduleGameAgainstOneVsOne($cyclePart);
        new ScheduleGamePlaceAgainst($game, AgainstSide::Home, 1);
        self::assertCount(1, $game->getSidePlaceNrs(AgainstSide::Home) );
    }
}
