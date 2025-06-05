<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Counters\Maps;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\AmountBoundary;
use SportsPlanning\Combinations\AmountRange;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\Maps\RangedDuoPlaceNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\AgainstNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\WithNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;

final class RangedDuoPlaceNrCounterMapTest extends TestCase
{
    public function testGetAllowedRange(): void
    {
        $againstNrCounterMap = new AgainstNrCounterMap(2);
        $allowedRange = new AmountRange(new AmountBoundary(2,3),new AmountBoundary(4,3));
        $rangedAgainstNrCounterMap = new RangedDuoPlaceNrCounterMap($againstNrCounterMap, $allowedRange);

        self::assertSame($allowedRange, $rangedAgainstNrCounterMap->allowedRange);
    }

    public function testAddHomeAway(): void
    {
        $againstNrCounterMap = new AgainstNrCounterMap(2);
        $allowedRange = new AmountRange(new AmountBoundary(2,3),new AmountBoundary(4,3));
        $rangedAgainstNrCounterMap = new RangedDuoPlaceNrCounterMap($againstNrCounterMap, $allowedRange);

        $rangedAgainstNrCounterMap->addHomeAway(new OneVsOneHomeAway(1,2));
        self::assertSame(1, $rangedAgainstNrCounterMap->count(new DuoPlaceNr(1,2)));
    }

//    public function testGetPlaceNrsGreaterThanMaximimum(): void
//    {
////        $counterForPlaceNrOne = new CounterForPlaceNr(1,1);
////        $counterForPlaceNrTwo = new CounterForPlaceNr(2, 2);
////        $placeNrCounterMap = new PlaceNrCounterMapAbstract(
////            [
////                $counterForPlaceNrOne->getPlaceNr() => $counterForPlaceNrOne,
////                $counterForPlaceNrTwo->getPlaceNr() => $counterForPlaceNrTwo
////            ]
////        );
//
//        $placeNrCounterMap = new AmountNrCounterMap(2);
////        $placeNrCounterMap->addCounters(
////            [
////                new CounterForPlaceNr(1,1),
////                new CounterForPlaceNr(2, 2),
////                new CounterForPlaceNr(3, 2),
////                new CounterForPlaceNr(4, 2),
////                new CounterForPlaceNr(5, 2)
////            ]
////        );
//
//        $allowedRange = new AmountRange(0,0,1,0));
//        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($placeNrCounterMap, $allowedRange);
//
//        self::assertCount(1, $rangedAmountNrCounterMap->getPlaceNrsGreaterThanMaximum());
//    }
//
//    public function testGetPlaceNrsSmallerThanMinimum(): void
//    {
////        $counterForPlaceNrOne = new CounterForPlaceNr(1,0);
////        $counterForPlaceNrTwo = new CounterForPlaceNr(2, 2);
////        $placeNrCounterMap = new PlaceNrCounterMapAbstract(
////            [
////                $counterForPlaceNrOne->getPlaceNr() => $counterForPlaceNrOne,
////                $counterForPlaceNrTwo->getPlaceNr() => $counterForPlaceNrTwo
////            ]
////        );
//
//        $placeNrCounterMap = new AmountNrCounterMap(2);
////        $placeNrCounterMap->addCounters(
////            [
////                new CounterForPlaceNr(1,1),
////                new CounterForPlaceNr(2, 2),
////                new CounterForPlaceNr(3, 2),
////                new CounterForPlaceNr(4, 2),
////                new CounterForPlaceNr(5, 2)
////            ]
////        );
//
//        $allowedRange = new AmountRange(1,0,2,0));
//        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($placeNrCounterMap, $allowedRange);
//
//        self::assertCount(1, $rangedAmountNrCounterMap->getPlaceNrsSmallerThanMinimum());
//    }

//    public function testGetNrOfEntitiesForAmount(): void
//    {
////        $counterForPlaceNrOne = new CounterForPlaceNr(1,1);
////        $counterForPlaceNrTwo = new CounterForPlaceNr(2, 1);
////        $placeNrCounterMap = new PlaceNrCounterMapAbstract(
////            [
////                $counterForPlaceNrOne->getPlaceNr() => $counterForPlaceNrOne,
////                $counterForPlaceNrTwo->getPlaceNr() => $counterForPlaceNrTwo
////            ]
////        );
//
//        $placeNrCounterMap = new AmountNrCounterMap(2);
////        $placeNrCounterMap->addCounters(
////            [
////                new CounterForPlaceNr(1,1),
////                new CounterForPlaceNr(2, 2),
////                new CounterForPlaceNr(3, 2),
////                new CounterForPlaceNr(4, 2),
////                new CounterForPlaceNr(5, 2)
////            ]
////        );
//        $allowedRange = new AmountRange(1,0,2,0));
//        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($placeNrCounterMap, $allowedRange);
//
//        self::assertSame(2, $rangedAmountNrCounterMap->getNrOfEntitiesForAmount(1));
//    }

