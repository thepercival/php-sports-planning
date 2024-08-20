<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Combinations;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Combinations\AmountRange;
use SportsPlanning\Counters\CounterForAmount;

class AmountRangeTest extends TestCase
{

    public function testAmountMinimumAmountSmallerThanMaximumAmount(): void
    {
        self::expectException(\Exception::class);
        new AmountRange(new CounterForAmount(2), new CounterForAmount(1));
    }

    public function testAmountDifferenceItOne(): void
    {
        $amountRange = new AmountRange(new CounterForAmount(1), new CounterForAmount(1));
        self::assertSame(0, $amountRange->getAmountDifference());
    }

    public function testAmountDifferenceItTwo(): void
    {
        $amountRange = new AmountRange(new CounterForAmount(1), new CounterForAmount(2));
        self::assertSame(1, $amountRange->getAmountDifference());
    }


    public function testToString(): void
    {
        $amountRange = new AmountRange(new CounterForAmount(1, 2), new CounterForAmount(2, 0));
        self::assertSame('[1.2 -> 2.0]', (string)$amountRange);
    }

}
