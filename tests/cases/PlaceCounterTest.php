<?php

declare(strict_types=1);

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Counters\CounterForPlaceNr;

final class PlaceCounterTest extends TestCase
{

    public function testSimple(): void
    {
        $placeCounter = new CounterForPlaceNr(1);
        self::assertSame(1, $placeCounter->getPlaceNr());
    }

    public function testCounter(): void
    {
        $placeNrCounter = new CounterForPlaceNr(1);
        self::assertCount(3, $placeNrCounter->increment()->increment()->increment());
    }
}
