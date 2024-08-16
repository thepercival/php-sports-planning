<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Combinations;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Combinations\Amount;

class AmountTest extends TestCase
{

    public function testAmountSmallerThanOne(): void
    {
        self::expectException(\Exception::class);
        new Amount(-1, 0);
    }

    public function testNrOfEntitiesSmallerThanOne(): void
    {
        self::expectException(\Exception::class);
        new Amount(0, -1);
    }


    public function testToString(): void
    {
        $amount = new Amount(1, 2);
        self::assertSame('1.2', (string)$amount);
    }

}
