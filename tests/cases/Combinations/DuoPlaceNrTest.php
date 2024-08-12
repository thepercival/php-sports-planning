<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Combinations;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\TestHelper\PlanningCreator;

class DuoPlaceNrTest extends TestCase
{
    use PlanningCreator;

    public function testCount(): void
    {
        $duoPlaceNr = new DuoPlaceNr(1, 2);
        self::assertCount(2, $duoPlaceNr->getPlaceNrs());
    }

    public function testToString(): void
    {
        $duoPlaceNr = new DuoPlaceNr(1, 2);
        self::assertSame(   '1 vs 2', (string)$duoPlaceNr);
    }
}
