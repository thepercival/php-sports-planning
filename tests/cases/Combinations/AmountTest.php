<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Combinations;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Counters\CounterForAmount;

class AmountTest extends TestCase
{

    public function testAmountSmallerThanOne(): void
    {
        self::expectException(\Exception::class);
        new CounterForAmount(-1, 0);
    }

    public function testNrOfEntitiesSmallerThanOne(): void
    {
        self::expectException(\Exception::class);
        new CounterForAmount(0, -1);
    }


    public function testToString(): void
    {
        $amount = new CounterForAmount(1, 2);
        self::assertSame('1.2', (string)$amount);
    }

}
