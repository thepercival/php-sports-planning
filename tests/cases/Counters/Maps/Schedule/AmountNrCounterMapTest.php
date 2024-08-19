<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Counters\Maps\Schedule;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\Maps\Schedule\AmountNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;

class AmountNrCounterMapTest extends TestCase
{

    public function testWithNrOfPlaces(): void
    {
        $amountNrCounterMap = new AmountNrCounterMap(2);
        $amountNrCounterMap->addHomeAway(new OneVsOneHomeAway(new DuoPlaceNr(1, 2)));
        self::assertSame(1, $amountNrCounterMap->count(1));
        self::assertSame(1, $amountNrCounterMap->count(2));
    }
}
