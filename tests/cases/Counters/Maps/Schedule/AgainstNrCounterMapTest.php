<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Counters\Maps\Schedule;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\Maps\Schedule\AgainstNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;

class AgainstNrCounterMapTest extends TestCase
{
    public function testZeroPlacesException(): void
    {
        self::expectException(\Exception::class);
        new AgainstNrCounterMap(0);
    }

    public function testCountItOne(): void
    {
        $againstNrCounterMap = new AgainstNrCounterMap(3);
        $duoPlaceNr = new DuoPlaceNr(2, 1);
        $againstNrCounterMap->addHomeAway(new OneVsOneHomeAway(2, 1));
        self::assertSame(1, $againstNrCounterMap->count($duoPlaceNr));
        self::assertSame(0, $againstNrCounterMap->count(new DuoPlaceNr(2, 3)));
        self::assertSame(0, $againstNrCounterMap->count(new DuoPlaceNr(1, 3)));
    }

    public function testCountException(): void
    {
        $againstNrCounterMap = new AgainstNrCounterMap(2);
        self::expectException(\Exception::class);
        self::assertSame(0, $againstNrCounterMap->count(new DuoPlaceNr(1, 3)));
    }

    public function testDecrementDuoPlaceNr(): void
    {
        $againstNrCounterMap = new AgainstNrCounterMap(3);
        $againstNrCounterMap->addHomeAways(
            [
                new OneVsOneHomeAway(2,3)
            ]
        );
        $againstNrCounterMap->decrementDuoPlaceNr(new DuoPlaceNr(2, 3));
        self::assertSame(0, $againstNrCounterMap->count(new DuoPlaceNr(2, 3)));
    }

    public function testAddHomeAways(): void
    {
        $againstNrCounterMap = new AgainstNrCounterMap(4);
        $againstNrCounterMap->addHomeAways(
            [
                new OneVsOneHomeAway(2,3),
                new OneVsTwoHomeAway(2, new DuoPlaceNr(1,4))
            ]
        );
        self::assertSame(1, $againstNrCounterMap->count(new DuoPlaceNr(1, 2)));
        self::assertSame(0, $againstNrCounterMap->count(new DuoPlaceNr(1, 3)));
        self::assertSame(1, $againstNrCounterMap->count(new DuoPlaceNr(2, 3)));
        self::assertSame(1, $againstNrCounterMap->count(new DuoPlaceNr(2, 4)));
    }
//
//    public function testAddHomeAwayWithNonExistingPlace(): void
//    {
//        $amountNrCounterMap = new AmountNrCounterMap(3);
//        $amountNrCounterMap->addHomeAways(
//            [
//                new OneVsOneHomeAway(new DuoPlaceNr(1,2))
//            ]
//        );
//
//        $amountNrCounterMap->addHomeAway(
//            new OneVsOneHomeAway(new DuoPlaceNr(1,3))
//        );
//        self::assertSame(2, $amountNrCounterMap->count(1));
//        self::assertSame(1, $amountNrCounterMap->count(2));
//    }
//
//    public function testRemoveHomeAway(): void
//    {
//        $amountNrCounterMap = new AmountNrCounterMap(3);
//        $amountNrCounterMap->incrementPlaceNr(2);
//        $amountNrCounterMap->addHomeAways(
//            [
//                new OneVsOneHomeAway(new DuoPlaceNr(1,2))
//            ]
//        );
//
//        $amountNrCounterMap->removeHomeAway(new OneVsOneHomeAway(new DuoPlaceNr(1,2)));
//        self::assertSame(0, $amountNrCounterMap->count(1));
//        self::assertSame(1, $amountNrCounterMap->count(2));
//    }
//
//    public function testRemoveHomeAwayNonExistingPlace(): void
//    {
//        $amountNrCounterMap = new AmountNrCounterMap(3);
//        $amountNrCounterMap->incrementPlaceNr(1);
//        $amountNrCounterMap->incrementPlaceNr(2);
//        $amountNrCounterMap->incrementPlaceNr(3);
//
//        $amountNrCounterMap->removeHomeAway(new OneVsOneHomeAway(new DuoPlaceNr(1,3)));
//        self::assertSame(0, $amountNrCounterMap->count(1));
//        self::assertSame(1, $amountNrCounterMap->count(2));
//        self::assertSame(0, $amountNrCounterMap->count(3));
//    }
//
////    public function testGetPlaceNrsGreaterThan(): void
////    {
////        $amountNrCounterMap = new AmountNrCounterMap(3);
////        $amountNrCounterMap->incrementPlaceNr(1);
////        $amountNrCounterMap->incrementPlaceNr(2);
////        $amountNrCounterMap->incrementPlaceNr(3);
////
////        self::assertCount(1, $placeNrCounterMap->getPlaceNrsGreaterThan(1));
////        self::assertCount(1, $placeNrCounterMap->getPlaceNrsSmallerThan(1));
////    }
//
    public function testClone(): void
    {
        $againstNrCounterMap = new AgainstNrCounterMap(3);
        $againstNrCounterMap->addHomeAways(
            [
                new OneVsOneHomeAway(2,3)
            ]
        );
        $againstNrCounterMapClone = clone $againstNrCounterMap;
        $againstNrCounterMapClone->incrementDuoPlaceNr(new DuoPlaceNr(2,3));

        self::assertSame(1, $againstNrCounterMap->count(new DuoPlaceNr(2,3)));
        self::assertSame(2, $againstNrCounterMapClone->count(new DuoPlaceNr(2,3)));
    }

    public function testOutput(): void
    {
        $againstNrCounterMap = new AgainstNrCounterMap(5);
        $againstNrCounterMap->incrementDuoPlaceNr(new DuoPlaceNr(1,2));
        $againstNrCounterMap->incrementDuoPlaceNr(new DuoPlaceNr(1,3));

        $logger = $this->createLogger();

        self::expectNotToPerformAssertions();
        $againstNrCounterMap->output(
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
