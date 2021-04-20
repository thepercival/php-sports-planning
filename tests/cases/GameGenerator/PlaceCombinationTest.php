<?php

namespace SportsPlanning\Tests\GameGenerator;

use PHPUnit\Framework\TestCase;
use SportsPlanning\GameGenerator\PlaceCombination;
use SportsPlanning\TestHelper\PlanningCreator;

class PlaceCombinationTest extends TestCase
{
    use PlanningCreator;

    public function testCount(): void
    {
        $planning = $this->createPlanning($this->createInput([4]));
        $places = array_values($planning->getInput()->getPoule(1)->getPlaces()->toArray());
        $placeCombination = new PlaceCombination($places);
        self::assertSame(4, $placeCombination->count());
    }

    public function testNumber(): void
    {
        $planning = $this->createPlanning($this->createInput([4]));
        $places = array_values($planning->getInput()->getPoule(1)->getPlaces()->toArray());
        $placeCombination = new PlaceCombination($places);
        self::assertSame(15, $placeCombination->getNumber());
    }
}
