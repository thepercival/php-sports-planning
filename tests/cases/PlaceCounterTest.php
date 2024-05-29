<?php

declare(strict_types=1);

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Counters\CounterForPlace;
use SportsPlanning\TestHelper\PlanningCreator;

class PlaceCounterTest extends TestCase
{
    use PlanningCreator;

    public function testSimple(): void
    {
        $input = $this->createInput([5]);
        $place = $input->getPoule(1)->getPlace(1);
        $placeCounter = new CounterForPlace($place);
        self::assertSame(1, $placeCounter->getPlaceNr());
    }

    public function testCounter(): void
    {
        $input = $this->createInput([5]);
        $place = $input->getPoule(1)->getPlace(1);
        $placeCounter = new CounterForPlace($place);
        self::assertCount(3, $placeCounter->increment()->increment()->increment());
    }
}
