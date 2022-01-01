<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Resource\GameCounter;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Resource\GameCounter\Place as PlaceCounter;
use SportsPlanning\TestHelper\PlanningCreator;
use SportsPlanning\TestHelper\PlanningReplacer;

class PlaceTest extends TestCase
{
    use PlanningCreator;
    use PlanningReplacer;

    public function testCalculations(): void
    {
        $planning = $this->createPlanning(
            $this->createInput([3])
        );

        $placeOne = $planning->getInput()->getPoule(1)->getPlace(1);
        $gameCounter = new PlaceCounter($placeOne);

        self::assertSame($placeOne, $gameCounter->getPlace());

        self::assertSame($placeOne->getLocation(), $gameCounter->getIndex());
    }
}
