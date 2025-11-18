<?php

namespace SportsPlanning\Tests\Resource;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Resource\GameCounter;
use SportsPlanning\TestHelper\PlanningCreator;

final class GameCounterTest extends TestCase
{
    use PlanningCreator;

    public function testCalculations(): void
    {
        $input = $this->createInput([3]);

        $referee = $input->getReferee(1);
        $gameCounter = new GameCounter($referee);

        self::assertSame("1", $gameCounter->getIndex());
        self::assertSame(0, $gameCounter->getNrOfGames());

        $gameCounter->increment();
        self::assertSame(1, $gameCounter->getNrOfGames());

        self::assertSame($referee, $gameCounter->getResource());
    }
}
