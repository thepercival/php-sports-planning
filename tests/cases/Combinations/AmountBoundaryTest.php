<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Combinations;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Combinations\AmountBoundary;
use SportsPlanning\Combinations\AmountRange;
use SportsPlanning\Counters\CounterForAmount;

final class AmountBoundaryTest extends TestCase
{
    public function testCountIs0(): void
    {
        self::expectException(\Exception::class);
        new AmountRange(new AmountBoundary(2, 1),new AmountBoundary(1, 0));
    }

    public function testConstructValid(): void
    {
        self::assertInstanceOf(AmountBoundary::class, new AmountBoundary(1, 1));
    }
}
