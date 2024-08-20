<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Counters\Maps\Schedule;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side;
use SportsHelpers\SportRange;
use SportsPlanning\Combinations\AmountCounter;
use SportsPlanning\Combinations\AmountRange;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\CounterForAmount;
use SportsPlanning\Counters\CounterForDuoPlaceNr;
use SportsPlanning\Counters\CounterForPlaceNr;
use SportsPlanning\Counters\Maps\PlaceNrCounterMapAbstract;
use SportsPlanning\Counters\Maps\Schedule\AmountNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\RangedPlaceNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\SideNrCounterMap;
use SportsPlanning\Counters\Reports\RangedPlaceNrCountersReport;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\Counters\Reports\PlaceNrCountersPerAmountReport;
class RangedPlaceNrCounterMapTest extends TestCase
{
    public function testGetAllowedRange(): void
    {
        $amountNrCounterMap = new AmountNrCounterMap(1);
        $allowedRange = new AmountRange(new CounterForAmount(2,3), new CounterForAmount(4,3));
        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($amountNrCounterMap, $allowedRange);

        self::assertSame($allowedRange, $rangedAmountNrCounterMap->getAllowedRange());
    }

    public function testAddHomeAway(): void
    {
        $amountNrCounterMap = new AmountNrCounterMap(2);
        $allowedRange = new AmountRange(new CounterForAmount(2,3), new CounterForAmount(4,3));
        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($amountNrCounterMap, $allowedRange);

        $rangedAmountNrCounterMap->addHomeAway(new OneVsOneHomeAway(new DuoPlaceNr(1,2)));
        self::assertSame(1, $rangedAmountNrCounterMap->count(1));
        self::assertSame(1, $rangedAmountNrCounterMap->count(2));
    }

    public function testIncrementPlaceNr(): void
    {
        $amountNrCounterMap = new AmountNrCounterMap(1);
        $allowedRange = new AmountRange(new CounterForAmount(2,3), new CounterForAmount(4,3));
        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($amountNrCounterMap, $allowedRange);

        $rangedAmountNrCounterMap->incrementPlaceNr(1);
        self::assertSame(1, $rangedAmountNrCounterMap->count(1));
    }

