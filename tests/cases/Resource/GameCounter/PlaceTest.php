<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Resource\GameCounter;

use PHPUnit\Framework\TestCase;
use SportsPlanning\PlaceCounter;
use SportsPlanning\TestHelper\PlanningCreator;

class PlaceTest extends TestCase
{
    use PlanningCreator;

    public function testCalculations(): void
    {
        $input = $this->createInput([3]);

        $placeOne = $input->getPoule(1)->getPlace(1);
        $gameCounter = new PlaceCounter($placeOne);

        self::assertSame($placeOne, $gameCounter->getPlace());
    }
}
