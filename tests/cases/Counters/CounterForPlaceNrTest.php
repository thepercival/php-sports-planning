<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Counters;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\CounterForDuoPlaceNr;
use SportsPlanning\Counters\CounterForPlaceNr;

final class CounterForPlaceNrTest extends TestCase
{

    public function testPlaceNrSmallerThanOne(): void
    {
        self::expectException(\Exception::class);
        new CounterForPlaceNr(0, 1);
    }

    public function testCountSmallerThanZero(): void
    {
        self::expectException(\Exception::class);
        new CounterForPlaceNr(1, -1);
    }

    public function testGetPlaceNr(): void
    {
        $counterForPlaceNr = new CounterForPlaceNr(1);
        self::assertSame(1, $counterForPlaceNr->getPlaceNr());
    }

    public function testIncrement(): void
    {
        $counterForPlaceNr = new CounterForPlaceNr(1);
        self::assertCount(1, $counterForPlaceNr->increment());
    }

    public function testDecrement(): void
    {
        $counterForPlaceNr = new CounterForPlaceNr(1, 1);
        self::assertCount(0, $counterForPlaceNr->decrement());
    }

    public function testDecrementException(): void
    {
        $counterForPlaceNr = new CounterForPlaceNr(1);
        self::expectException(\Exception::class);
        $counterForPlaceNr->decrement();
    }

    public function testToString(): void
    {
        $counterForPlaceNr = new CounterForPlaceNr(1, 2);
        self::assertSame('1 2x', (string)$counterForPlaceNr);
    }

    public function testGetIndex(): void
    {
        $counterForDuoPlaceNr = new CounterForDuoPlaceNr(new DuoPlaceNr(1, 2));
        self::assertSame('1 & 2', $counterForDuoPlaceNr->getIndex());
    }

}
