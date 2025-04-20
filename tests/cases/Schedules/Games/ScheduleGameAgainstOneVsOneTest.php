<?php

namespace SportsPlanning\Tests\Schedules\Games;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Against\AgainstSide;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Schedules;
use SportsPlanning\Schedules\GamePlaces\ScheduleGamePlaceAgainst;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstOneVsOne;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;
use SportsPlanning\Schedules\Sports\ScheduleAgainstOneVsOne;

class ScheduleGameAgainstOneVsOneTest extends TestCase
{
    public function testCycleNrAndPartNr(): void
    {
        $schedule = new ScheduleWithNrOfPlaces(5, []);
        $scheduleSport = new ScheduleAgainstOneVsOne($schedule, 1, new AgainstOneVsOne());

        $game = new ScheduleGameAgainstOneVsOne($scheduleSport, 1, 1);
        self::assertSame(1, $game->cycleNr );
        self::assertSame(1, $game->cyclePartNr );
    }

    public function testGetSidePlaceNrs(): void
    {
        $schedule = new ScheduleWithNrOfPlaces(5, []);
        $scheduleSport = new ScheduleAgainstOneVsOne($schedule, 1, new AgainstOneVsOne());

        $game = new ScheduleGameAgainstOneVsOne($scheduleSport, 1, 1);
        new ScheduleGamePlaceAgainst($game, AgainstSide::Home, 1);
        self::assertCount(1, $game->getSidePlaceNrs(AgainstSide::Home) );
    }
}
