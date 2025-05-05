<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Schedules\Cycles;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Schedules\Cycles\ScheduleCycleTogether;
use SportsPlanning\Schedules\GamePlaces\ScheduleGamePlaceTogether;
use SportsPlanning\Schedules\Games\ScheduleGameTogether;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;
use SportsPlanning\Schedules\Sports\ScheduleAgainstOneVsOne;
use SportsPlanning\Schedules\Sports\ScheduleTogetherSport;
use SportsPlanning\Sports\SportWithNrOfCycles;

class ScheduleCycleTogetherTest extends TestCase
{

    public function testCreateNext(): void
    {
        $nrOfPlaces = 4;
        $togetherSport = new TogetherSport(1);
        $sportsWithNrOfCycles = [
            new SportWithNrOfCycles($togetherSport, 1)
        ];

        $scheduleWithNrOfPlaces = new ScheduleWithNrOfPlaces($nrOfPlaces, $sportsWithNrOfCycles);
        $togetherSportSchedules = $scheduleWithNrOfPlaces->getTogetherSportSchedules();
        $togetherSportSchedule = reset($togetherSportSchedules);
        self::assertInstanceOf(ScheduleTogetherSport::class, $togetherSportSchedule);

        $cycle = new ScheduleCycleTogether($togetherSportSchedule);
        self::assertInstanceOf(ScheduleCycleTogether::class, $cycle->createNext());
    }

//    public function testAddGame(): void
//    {
//        $nrOfPlaces = 4;
//        $cycle = new ScheduleCycleTogether($nrOfPlaces);
//        $game = new ScheduleGameTogether($cycle);
//        $game->addGamePlace(new ScheduleGamePlaceTogether($game, 1, 1));
//        $game->addGamePlace(new ScheduleGamePlaceTogether($game, 2, 1));
//
//        self::assertCount(1, $cycle->getGames());
//    }

//    public function testAddGameException(): void
//    {
//        $nrOfPlaces = 4;
//        $cycle = new ScheduleCycleTogether($nrOfPlaces);
//        $game = new ScheduleGameTogether($cycle);
//        $game->addGamePlace(new ScheduleGamePlaceTogether($game, 1, 1));
//        $game->addGamePlace(new ScheduleGamePlaceTogether($game, 2, 1));
//
//        $cycle2 = $cycle->createNext();
//        $game2 = new ScheduleGameTogether($cycle2);
//        $game2->addGamePlace(new ScheduleGamePlaceTogether($game, 1, 2));
//
//        self::expectException(\Exception::class);
//        $game2->addGamePlace(new ScheduleGamePlaceTogether($game, 1, 2));
//
//    }

//    public function testConvertToUniqueDuoPlaceNrs_1(): void
//    {
//        $gameRound = new TogetherGameRound(4);
//        $gameRound->addGame(1, [1,2,3]);
//
//        self::assertCount(3, $gameRound->convertToUniqueDuoPlaceNrs());
//    }


}
