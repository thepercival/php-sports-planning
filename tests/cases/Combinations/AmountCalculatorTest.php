<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Combinations;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Combinations\AmountBoundary;
use SportsPlanning\Combinations\AmountCalculator;
use SportsPlanning\Combinations\AmountRange;
use SportsPlanning\Counters\CounterForAmount;

class AmountCalculatorTest extends TestCase
{
    public function testSmallerThanMinItOne(): void
    {
        $allowedAmountRange = new AmountRange(new AmountBoundary(1, 1),new AmountBoundary(1, 1));
        $amountCalculator = new AmountCalculator();
        $amountMap = [
            0 => new CounterForAmount(0, 2),
            1 => new CounterForAmount(1, 3),
            2 => new CounterForAmount(2, 4),
        ];
        self::assertSame(2, $amountCalculator->calculateSmallerThan($allowedAmountRange->min, $amountMap));
    }

    public function testSmallerThanMinItTwo(): void
    {
        $allowedAmountRange = new AmountRange(new AmountBoundary(2, 1),new AmountBoundary(3, 1));
        $amountCalculator = new AmountCalculator();
        $amountMap = [
            0 => new CounterForAmount(0, 2),
            1 => new CounterForAmount(1, 3),
            2 => new CounterForAmount(2, 4),
        ];
        // (2*(2-0)) + (3*(2-1))
        self::assertSame(7, $amountCalculator->calculateSmallerThan($allowedAmountRange->min, $amountMap));
    }

    public function testSmallerThanMinItThree(): void
    {
        $allowedAmountRange = new AmountRange(new AmountBoundary(2, 2),new AmountBoundary(3, 1));
        $amountCalculator = new AmountCalculator();
        $amountMap = [
            0 => new CounterForAmount(0, 2),
            1 => new CounterForAmount(1, 3),
            2 => new CounterForAmount(2, 4),
        ];
        // (2*(2-0)) + (3*(2-1))
        self::assertSame(7, $amountCalculator->calculateSmallerThan($allowedAmountRange->min, $amountMap));
    }

    public function testSmallerThanMinItFour(): void
    {
        $allowedAmountRange = new AmountRange(new AmountBoundary(2, 8),new AmountBoundary(3, 1 ));
        $amountCalculator = new AmountCalculator();
        $amountMap = [
            0 => new CounterForAmount(0, 2),
            1 => new CounterForAmount(1, 3),
            2 => new CounterForAmount(2, 2),
        ];
        // (2*(2-0)) + (3*(2-1)) + ((8-5+2)*2)
        self::assertSame(9, $amountCalculator->calculateSmallerThan($allowedAmountRange->min, $amountMap));
    }

//    public function testGreaterThanMaxItOne(): void
//    {
//        $allowedAmountRange = new AmountRange(new AmountBoundary(1, 1), new AmountBoundary(1, 1));
//        $amountCalculator = new AmountCalculator();
//        $amounts = [new CounterForAmount(1, 3)];
//        self::assertSame(3, $amountCalculator->calculateGreaterThan($allowedAmountRange->max, $amounts));
//    }

//    public function testGreaterThanMaxItTwo(): void
//    {
//        $allowedAmountRange = new AmountRange(new AmountBoundary(1, 1),new AmountBoundary(1, 1));
//        $amountCalculator = new AmountCalculator();
//        $amountMap = [
//            0 => new CounterForAmount(0, 2),
//            1 => new CounterForAmount(1, 3),
//            2 => new CounterForAmount(2, 4),
//        ];
//        // (4*(2-1)) + (4+3)
//        self::assertSame(11, $amountCalculator->calculateGreaterThan($allowedAmountRange->max, $amountMap));
//    }
//
//    public function testGreaterThanMaxItThree(): void
//    {
//        $allowedAmountRange = new AmountRange(new AmountBoundary(1,1), new AmountBoundary(5, 1));
//        $amountCalculator = new AmountCalculator();
//        $amountMap = [
//            0 => new CounterForAmount(0, 2),
//            1 => new CounterForAmount(1, 3),
//            2 => new CounterForAmount(2, 4),
//        ];
//        // (4*(2-1)) + (7-5)
//        self::assertSame(6, $amountCalculator->calculateGreaterThan($allowedAmountRange->max, $amountMap));
//    }

//    public function testGreaterThanMaxItFour(): void
//    {
//        $allowedAmountRange = new AmountRange(new AmountBoundary(1, 1),new AmountBoundary(1, 5));
//        $amountCalculator = new AmountCalculator();
//        $amountMap = [
//            0 => new CounterForAmount(0, 2),
//            1 => new CounterForAmount(1, 3),
//            2 => new CounterForAmount(2, 4),
//            3 => new CounterForAmount(3, 4),
//        ];
//        // (4*(3-1)) + (4*(2-1)) + (11-5)
//        self::assertSame(18, $amountCalculator->calculateGreaterThan($allowedAmountRange->max, $amountMap));
//    }
}