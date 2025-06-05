<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Combinations;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Combinations\AmountBoundary;
use SportsPlanning\Combinations\AmountRange;
use SportsPlanning\Counters\CounterForAmount;

final class AmountRangeTest extends TestCase
{

    public function testAmountMinimumAmountSmallerThanMaximumAmountItOne(): void
    {
        self::expectException(\Exception::class);
        new AmountRange(new AmountBoundary(2, 1),new AmountBoundary(1, 1));
    }

    public function testAmountMinimumAmountSmallerThanMaximumAmountItTwo(): void
    {
        self::expectException(\Exception::class);
        new AmountRange(new AmountBoundary(2, 2),new AmountBoundary(2, 1));
    }

    public function testAmountDifferenceItOne(): void
    {
        $amountRange = new AmountRange(new AmountBoundary(1, 1),new AmountBoundary(1, 1));
        self::assertSame(0, $amountRange->getAmountDifference());
    }

    public function testAmountDifferenceItTwo(): void
    {
        $amountRange = new AmountRange(new AmountBoundary(1, 1),new AmountBoundary(2, 1));
        self::assertSame(1, $amountRange->getAmountDifference());
    }


    public function testToString(): void
    {
        $amountRange = new AmountRange(new AmountBoundary(1, 2),new AmountBoundary(2, 3));
        self::assertSame('[1.2 -> 2.3]', (string)$amountRange);
    }

}
