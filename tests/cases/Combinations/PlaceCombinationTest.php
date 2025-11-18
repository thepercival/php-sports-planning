<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Combinations;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Combinations\PlaceCombination;
use SportsPlanning\TestHelper\PlanningCreator;

final class PlaceCombinationTest extends TestCase
{
    use PlanningCreator;

    public function testCount(): void
    {
        $input = $this->createInput([4]);
        $places = array_values($input->getPoule(1)->getPlaces()->toArray());
        $placeCombination = new PlaceCombination($places);
        self::assertSame(4, $placeCombination->count());
    }

    public function testToString(): void
    {
        $input = $this->createInput([4]);
        $places = array_values($input->getPoule(1)->getPlaces()->toArray());
        $placeCombination = new PlaceCombination($places);
        self::assertSame(   '1.1 & 1.2 & 1.3 & 1.4'/*15*/, (string)$placeCombination);
    }
}
