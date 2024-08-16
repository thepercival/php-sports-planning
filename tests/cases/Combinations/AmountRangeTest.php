<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Combinations;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Combinations\Amount;
use SportsPlanning\Combinations\AmountRange;

class AmountRangeTest extends TestCase
{

    public function testAmountMinimumAmountSmallerThanMaximumAmount(): void
    {
        self::expectException(\Exception::class);
        new AmountRange(new Amount(2), new Amount(1));
    }

    public function testAmountDifferenceItOne(): void
    {
        $amountRange = new AmountRange(new Amount(1), new Amount(1));
        self::assertSame(0, $amountRange->getAmountDifference());
    }

    public function testAmountDifferenceItTwo(): void
    {
        $amountRange = new AmountRange(new Amount(1), new Amount(2));
        self::assertSame(1, $amountRange->getAmountDifference());
    }


    public function testToString(): void
    {
        $amountRange = new AmountRange(new Amount(1, 2), new Amount(2, 0));
        self::assertSame('[1.2 -> 2.0]', (string)$amountRange);
    }

}
