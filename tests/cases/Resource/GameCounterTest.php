<?php


namespace SportsPlanning\Tests\Resource;

use SportsPlanning\Resource\GameCounter;
use SportsPlanning\TestHelper\PlanningCreator;
use SportsPlanning\TestHelper\PlanningReplacer;

class GameCounterTest extends \PHPUnit\Framework\TestCase
{
    use PlanningCreator, PlanningReplacer;

    public function testCalculations()
    {
        $planning = $this->createPlanning(
            $this->createInput( [3] )
        );

        $referee = $planning->getReferee(1);
        $gameCounter = new GameCounter($referee);

        self::assertSame("1", $gameCounter->getIndex());
        self::assertSame(0, $gameCounter->getNrOfGames());

        $gameCounter->increase();
        self::assertSame(1, $gameCounter->getNrOfGames());

        self::assertSame($referee, $gameCounter->getResource());
    }

}