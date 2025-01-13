<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Schedule\GameRounds;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\Planning;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsPlanning\Schedule\GameRounds\TogetherGameRoundGame;
use SportsPlanning\Schedule\GameRounds\GameRoundTogetherGamePlace;
use SportsPlanning\Schedule\GameRounds\TogetherGameRound;
use SportsPlanning\Schedule\GameRounds\TogetherGameRoundGamePlace;
use SportsPlanning\TestHelper\PlanningCreator;

class TogetherGameRoundGameTest extends TestCase
{
    public function testCreateWithSamePlaceNr(): void
    {
        self::expectException(\Exception::class);
        new TogetherGameRoundGame([
            new TogetherGameRoundGamePlace(1, 1),
            new TogetherGameRoundGamePlace(1,2),
            new TogetherGameRoundGamePlace(2,1)
        ]);
    }

    public function testCreateUniqueNumber(): void
    {
        $game = new TogetherGameRoundGame([
            new TogetherGameRoundGamePlace(1, 1),
            new TogetherGameRoundGamePlace(1,2),
            new TogetherGameRoundGamePlace(1,3)
        ]);
        self::assertSame(7, $game->createUniqueNumber());
    }

    public function testCount(): void
    {
        $game = new TogetherGameRoundGame([
            new TogetherGameRoundGamePlace(1, 1),
            new TogetherGameRoundGamePlace(1,2),
            new TogetherGameRoundGamePlace(1,3)
        ]);
        self::assertSame(3, $game->count());
    }

    public function testHas(): void
    {
        $game = new TogetherGameRoundGame([
            new TogetherGameRoundGamePlace(1, 1),
            new TogetherGameRoundGamePlace(1,2),
            new TogetherGameRoundGamePlace(1,3)
        ]);
        self::assertTrue($game->has(1));
        self::assertFalse($game->has(4));
    }

    public function testConvertToUniqueDuoPlaceNrs(): void
    {
        $game = new TogetherGameRoundGame([
            new TogetherGameRoundGamePlace(1, 1),
            new TogetherGameRoundGamePlace(1,2),
            new TogetherGameRoundGamePlace(1,3)
        ]);

        self::assertCount(3, $game->convertToDuoPlaceNrs());
    }

    public function testGetPlaceNrs(): void
    {
        $game = new TogetherGameRoundGame([
            new TogetherGameRoundGamePlace(1, 1),
            new TogetherGameRoundGamePlace(1,2),
            new TogetherGameRoundGamePlace(1,3)
        ]);

        self::assertCount(3, $game->gamePlaces);
    }

    public function testToString(): void
    {
        $game = new TogetherGameRoundGame([
            new TogetherGameRoundGamePlace(1, 1),
            new TogetherGameRoundGamePlace(1,2),
            new TogetherGameRoundGamePlace(1,3)
        ]);

        self::assertSame('1(1) & 2(1) & 3(1)', (string)$game);
    }
}