    public function testDecrementPlaceNr(): void
    {
        $amountNrCounterMap = new AmountNrCounterMap(1);
        $allowedRange = new AmountRange(new CounterForAmount(2,3), new CounterForAmount(4,3));
        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($amountNrCounterMap, $allowedRange);

        $rangedAmountNrCounterMap->incrementPlaceNr(1);
        $rangedAmountNrCounterMap->decrementPlaceNr(1);
        self::assertSame(0, $rangedAmountNrCounterMap->count(1));
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
//        $allowedRange = new AmountRange(new CounterForAmount(0,0), new CounterForAmount(1,0));
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
//        $allowedRange = new AmountRange(new CounterForAmount(1,0), new CounterForAmount(2,0));
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
//        $allowedRange = new AmountRange(new CounterForAmount(1,0), new CounterForAmount(2,0));
//        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($placeNrCounterMap, $allowedRange);
//
//        self::assertSame(2, $rangedAmountNrCounterMap->getNrOfEntitiesForAmount(1));
//    }

    public function testMinimumCanBeReachedItOne(): void
    {
        $amountNrCounterMap = new AmountNrCounterMap(2);

        $allowedRange = new AmountRange(new CounterForAmount(1,0), new CounterForAmount(2,0));
        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($amountNrCounterMap, $allowedRange);

        self::assertTrue($rangedAmountNrCounterMap->minimumCanBeReached(3));
    }



//    public function testMinimumCanBeReachedItTwo(): void
//    {
//        $amountNrCounterMap = new AmountNrCounterMap(5);
//        $amountNrCounterMap->incrementPlaceNr(1);
//        $amountNrCounterMap->incrementPlaceNr(2);
//        $amountNrCounterMap->incrementPlaceNr(3);
//        $amountNrCounterMap->incrementPlaceNr(4);
//        $amountNrCounterMap->incrementPlaceNr(5);
//        $allowedRange = new AmountRange(new CounterForAmount(1,6), new CounterForAmount(3,0));
//        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($amountNrCounterMap, $allowedRange);
//
//        self::assertTrue($rangedAmountNrCounterMap->minimumCanBeReached(0));
//    }


    public function testMinimumCanBeReachedItTwo(): void
    {
        $amountNrCounterMap = new AmountNrCounterMap(5);
        $amountNrCounterMap->incrementPlaceNr(1);
        $amountNrCounterMap->incrementPlaceNr(2);
        $amountNrCounterMap->incrementPlaceNr(3);
        $amountNrCounterMap->incrementPlaceNr(4);
        $amountNrCounterMap->incrementPlaceNr(5);
        $allowedRange = new AmountRange(new CounterForAmount(2,4), new CounterForAmount(3,0));
        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($amountNrCounterMap, $allowedRange);

        self::assertFalse($rangedAmountNrCounterMap->minimumCanBeReached(3));
    }

    public function testAboveMaxmimumItOne(): void
    {
        $amountNrCounterMap = new AmountNrCounterMap(2);
        $amountNrCounterMap->incrementPlaceNr(1);
        $amountNrCounterMap->incrementPlaceNr(1);
        $amountNrCounterMap->incrementPlaceNr(2);
        $amountNrCounterMap->incrementPlaceNr(2);

        $allowedRange = new AmountRange(new CounterForAmount(1,0), new CounterForAmount(2,0));
        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($amountNrCounterMap, $allowedRange);

        self::assertTrue($rangedAmountNrCounterMap->aboveMaximum(1));
    }

    public function testAboveMaxmimumItTwo(): void
    {
        $amountNrCounterMap = new AmountNrCounterMap(2);
        $amountNrCounterMap->incrementPlaceNr(1);
        $amountNrCounterMap->incrementPlaceNr(2);

        $allowedRange = new AmountRange(new CounterForAmount(1,0), new CounterForAmount(2,0));
        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($amountNrCounterMap, $allowedRange);

        self::assertFalse($rangedAmountNrCounterMap->aboveMaximum(1));
    }

    public function testAboveMaxmimumItThree(): void
    {
        $amountNrCounterMap = new AmountNrCounterMap(2);
        $amountNrCounterMap->incrementPlaceNr(1);
        $amountNrCounterMap->incrementPlaceNr(2);
        $amountNrCounterMap->incrementPlaceNr(2);

        $allowedRange = new AmountRange(new CounterForAmount(1,0), new CounterForAmount(2,0));
        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($amountNrCounterMap, $allowedRange);

        self::assertFalse($rangedAmountNrCounterMap->aboveMaximum(1));
    }

    public function testwithinRange(): void
    {
        $amountNrCounterMap = new AmountNrCounterMap(2);

        $allowedRange = new AmountRange(new CounterForAmount(1,2), new CounterForAmount(2,0));
        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($amountNrCounterMap, $allowedRange);

        self::assertTrue($rangedAmountNrCounterMap->withinRange(2));
    }

//
//    public function testAddHomeAways(): void
//    {
//        $counterForPlaceNrOne = new CounterForPlaceNr(1,1);
//        $counterForPlaceNrTwo = new CounterForPlaceNr(2, 2);
//        $placeNrCounterMap = new PlaceNrCounterMap(
//            [
//                $counterForPlaceNrOne->getPlaceNr() => $counterForPlaceNrOne,
//                $counterForPlaceNrTwo->getPlaceNr() => $counterForPlaceNrTwo
//            ]
//        );
//        $placeNrCounterMap->addHomeAways(
//            [
//                new OneVsOneHomeAway(new DuoPlaceNr(1,2))
//            ]
//        );
//        self::assertSame(2, $placeNrCounterMap->count(1));
//        self::assertSame(3, $placeNrCounterMap->count(2));
//    }
//
//    public function testAddHomeAwayWithNonExistingPlace(): void
//    {
//        $counterForPlaceNrOne = new CounterForPlaceNr(1,1);
//        $counterForPlaceNrTwo = new CounterForPlaceNr(2, 2);
//        $placeNrCounterMap = new PlaceNrCounterMap(
//            [
//                $counterForPlaceNrOne->getPlaceNr() => $counterForPlaceNrOne,
//                $counterForPlaceNrTwo->getPlaceNr() => $counterForPlaceNrTwo
//            ]
//        );
//        $placeNrCounterMap->addHomeAway(
//            new OneVsOneHomeAway(new DuoPlaceNr(1,3))
//        );
//        self::assertSame(2, $placeNrCounterMap->count(1));
//        self::assertSame(2, $placeNrCounterMap->count(2));
//    }
//
//    public function testRemoveHomeAway(): void
//    {
//        $counterForPlaceNrOne = new CounterForPlaceNr(1,1);
//        $counterForPlaceNrTwo = new CounterForPlaceNr(2, 2);
//        $placeNrCounterMap = new PlaceNrCounterMap(
//            [
//                $counterForPlaceNrOne->getPlaceNr() => $counterForPlaceNrOne,
//                $counterForPlaceNrTwo->getPlaceNr() => $counterForPlaceNrTwo
//            ]
//        );
//        $placeNrCounterMap->removeHomeAway(new OneVsOneHomeAway(new DuoPlaceNr(1,2)));
//        self::assertSame(0, $placeNrCounterMap->count(1));
//        self::assertSame(1, $placeNrCounterMap->count(2));
//    }
//
//    public function testRemoveHomeAwayNonExistingPlace(): void
//    {
//        $counterForPlaceNrOne = new CounterForPlaceNr(1,1);
//        $counterForPlaceNrTwo = new CounterForPlaceNr(2, 2);
//        $placeNrCounterMap = new PlaceNrCounterMap(
//            [
//                $counterForPlaceNrOne->getPlaceNr() => $counterForPlaceNrOne,
//                $counterForPlaceNrTwo->getPlaceNr() => $counterForPlaceNrTwo
//            ]
//        );
//        $placeNrCounterMap->removeHomeAway(new OneVsOneHomeAway(new DuoPlaceNr(1,3)));
//        self::assertSame(0, $placeNrCounterMap->count(1));
//        self::assertSame(2, $placeNrCounterMap->count(2));
//    }
//


    public function testCloneAsSideNrCounterMap(): void
    {
        $amountNrCounterMap = new AmountNrCounterMap(1);
        $allowedRange = new AmountRange(new CounterForAmount(2,3), new CounterForAmount(4,3));
        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($amountNrCounterMap, $allowedRange);

        self::expectException(\Exception::class);
        $rangedAmountNrCounterMap->cloneAsSideNrCounterMap();
    }

    public function testCloneAsSideNrCounterMapException(): void
    {
        $sideNrCounterMap = new SideNrCounterMap(Side::Home, 4);
        $allowedRange = new AmountRange(new CounterForAmount(2,3), new CounterForAmount(4,3));
        $rangedSideNrCounterMap = new RangedPlaceNrCounterMap($sideNrCounterMap, $allowedRange);

        $cloned = $rangedSideNrCounterMap->cloneAsSideNrCounterMap();
        self::assertInstanceOf(SideNrCounterMap::class, $cloned);
    }

    public function testOutput(): void
    {
        $placeNrCounterMap = new AmountNrCounterMap(5);

        $allowedRange = new AmountRange(new CounterForAmount(2,3), new CounterForAmount(4,3));
        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($placeNrCounterMap, $allowedRange);

        $logger = $this->createLogger();

        self::expectNotToPerformAssertions();
        $rangedAmountNrCounterMap->output(
            $logger, 'prefix ', ' header'
        );
    }

    protected function createLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
//        $handler = new StreamHandler('php://stdout', Logger::INFO);
//        $logger->pushHandler($handler);
        return $logger;
    }
}
