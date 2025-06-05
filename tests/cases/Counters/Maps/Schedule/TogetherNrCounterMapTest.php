<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Counters\Maps\Schedule;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\Maps\Schedule\AgainstNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\TogetherNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

final class TogetherNrCounterMapTest extends TestCase
{
    public function testAddHomeAways(): void
    {
        $togetherNrCounterMap = new TogetherNrCounterMap(4);
        $togetherNrCounterMap->addHomeAways(
            [
                new OneVsOneHomeAway(2,3),
                new OneVsTwoHomeAway(2, new DuoPlaceNr(1,4)),
                new TwoVsTwoHomeAway(new DuoPlaceNr(1,3), new DuoPlaceNr(2,4))
            ]
        );
        self::assertSame(2, $togetherNrCounterMap->count(new DuoPlaceNr(1, 2)));
        self::assertSame(1, $togetherNrCounterMap->count(new DuoPlaceNr(1, 3)));
        self::assertSame(2, $togetherNrCounterMap->count(new DuoPlaceNr(1, 4)));
        self::assertSame(2, $togetherNrCounterMap->count(new DuoPlaceNr(2, 3)));
        self::assertSame(2, $togetherNrCounterMap->count(new DuoPlaceNr(2, 4)));
        self::assertSame(1, $togetherNrCounterMap->count(new DuoPlaceNr(3, 4)));

    }
}
