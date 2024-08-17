<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Counters\Maps\Schedule;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side;
use SportsHelpers\SportRange;
use SportsPlanning\Combinations\Amount;
use SportsPlanning\Combinations\AmountRange;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\CounterForDuoPlaceNr;
use SportsPlanning\Counters\CounterForPlaceNr;
use SportsPlanning\Counters\Maps\PlaceNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\AmountNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\RangedPlaceNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\SideNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\Counters\Reports\PlaceNrCountersReport;
class RangedPlaceNrCounterMapTest extends TestCase
{
    public function testCloneAsSideNrCounterMap(): void
    {
        $amountNrCounterMap = new AmountNrCounterMap();
        $allowedRange = new AmountRange(new Amount(2,3), new Amount(4,3));
        $rangedAmountNrCounterMap = new RangedPlaceNrCounterMap($amountNrCounterMap, $allowedRange);

        self::expectException(\Exception::class);
        $rangedAmountNrCounterMap->cloneAsSideNrCounterMap();
    }

    public function testCloneAsSideNrCounterMapException(): void
    {
        $sideNrCounterMap = new SideNrCounterMap(Side::Home);
        $allowedRange = new AmountRange(new Amount(2,3), new Amount(4,3));
        $rangedSideNrCounterMap = new RangedPlaceNrCounterMap($sideNrCounterMap, $allowedRange);

        $cloned = $rangedSideNrCounterMap->cloneAsSideNrCounterMap();
        self::assertInstanceOf(SideNrCounterMap::class, $cloned);
    }


//    public function testCountItTwo(): void
//    {
//        $counterForPlaceNrOne = new CounterForPlaceNr(1,1);
//        $counterForPlaceNrTwo = new CounterForPlaceNr(2, 2);
//        $placeNrCounterMap = new PlaceNrCounterMap(
//            [
//                $counterForPlaceNrOne->getPlaceNr() => $counterForPlaceNrOne,
//                $counterForPlaceNrTwo->getPlaceNr() => $counterForPlaceNrTwo
//            ]
//        );
//        self::assertSame(2, $placeNrCounterMap->count());
//        self::assertSame(0, $placeNrCounterMap->count(3));
//    }
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
//    public function testGetPlaceNrsGreaterThan(): void
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
//        self::assertCount(1, $placeNrCounterMap->getPlaceNrsGreaterThan(1));
//        self::assertCount(1, $placeNrCounterMap->getPlaceNrsSmallerThan(1));
//    }
//
//    public function testClone(): void
//    {
//        $counterForPlaceNrOne = new CounterForPlaceNr(1,1);
//        $counterForPlaceNrTwo = new CounterForPlaceNr(2, 2);
//        $placeNrCounterMap = new PlaceNrCounterMap(
//            [
//                $counterForPlaceNrOne->getPlaceNr() => $counterForPlaceNrOne,
//                $counterForPlaceNrTwo->getPlaceNr() => $counterForPlaceNrTwo
//            ]
//        );
//        $placeNrCounterMapClone = clone $placeNrCounterMap;
//        $placeNrCounterMap->removeHomeAway(new OneVsOneHomeAway(new DuoPlaceNr(1,2)));
//
//        self::assertSame(1, $placeNrCounterMapClone->count(1));
//        self::assertSame(2, $placeNrCounterMapClone->count(2));
//    }
//
//    public function testCalculateReportAndOutput(): void
//    {
//        $counterForPlaceNrOne = new CounterForPlaceNr(1,1);
//        $counterForPlaceNrTwo = new CounterForPlaceNr(2, 2);
//        $counterForPlaceNrThree = new CounterForPlaceNr(3, 2);
//        $counterForPlaceNrFour = new CounterForPlaceNr(4, 2);
//        $counterForPlaceNrFive = new CounterForPlaceNr(5, 2);
//        $placeNrCounterMap = new PlaceNrCounterMap(
//            [
//                $counterForPlaceNrOne->getPlaceNr() => $counterForPlaceNrOne,
//                $counterForPlaceNrTwo->getPlaceNr() => $counterForPlaceNrTwo,
//                $counterForPlaceNrThree->getPlaceNr() => $counterForPlaceNrThree,
//                $counterForPlaceNrFour->getPlaceNr() => $counterForPlaceNrFour,
//                $counterForPlaceNrFive->getPlaceNr() => $counterForPlaceNrFive
//            ]
//        );
//
//        $logger = $this->createLogger();
//
//        $placeNrCounterMap->output(
//            $logger, 'prefix ', ' header'
//        );
//
//        $placeNrCountersReport = $placeNrCounterMap->calculateReport();
//        self::assertInstanceOf(PlaceNrCountersReport::class, $placeNrCountersReport);
//
//    }

    protected function createLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
//        $handler = new StreamHandler('php://stdout', Logger::INFO);
//        $logger->pushHandler($handler);
        return $logger;
    }
}