    public function testDuoPlaceNrCountersCanBeEqualOrGreaterThanMinimum_ItOne(): void
    {
        $againstNrCounterMap = new AgainstNrCounterMap(2);

        $allowedRange = new AmountRange(new AmountBoundary(1,1),new AmountBoundary(2,2));
        $rangedAgainstNrCounterMap = new RangedDuoPlaceNrCounterMap($againstNrCounterMap, $allowedRange);

        self::assertTrue($rangedAgainstNrCounterMap->allDuoPlaceNrCountersCanBeEqualOrGreaterThanMinimum(3));
    }



//    public function testMinimumCanBeReachedItTwo(): void
//    {
//        $amountNrCounterMap = new AmountNrCounterMap(5);
//        $amountNrCounterMap->incrementPlaceNr(1);
//        $amountNrCounterMap->incrementPlaceNr(2);
//        $amountNrCounterMap->incrementPlaceNr(3);
//        $amountNrCounterMap->incrementPlaceNr(4);
//        $amountNrCounterMap->incrementPlaceNr(5);
//        $allowedRange = new AmountRange(1,6,3,0));
//        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($amountNrCounterMap, $allowedRange);
//
//        self::assertTrue($rangedAmountNrCounterMap->minimumCanBeReached(0));
//    }


    public function testMinimumCanBeReachedItTwo(): void
    {
        $againstNrCounterMap = new AgainstNrCounterMap(5);
        $againstNrCounterMap->addHomeAways(
            [
                new OneVsOneHomeAway(1,2),
                new OneVsOneHomeAway(3,4),
                new OneVsOneHomeAway(5,1),
                new OneVsOneHomeAway(2,3),
                new OneVsOneHomeAway(4,5),
            ]
        );
        $allowedRange = new AmountRange(new AmountBoundary(2,4),new AmountBoundary(3,5));
        $rangedAgainstNrCounterMap = new RangedDuoPlaceNrCounterMap($againstNrCounterMap, $allowedRange);

        self::assertFalse($rangedAgainstNrCounterMap->allDuoPlaceNrCountersCanBeEqualOrGreaterThanMinimum(3));
    }

