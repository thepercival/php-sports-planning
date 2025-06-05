<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Counters;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\CounterForDuoPlaceNr;
use SportsPlanning\Counters\CounterForPlaceNr;

final class CounterForDuoPlaceNrTest extends TestCase
{
    public function testCountSmallerThanZero(): void
    {
        self::expectException(\Exception::class);
        $duoPlaceNr = new DuoPlaceNr(1,2);
        new CounterForDuoPlaceNr($duoPlaceNr, -1);
    }
    public function testGetDuoPlaceNr(): void
    {
        $duoPlaceNr = new DuoPlaceNr(1,2);
        $counterForDuoPlaceNr = new CounterForDuoPlaceNr($duoPlaceNr);
        self::assertSame($duoPlaceNr, $counterForDuoPlaceNr->getDuoPlaceNr());
    }

    public function testIncrement(): void
    {
        $duoPlaceNr = new DuoPlaceNr(1,2);
        $counterForDuoPlaceNr = new CounterForDuoPlaceNr($duoPlaceNr);
        self::assertCount(1, $counterForDuoPlaceNr->increment());
    }

    public function testDecrement(): void
    {
        $duoPlaceNr = new DuoPlaceNr(1,2);
        $counterForDuoPlaceNr = new CounterForDuoPlaceNr($duoPlaceNr, 1);
        self::assertCount(0, $counterForDuoPlaceNr->decrement());
    }

    public function testDecrementException(): void
    {
        $duoPlaceNr = new DuoPlaceNr(1,2);
        $counterForDuoPlaceNr = new CounterForDuoPlaceNr($duoPlaceNr);
        self::expectException(\Exception::class);
        $counterForDuoPlaceNr->decrement();
    }

    public function testToString(): void
    {
        $duoPlaceNr = new DuoPlaceNr(1,2);
        $counterForDuoPlaceNr = new CounterForDuoPlaceNr($duoPlaceNr, 2);
        self::assertSame('1 & 2 2x', (string)$counterForDuoPlaceNr);
    }

}
