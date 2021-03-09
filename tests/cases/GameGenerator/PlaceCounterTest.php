<?php

namespace SportsPlanning\Tests\GameGenerator;

use PHPUnit\Framework\TestCase;
use SportsHelpers\GameMode;
use SportsHelpers\SportBase;
use SportsHelpers\SportConfig;
use SportsPlanning\GameGenerator\Against as AgainstGameGenerator;
use SportsPlanning\GameGenerator\PlaceCounter;
use SportsPlanning\TestHelper\PlanningCreator;

class PlaceCounterTest extends TestCase
{
    use PlanningCreator;

    public function testSimple()
    {
        $planning = $this->createPlanning($this->createInputNew([5]));
        $place = $planning->getPoule(1)->getPlace(1);
        $placeCounter = new PlaceCounter($place);
        self::assertSame(1, $placeCounter->getNumber());
    }

    public function testCounter()
    {
        $planning = $this->createPlanning($this->createInputNew([5]));
        $place = $planning->getPoule(1)->getPlace(1);
        $placeCounter = new PlaceCounter($place);
        $placeCounter->increment();
        $placeCounter->increment();
        $placeCounter->increment();
        self::assertSame(3, $placeCounter->getCounter());
    }
}
