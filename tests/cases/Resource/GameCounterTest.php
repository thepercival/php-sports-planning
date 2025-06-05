<?php

namespace SportsPlanning\Tests\Resource;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Referee;
use SportsPlanning\Resource\GameCounter;

final class GameCounterTest extends TestCase
{


    public function testCalculations(): void
    {
        $referee = new Referee(1);
        $gameCounter = new GameCounter($referee);

        self::assertSame("1", $gameCounter->getIndex());
        self::assertSame(0, $gameCounter->getNrOfGames());

        $gameCounter = $gameCounter->increment();
        self::assertSame(1, $gameCounter->getNrOfGames());

        self::assertSame($referee, $gameCounter->getResource());
    }
}
