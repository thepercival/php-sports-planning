<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Counters\Maps;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\CounterForDuoPlaceNr;
use SportsPlanning\Counters\CounterForPlaceNr;
use SportsPlanning\Counters\Maps\PlaceNrCounterMapAbstract;
use SportsPlanning\Counters\Maps\Schedule\AmountNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\Counters\Reports\PlaceNrCountersPerAmountReport;
class PlaceNrCounterMapTest extends TestCase
{

//    public function testCountItOne(): void
//    {
////        $counterForPlaceNr = new CounterForPlaceNr(1);
////        $placeNrCounterMap = new PlaceNrCounterMapAbstract(
////            [ $counterForPlaceNr->getPlaceNr() => $counterForPlaceNr ]
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
//        $placeNrCounterMap->incrementPlaceNr(1);
//        self::assertSame(1, $placeNrCounterMap->count($counterForPlaceNr->getPlaceNr()));
//
//    }

    public function testCountItTwo(): void
    {
//        $counterForPlaceNrOne = new CounterForPlaceNr(1,1);
//        $counterForPlaceNrTwo = new CounterForPlaceNr(2, 2);
//        $placeNrCounterMap = new PlaceNrCounterMapAbstract(
//            [
//                $counterForPlaceNrOne->getPlaceNr() => $counterForPlaceNrOne,
//                $counterForPlaceNrTwo->getPlaceNr() => $counterForPlaceNrTwo
//            ]
//        );

        $placeNrCounterMap = new AmountNrCounterMap(2);
//        $placeNrCounterMap->addCounters(
//            [
//                new CounterForPlaceNr(1,1),
//                new CounterForPlaceNr(2, 2),
//                new CounterForPlaceNr(3, 2),
//                new CounterForPlaceNr(4, 2),
//                new CounterForPlaceNr(5, 2)
//            ]
//        );


        self::assertSame(0, $placeNrCounterMap->count(3));
    }

    public function testAddHomeAways(): void
    {
//        $counterForPlaceNrOne = new CounterForPlaceNr(1,1);
//        $counterForPlaceNrTwo = new CounterForPlaceNr(2, 2);
//        $placeNrCounterMap = new PlaceNrCounterMapAbstract(
//            [
//                $counterForPlaceNrOne->getPlaceNr() => $counterForPlaceNrOne,
//                $counterForPlaceNrTwo->getPlaceNr() => $counterForPlaceNrTwo
//            ]
//        );

        $placeNrCounterMap = new AmountNrCounterMap(2);
//        $placeNrCounterMap->addCounters(
//            [
//                new CounterForPlaceNr(1,1),
//                new CounterForPlaceNr(2, 2),
//                new CounterForPlaceNr(3, 2),
//                new CounterForPlaceNr(4, 2),
//                new CounterForPlaceNr(5, 2)
//            ]
//        );

        $placeNrCounterMap->addHomeAways(
            [
                new OneVsOneHomeAway(new DuoPlaceNr(1,2))
            ]
        );
        self::assertSame(2, $placeNrCounterMap->count(1));
        self::assertSame(3, $placeNrCounterMap->count(2));
    }

    public function testAddHomeAwayWithNonExistingPlace(): void
    {
//        $counterForPlaceNrOne = new CounterForPlaceNr(1,1);
//        $counterForPlaceNrTwo = new CounterForPlaceNr(2, 2);
//        $placeNrCounterMap = new PlaceNrCounterMapAbstract(
//            [
//                $counterForPlaceNrOne->getPlaceNr() => $counterForPlaceNrOne,
//                $counterForPlaceNrTwo->getPlaceNr() => $counterForPlaceNrTwo
//            ]
//        );

        $placeNrCounterMap = new AmountNrCounterMap(2);
//        $placeNrCounterMap->addCounters(
//            [
//                new CounterForPlaceNr(1,1),
//                new CounterForPlaceNr(2, 2),
//                new CounterForPlaceNr(3, 2),
//                new CounterForPlaceNr(4, 2),
//                new CounterForPlaceNr(5, 2)
//            ]
//        );

        $placeNrCounterMap->addHomeAway(
            new OneVsOneHomeAway(new DuoPlaceNr(1,3))
        );
        self::assertSame(2, $placeNrCounterMap->count(1));
        self::assertSame(2, $placeNrCounterMap->count(2));
    }

    public function testRemoveHomeAway(): void
    {
//        $counterForPlaceNrOne = new CounterForPlaceNr(1,1);
//        $counterForPlaceNrTwo = new CounterForPlaceNr(2, 2);
//        $placeNrCounterMap = new PlaceNrCounterMapAbstract(
//            [
//                $counterForPlaceNrOne->getPlaceNr() => $counterForPlaceNrOne,
//                $counterForPlaceNrTwo->getPlaceNr() => $counterForPlaceNrTwo
//            ]
//        );


        $placeNrCounterMap = new AmountNrCounterMap(2);
//        $placeNrCounterMap->addCounters(
//            [
//                new CounterForPlaceNr(1,1),
//                new CounterForPlaceNr(2, 2),
//                new CounterForPlaceNr(3, 2),
//                new CounterForPlaceNr(4, 2),
//                new CounterForPlaceNr(5, 2)
//            ]
//        );
//
        $placeNrCounterMap->removeHomeAway(new OneVsOneHomeAway(new DuoPlaceNr(1,2)));
        self::assertSame(0, $placeNrCounterMap->count(1));
        self::assertSame(1, $placeNrCounterMap->count(2));
    }

