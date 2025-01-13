<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Schedule\GameRounds;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Schedule\GameRounds\TogetherGameRound;
use SportsPlanning\Schedule\GameRounds\TogetherGameRoundGamePlace;

class TogetherGameRoundTest extends TestCase
{

    public function testCreateNext(): void
    {
        $nrOfPlaces = 4;
        $gameRound = new TogetherGameRound($nrOfPlaces);
        self::assertInstanceOf(TogetherGameRound::class, $gameRound->createNext());
    }

    public function testAddGame(): void
    {
        $gameRound = new TogetherGameRound(4);
        $gameRound->addGame([
            new TogetherGameRoundGamePlace(1, 1),
            new TogetherGameRoundGamePlace(1,2)
        ]);
        self::assertCount(1, $gameRound->getGames());
    }

    public function testAddGameException(): void
    {
        $gameRound = new TogetherGameRound(4);
        $gameRound->addGame([
            new TogetherGameRoundGamePlace(1, 1),
            new TogetherGameRoundGamePlace(1,2)
        ]);
        self::expectException(\Exception::class);
        $gameRound->addGame([
            new TogetherGameRoundGamePlace(1, 2),
            new TogetherGameRoundGamePlace(1,3)
        ]);
    }

//    public function testConvertToUniqueDuoPlaceNrs_1(): void
//    {
//        $gameRound = new TogetherGameRound(4);
//        $gameRound->addGame(1, [1,2,3]);
//
//        self::assertCount(3, $gameRound->convertToUniqueDuoPlaceNrs());
//    }


}
