<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Combinations;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Combinations\Amount;
use SportsPlanning\Combinations\AmountCalculator;
use SportsPlanning\Combinations\AmountRange;

class AmountCalculatorTest extends TestCase
{
    public function testSmallerThanMinItOne(): void
    {
        $allowedAmountRange = new AmountRange(new Amount(1), new Amount(1));
        $amountCalculator = new AmountCalculator($allowedAmountRange);
        $amountMap = [
            0 => new Amount(0, 2),
            1 => new Amount(1, 3),
            2 => new Amount(2, 4),
        ];
        self::assertSame(2, $amountCalculator->calculateCumulativeSmallerThanMinAmount($amountMap));
    }

    public function testSmallerThanMinItTwo(): void
    {
        $allowedAmountRange = new AmountRange(new Amount(2), new Amount(3));
        $amountCalculator = new AmountCalculator($allowedAmountRange);
        $amountMap = [
            0 => new Amount(0, 2),
            1 => new Amount(1, 3),
            2 => new Amount(2, 4),
        ];
        // (2*(2-0)) + (3*(2-1))
        self::assertSame(7, $amountCalculator->calculateCumulativeSmallerThanMinAmount($amountMap));
    }

    public function testSmallerThanMinItThree(): void
    {
        $allowedAmountRange = new AmountRange(new Amount(2, 2), new Amount(3));
        $amountCalculator = new AmountCalculator($allowedAmountRange);
        $amountMap = [
            0 => new Amount(0, 2),
            1 => new Amount(1, 3),
            2 => new Amount(2, 4),
        ];
        // (2*(2-0)) + (3*(2-1))
        self::assertSame(7, $amountCalculator->calculateCumulativeSmallerThanMinAmount($amountMap));
    }

    public function testSmallerThanMinItFour(): void
    {
        $allowedAmountRange = new AmountRange(new Amount(2, 8), new Amount(3));
        $amountCalculator = new AmountCalculator($allowedAmountRange);
        $amountMap = [
            0 => new Amount(0, 2),
            1 => new Amount(1, 3),
            2 => new Amount(2, 2),
        ];
        // (2*(2-0)) + (3*(2-1)) + ((8-5+2)*2)
        self::assertSame(9, $amountCalculator->calculateCumulativeSmallerThanMinAmount($amountMap));
    }

    public function testGreaterThanMaxItOne(): void
    {
        $allowedAmountRange = new AmountRange(new Amount(1), new Amount(1));
        $amountCalculator = new AmountCalculator($allowedAmountRange);
        $amountMap = [
            1 => new Amount(1, 3)
        ];
        self::assertSame(3, $amountCalculator->calculateCumulativeGreaterThanMaxAmount($amountMap));
    }

    public function testGreaterThanMaxItTwo(): void
    {
        $allowedAmountRange = new AmountRange(new Amount(1), new Amount(1));
        $amountCalculator = new AmountCalculator($allowedAmountRange);
        $amountMap = [
            0 => new Amount(0, 2),
            1 => new Amount(1, 3),
            2 => new Amount(2, 4),
        ];
        // (4*(2-1)) + (4+3)
        self::assertSame(11, $amountCalculator->calculateCumulativeGreaterThanMaxAmount($amountMap));
    }

    public function testGreaterThanMaxItThree(): void
    {
        $allowedAmountRange = new AmountRange(new Amount(1), new Amount(1, 5));
        $amountCalculator = new AmountCalculator($allowedAmountRange);
        $amountMap = [
            0 => new Amount(0, 2),
            1 => new Amount(1, 3),
            2 => new Amount(2, 4),
        ];
        // (4*(2-1)) + (7-5)
        self::assertSame(6, $amountCalculator->calculateCumulativeGreaterThanMaxAmount($amountMap));
    }

    public function testGreaterThanMaxItFour(): void
    {
        $allowedAmountRange = new AmountRange(new Amount(1), new Amount(1, 5));
        $amountCalculator = new AmountCalculator($allowedAmountRange);
        $amountMap = [
            0 => new Amount(0, 2),
            1 => new Amount(1, 3),
            2 => new Amount(2, 4),
            3 => new Amount(3, 4),
        ];
        // (4*(3-1)) + (4*(2-1)) + (11-5)
        self::assertSame(18, $amountCalculator->calculateCumulativeGreaterThanMaxAmount($amountMap));
    }
}