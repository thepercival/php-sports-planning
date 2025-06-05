<?php

namespace SportsPlanning\Tests\Schedules\Games;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Against\AgainstSide;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Schedules\CycleParts\ScheduleCyclePartAgainstOneVsOne;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstOneVsOne;
use SportsPlanning\Schedules\GamePlaces\ScheduleGamePlaceAgainst;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstOneVsOne;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;
use SportsPlanning\Schedules\Sports\ScheduleAgainstOneVsOne;
use SportsPlanning\Sports\SportWithNrOfCycles;

final class ScheduleGameAgainstOneVsOneTest extends TestCase
{
    public function testGetSidePlaceNrs(): void
    {
        $nrOfPlaces = 5;
        $againstOneVsOne = new AgainstOneVsOne();
        $sportsWithNrOfCycles = [
            new SportWithNrOfCycles($againstOneVsOne, 1)
        ];

        $scheduleWithNrOfPlaces = new ScheduleWithNrOfPlaces($nrOfPlaces, $sportsWithNrOfCycles);
        $schedulesAgainstOneVsOne = $scheduleWithNrOfPlaces->getAgainstSportSchedules();
        $scheduleAgainstOneVsOne = reset($schedulesAgainstOneVsOne);
        self::assertInstanceOf(ScheduleAgainstOneVsOne::class, $scheduleAgainstOneVsOne);
        $cycle = new ScheduleCycleAgainstOneVsOne($scheduleAgainstOneVsOne);

        $cyclePart = new ScheduleCyclePartAgainstOneVsOne($cycle);
        $game = new ScheduleGameAgainstOneVsOne($cyclePart);
        new ScheduleGamePlaceAgainst($game, AgainstSide::Home, 1);
        self::assertCount(1, $game->getSidePlaceNrs(AgainstSide::Home) );
    }
}
