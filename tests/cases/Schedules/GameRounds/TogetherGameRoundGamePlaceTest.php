<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Schedules\GameRounds;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Schedules\GameRounds\TogetherGameRoundGamePlace;

class TogetherGameRoundGamePlaceTest extends TestCase
{

    public function testToString(): void
    {
        $gamePlace = new TogetherGameRoundGamePlace(1, 2);
        self::assertSame('2(1)', (string)$gamePlace);
    }
}
