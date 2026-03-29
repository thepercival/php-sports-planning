<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Combinations;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Combinations\PlaceNrCombination;
use SportsPlanning\TestHelper\PlanningCreator;

final class PlaceNrCombinationTest extends TestCase
{
    use PlanningCreator;

    public function testCount(): void
    {
        $input = $this->createInput([4]);
        $placesNrs = $input->getPoule(1)->getPlaceNrs();
        $placeNrCombination = new PlaceNrCombination($placesNrs);
        self::assertSame(4, $placeNrCombination->count());
    }

    public function testToString(): void
    {
        $input = $this->createInput([4]);
        $placesNrs = $input->getPoule(1)->getPlaceNrs();
        $placeNrCombination = new PlaceNrCombination($placesNrs);
        // self::assertSame(   '1.1 & 1.2 & 1.3 & 1.4'/*15*/, (string)$placeNrCombination);
        self::assertSame(   '1 & 2 & 3 & 4'/*15*/, (string)$placeNrCombination);
    }
}