    public function testRemoveHomeAwayNonExistingPlace(): void
    {
//        $counterForPlaceNrOne = new CounterForPlaceNr(1,1);
//        $counterForPlaceNrTwo = new CounterForPlaceNr(2, 2);
//        $placeNrCounterMap = new PlaceNrCounterMapAbstract(
//            [
//                $counterForPlaceNrOne->getPlaceNr() => $counterForPlaceNrOne,
//                $counterForPlaceNrTwo->getPlaceNr() => $counterForPlaceNrTwo
//            ]
//        );

        $placeNrCounterMap = new AmountNrCounterMap(2);
//        $placeNrCounterMap->addCounters(
//            [
//                new CounterForPlaceNr(1,1),
//                new CounterForPlaceNr(2, 2),
//                new CounterForPlaceNr(3, 2),
//                new CounterForPlaceNr(4, 2),
//                new CounterForPlaceNr(5, 2)
//            ]
//        );

        $placeNrCounterMap->removeHomeAway(new OneVsOneHomeAway(new DuoPlaceNr(1,3)));
        self::assertSame(0, $placeNrCounterMap->count(1));
        self::assertSame(2, $placeNrCounterMap->count(2));
    }

    public function testGetPlaceNrsGreaterThan(): void
    {
//        $counterForPlaceNrOne = new CounterForPlaceNr(1,1);
//        $counterForPlaceNrTwo = new CounterForPlaceNr(2, 2);
//        $placeNrCounterMap = new PlaceNrCounterMapAbstract(
//            [
//                $counterForPlaceNrOne->getPlaceNr() => $counterForPlaceNrOne,
//                $counterForPlaceNrTwo->getPlaceNr() => $counterForPlaceNrTwo
//            ]
//        );

        $placeNrCounterMap = new AmountNrCounterMap(2);
//        $placeNrCounterMap->addCounters(
//            [
//                new CounterForPlaceNr(1,1),
//                new CounterForPlaceNr(2, 2),
//                new CounterForPlaceNr(3, 2),
//                new CounterForPlaceNr(4, 2),
//                new CounterForPlaceNr(5, 2)
//            ]
//        );
        $placeNrCounterMap->removeHomeAway(new OneVsOneHomeAway(new DuoPlaceNr(1,3)));
        self::assertCount(1, $placeNrCounterMap->getPlaceNrsGreaterThan(1));
        self::assertCount(1, $placeNrCounterMap->getPlaceNrsSmallerThan(1));
    }

    public function testClone(): void
    {
//        $counterForPlaceNrOne = new CounterForPlaceNr(1,1);
//        $counterForPlaceNrTwo = new CounterForPlaceNr(2, 2);
//        $placeNrCounterMap = new PlaceNrCounterMapAbstract(
//            [
//                $counterForPlaceNrOne->getPlaceNr() => $counterForPlaceNrOne,
//                $counterForPlaceNrTwo->getPlaceNr() => $counterForPlaceNrTwo
//            ]
//        );

        $placeNrCounterMap = new AmountNrCounterMap(2);
//        $placeNrCounterMap->addCounters(
//            [
//                new CounterForPlaceNr(1,1),
//                new CounterForPlaceNr(2, 2),
//                new CounterForPlaceNr(3, 2),
//                new CounterForPlaceNr(4, 2),
//                new CounterForPlaceNr(5, 2)
//            ]
//        );

        $placeNrCounterMapClone = clone $placeNrCounterMap;
        $placeNrCounterMap->removeHomeAway(new OneVsOneHomeAway(new DuoPlaceNr(1,2)));

        self::assertSame(1, $placeNrCounterMapClone->count(1));
        self::assertSame(2, $placeNrCounterMapClone->count(2));
    }

    public function testCalculateReportAndOutput(): void
    {
//        $counterForPlaceNrOne = new CounterForPlaceNr(1,1);
//        $counterForPlaceNrTwo = new CounterForPlaceNr(2, 2);
//        $counterForPlaceNrThree = new CounterForPlaceNr(3, 2);
//        $counterForPlaceNrFour = new CounterForPlaceNr(4, 2);
//        $counterForPlaceNrFive = new CounterForPlaceNr(5, 2);
//        $placeNrCounterMap = new PlaceNrCounterMapAbstract(
//            [
//                $counterForPlaceNrOne->getPlaceNr() => $counterForPlaceNrOne,
//                $counterForPlaceNrTwo->getPlaceNr() => $counterForPlaceNrTwo,
//                $counterForPlaceNrThree->getPlaceNr() => $counterForPlaceNrThree,
//                $counterForPlaceNrFour->getPlaceNr() => $counterForPlaceNrFour,
//                $counterForPlaceNrFive->getPlaceNr() => $counterForPlaceNrFive
//            ]
//        );

        $placeNrCounterMap = new AmountNrCounterMap(5);
//        $placeNrCounterMap->addCounters(
//            [
//                new CounterForPlaceNr(1,1),
//                new CounterForPlaceNr(2, 2),
//                new CounterForPlaceNr(3, 2),
//                new CounterForPlaceNr(4, 2),
//                new CounterForPlaceNr(5, 2)
//            ]
//        );

        $logger = $this->createLogger();

        $placeNrCounterMap->output(
            $logger, 'prefix ', ' header'
        );

        $placeNrCountersReport = $placeNrCounterMap->calculateReport();
        self::assertInstanceOf(PlaceNrCountersPerAmountReport::class, $placeNrCountersReport);

    }

    protected function createLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
//        $handler = new StreamHandler('php://stdout', Logger::INFO);
//        $logger->pushHandler($handler);
        return $logger;
    }
}
