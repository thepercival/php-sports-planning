<?php


namespace SportsPlanning\Tests\Resource\GameCounter;

use SportsPlanning\Resource\GameCounter\Place as PlaceCounter;
use SportsPlanning\TestHelper\PlanningCreator;
use SportsPlanning\TestHelper\PlanningReplacer;

class PlaceTest extends \PHPUnit\Framework\TestCase
{
    use PlanningCreator, PlanningReplacer;

    public function testCalculations()
    {
        $planning = $this->createPlanning(
            $this->createInput( [3] )
        );

        $placeOne = $planning->getPoule(1)->getPlace(1);
        $gameCounter = new PlaceCounter($placeOne);

        self::assertSame($placeOne, $gameCounter->getPlace());

        self::assertSame($placeOne->getLocation(), $gameCounter->getIndex());
    }

}