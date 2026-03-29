<?php

declare(strict_types=1);

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Combinations\PlaceNrCounter;

final class PlaceCounterTest extends TestCase
{

    public function testSimple(): void
    {
        $placeNr = 1;
        $placeCounter = new PlaceNrCounter($placeNr);
        self::assertSame(1, $placeCounter->getPlaceNr());
    }

    public function testCounter(): void
    {
        $placeNr = 1;
        $placeCounter = new PlaceNrCounter($placeNr);
        $placeCounter->increment();
        $placeCounter->increment();
        $placeCounter->increment();
        self::assertCount(3, $placeCounter);
    }
}