    public function testAllDuoPlaceNrCountersCanBeSmallerOrEqualToMaximumItOne(): void
    {
        $againstNrCounterMap = new AgainstNrCounterMap(5);
        $againstNrCounterMap->addHomeAways(
            [
                new OneVsOneHomeAway(1,2),
                new OneVsOneHomeAway(1,3),
                new OneVsOneHomeAway(1,5),
                new OneVsOneHomeAway(2,3),
                new OneVsOneHomeAway(2,5),
                new OneVsOneHomeAway(3,4),
                new OneVsOneHomeAway(4,5),
            ]
        );

        $allowedRange = new AmountRange(new AmountBoundary(0,1), new AmountBoundary(1,3));
        $rangedAgainstNrCounterMap = new RangedDuoPlaceNrCounterMap($againstNrCounterMap, $allowedRange);
        self::assertFalse($rangedAgainstNrCounterMap->allDuoPlaceNrCountersCanBeSmallerOrEqualToMaximum(1));

        $allowedRange = new AmountRange(new AmountBoundary(0,1),new AmountBoundary(1,7));
        $rangedAgainstNrCounterMap = new RangedDuoPlaceNrCounterMap($againstNrCounterMap, $allowedRange);
        self::assertTrue($rangedAgainstNrCounterMap->allDuoPlaceNrCountersCanBeSmallerOrEqualToMaximum(0));
    }

//    public function testAboveMaxmimumItTwo(): void
//    {
//        $amountNrCounterMap = new AmountNrCounterMap(2);
//        $amountNrCounterMap->incrementPlaceNr(1);
//        $amountNrCounterMap->incrementPlaceNr(2);
//
//        $allowedRange = new AmountRange(1,0,2,0));
//        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($amountNrCounterMap, $allowedRange);
//
//        self::assertFalse($rangedAmountNrCounterMap->aboveMaximum(1));
//    }
//
//    public function testAboveMaxmimumItThree(): void
//    {
//        $amountNrCounterMap = new AmountNrCounterMap(2);
//        $amountNrCounterMap->incrementPlaceNr(1);
//        $amountNrCounterMap->incrementPlaceNr(2);
//        $amountNrCounterMap->incrementPlaceNr(2);
//
//        $allowedRange = new AmountRange(1,0,2,0));
//        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($amountNrCounterMap, $allowedRange);
//
//        self::assertFalse($rangedAmountNrCounterMap->aboveMaximum(1));
//    }
//
//    public function testwithinRange(): void
//    {
//        $amountNrCounterMap = new AmountNrCounterMap(2);
//
//        $allowedRange = new AmountRange(1,2,2,0));
//        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($amountNrCounterMap, $allowedRange);
//
//        self::assertTrue($rangedAmountNrCounterMap->withinRange(2));
//    }

//
    public function testAddHomeAwayItTwo(): void
    {
        $withNrCounterMap = new WithNrCounterMap(5);
        $withNrCounterMap->addHomeAways(
            [
                new OneVsOneHomeAway(1,2)
            ]
        );
        self::assertSame(0, $withNrCounterMap->count(new DuoPlaceNr(1,2)));
    }

    public function testAddHomeAwayItThree(): void
    {
        $withNrCounterMap = new WithNrCounterMap(5);
        $allowedRange = new AmountRange(new AmountBoundary(2,3), new AmountBoundary(4,3));
        $rangedWithNrCounterMap = new RangedDuoPlaceNrCounterMap($withNrCounterMap, $allowedRange);
        $rangedWithNrCounterMap->addHomeAway(new OneVsOneHomeAway(1,2));
        self::assertSame(0, $rangedWithNrCounterMap->count(new DuoPlaceNr(1,2)));
    }

    public function testCloneAsSideNrCounterMap(): void
    {
        $withNrCounterMap = new WithNrCounterMap(4);
        $allowedRange = new AmountRange(new AmountBoundary(2,3), new AmountBoundary(4,3));
        $rangedWithNrCounterMap = new RangedDuoPlaceNrCounterMap($withNrCounterMap, $allowedRange);

        $rangedWithNrCounterMapCloned = clone $rangedWithNrCounterMap;
        self::assertInstanceOf(RangedDuoPlaceNrCounterMap::class, $rangedWithNrCounterMapCloned);
    }
//
//    public function testCloneAsSideNrCounterMapException(): void
//    {
//        $sideNrCounterMap = new SideNrCounterMap(Side::Home, 4);
//        $allowedRange = new AmountRange(2,3,4,3));
//        $rangedSideNrCounterMap = new RangedPlaceNrCounterMap($sideNrCounterMap, $allowedRange);
//
//        $cloned = $rangedSideNrCounterMap->cloneAsSideNrCounterMap();
//        self::assertInstanceOf(SideNrCounterMap::class, $cloned);
//    }
//
//    public function testOutput(): void
//    {
//        $placeNrCounterMap = new AmountNrCounterMap(5);
//
//        $allowedRange = new AmountRange(2,3,4,3));
//        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($placeNrCounterMap, $allowedRange);
//
//        $logger = $this->createLogger();
//
//        self::expectNotToPerformAssertions();
//        $rangedAmountNrCounterMap->output(
//            $logger, 'prefix ', ' header'
//        );
//    }

    protected function createLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
//        $handler = new StreamHandler('php://stdout', Logger::INFO);
//        $logger->pushHandler($handler);
        return $logger;
    }
}
