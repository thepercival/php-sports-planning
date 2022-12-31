<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Combinations;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Combinations\PlaceCombination;
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

    public function testToString(): void
    {
        $planning = $this->createPlanning($this->createInput([4]));
        $places = array_values($planning->getInput()->getPoule(1)->getPlaces()->toArray());
        $placeCombination = new PlaceCombination($places);
        self::assertSame(   '1.1 & 1.2 & 1.3 & 1.4'/*15*/, (string)$placeCombination);
    }
}
