<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Counters;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Counters\CounterForAmount;

class CounterForAmountTest extends TestCase
{

    public function testIncrement(): void
    {
        $counterForPlaceNr = new CounterForAmount(1);
        self::assertCount(1, $counterForPlaceNr->increment());
    }

    public function testDecrement(): void
    {
        $counterForPlaceNr = new CounterForAmount(1, 1);
        self::assertCount(0, $counterForPlaceNr->decrement());
    }

    public function testDecrementException(): void
    {
        $counterForPlaceNr = new CounterForAmount(1);
        self::expectException(\Exception::class);
        $counterForPlaceNr->decrement();
    }
}
