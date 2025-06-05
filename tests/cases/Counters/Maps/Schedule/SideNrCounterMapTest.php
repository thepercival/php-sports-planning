<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Counters\Maps\Schedule;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\Maps\Schedule\AmountNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\SideNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;
use SportsPlanning\HomeAways\TwoVsTwoHomeAway;

final class SideNrCounterMapTest extends TestCase
{

    public function testWithNrOfPlaces(): void
    {
        $homeNrCounterMap = new SideNrCounterMap(AgainstSide::Home, 5);
        $homeNrCounterMap->addHomeAway(new OneVsOneHomeAway(1, 2));
        self::assertSame(1, $homeNrCounterMap->count(1));
        self::assertSame(0, $homeNrCounterMap->count(2));
    }

    public function testWithoutNrOfPlacesAndAddHomeAways(): void
    {
        $awayNrCounterMap = new SideNrCounterMap(AgainstSide::Away, 2);
        $awayNrCounterMap->addHomeAways(
            [new OneVsOneHomeAway(1, 2)]
        );
        self::assertSame(1, $awayNrCounterMap->count(2));
    }

    public function testAddOneVsTwoHomeAwayHome(): void
    {
        $homeNrCounterMap = new SideNrCounterMap(AgainstSide::Home, 5);
        $homeNrCounterMap->addHomeAway(new OneVsTwoHomeAway(1, new DuoPlaceNr(2, 3)));
        self::assertSame(1, $homeNrCounterMap->count(1));
        self::assertSame(0, $homeNrCounterMap->count(2));
        self::assertSame(0, $homeNrCounterMap->count(3));
    }

    public function testAddOneVsTwoHomeAwayAway(): void
    {
        $homeNrCounterMap = new SideNrCounterMap(AgainstSide::Away, 5);
        $homeNrCounterMap->addHomeAway(new OneVsTwoHomeAway(1, new DuoPlaceNr(2, 3)));
        self::assertSame(0, $homeNrCounterMap->count(1));
        self::assertSame(1, $homeNrCounterMap->count(2));
        self::assertSame(1, $homeNrCounterMap->count(3));
    }

    public function testAddYwoVsTwoHomeAway(): void
    {
        $homeNrCounterMap = new SideNrCounterMap(AgainstSide::Home, 5);
        $homeNrCounterMap->addHomeAway(
            new TwoVsTwoHomeAway(
                new DuoPlaceNr(1, 4), new DuoPlaceNr(2, 3)));
        self::assertSame(1, $homeNrCounterMap->count(1));
        self::assertSame(0, $homeNrCounterMap->count(2));
        self::assertSame(0, $homeNrCounterMap->count(3));
        self::assertSame(1, $homeNrCounterMap->count(4));
        self::assertSame(0, $homeNrCounterMap->count(5));
    }
}
