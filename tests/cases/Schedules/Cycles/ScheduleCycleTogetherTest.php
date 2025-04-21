<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Schedules\Cycles;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Schedules\Cycles\ScheduleCycleTogether;
use SportsPlanning\Schedules\GamePlaces\ScheduleGamePlaceTogether;
use SportsPlanning\Schedules\Games\ScheduleGameTogether;

class ScheduleCycleTogetherTest extends TestCase
{

    public function testCreateNext(): void
    {
        $nrOfPlaces = 4;
        $cycle = new ScheduleCycleTogether($nrOfPlaces);
        self::assertInstanceOf(ScheduleCycleTogether::class, $cycle->createNext());
    }

    public function testAddGame(): void
    {
        $nrOfPlaces = 4;
        $cycle = new ScheduleCycleTogether($nrOfPlaces);
        $game = new ScheduleGameTogether($cycle);
        $game->addGamePlace(new ScheduleGamePlaceTogether($game, 1, 1));
        $game->addGamePlace(new ScheduleGamePlaceTogether($game, 2, 1));

        self::assertCount(1, $cycle->getGames());
    }

    public function testAddGameException(): void
    {
        $nrOfPlaces = 4;
        $cycle = new ScheduleCycleTogether($nrOfPlaces);
        $game = new ScheduleGameTogether($cycle);
        $game->addGamePlace(new ScheduleGamePlaceTogether($game, 1, 1));
        $game->addGamePlace(new ScheduleGamePlaceTogether($game, 2, 1));

        $cycle2 = $cycle->createNext();
        $game2 = new ScheduleGameTogether($cycle2);
        $game2->addGamePlace(new ScheduleGamePlaceTogether($game, 1, 2));

        self::expectException(\Exception::class);
        $game2->addGamePlace(new ScheduleGamePlaceTogether($game, 1, 2));

    }

//    public function testConvertToUniqueDuoPlaceNrs_1(): void
//    {
//        $gameRound = new TogetherGameRound(4);
//        $gameRound->addGame(1, [1,2,3]);
//
//        self::assertCount(3, $gameRound->convertToUniqueDuoPlaceNrs());
//    }


}
