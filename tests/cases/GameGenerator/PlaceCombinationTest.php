<?php

namespace SportsPlanning\Tests\GameGenerator;

use PHPUnit\Framework\TestCase;
use SportsHelpers\GameMode;
use SportsHelpers\SportBase;
use SportsHelpers\SportConfig;
use SportsPlanning\GameGenerator\Against as AgainstGameGenerator;
use SportsPlanning\GameGenerator\PlaceCombination;
use SportsPlanning\GameGenerator\PlaceCounter;
use SportsPlanning\TestHelper\PlanningCreator;

class PlaceCombinationTest extends TestCase
{
    use PlanningCreator;

    public function testCount()
    {
        $planning = $this->createPlanning($this->createInputNew([4]));
        $placeCombination = new PlaceCombination($planning->getPoule(1)->getPlaces()->toArray());
        self::assertSame(4, $placeCombination->count());
    }

    public function testNumber()
    {
        $planning = $this->createPlanning($this->createInputNew([4]));
        $placeCombination = new PlaceCombination($planning->getPoule(1)->getPlaces()->toArray());
        self::assertSame(15, $placeCombination->getNumber());
    }
}
