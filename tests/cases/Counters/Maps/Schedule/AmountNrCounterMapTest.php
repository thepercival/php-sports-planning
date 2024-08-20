<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Counters\Maps\Schedule;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\CounterForAmount;
use SportsPlanning\Counters\CounterForPlaceNr;
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

        public function testCountItOne(): void
    {
        $amountNrCounterMap = new AmountNrCounterMap(5);
        $amountNrCounterMap->incrementPlaceNr(1);
        self::assertSame(1, $amountNrCounterMap->count(1));
    }

    public function testCountException(): void
    {
        $amountNrCounterMap = new AmountNrCounterMap(1);
        self::expectException(\Exception::class);
        $amountNrCounterMap->count(2);
    }

    public function testAddHomeAways(): void
    {
        $amountNrCounterMap = new AmountNrCounterMap(5);
        $amountNrCounterMap->incrementPlaceNr(1);
        $amountNrCounterMap->addHomeAways(
            [
                new OneVsOneHomeAway(new DuoPlaceNr(1,2))
            ]
        );
        self::assertSame(2, $amountNrCounterMap->count(1));
        self::assertSame(1, $amountNrCounterMap->count(2));
    }

    public function testAddHomeAwayWithNonExistingPlace(): void
    {
        $amountNrCounterMap = new AmountNrCounterMap(3);
        $amountNrCounterMap->addHomeAways(
            [
                new OneVsOneHomeAway(new DuoPlaceNr(1,2))
            ]
        );

        $amountNrCounterMap->addHomeAway(
            new OneVsOneHomeAway(new DuoPlaceNr(1,3))
        );
        self::assertSame(2, $amountNrCounterMap->count(1));
        self::assertSame(1, $amountNrCounterMap->count(2));
    }

    public function testRemoveHomeAway(): void
    {
        $amountNrCounterMap = new AmountNrCounterMap(3);
        $amountNrCounterMap->incrementPlaceNr(2);
        $amountNrCounterMap->addHomeAways(
            [
                new OneVsOneHomeAway(new DuoPlaceNr(1,2))
            ]
        );

        $amountNrCounterMap->removeHomeAway(new OneVsOneHomeAway(new DuoPlaceNr(1,2)));
        self::assertSame(0, $amountNrCounterMap->count(1));
        self::assertSame(1, $amountNrCounterMap->count(2));
    }

    public function testRemoveHomeAwayNonExistingPlace(): void
    {
        $amountNrCounterMap = new AmountNrCounterMap(3);
        $amountNrCounterMap->incrementPlaceNr(1);
        $amountNrCounterMap->incrementPlaceNr(2);
        $amountNrCounterMap->incrementPlaceNr(3);

        $amountNrCounterMap->removeHomeAway(new OneVsOneHomeAway(new DuoPlaceNr(1,3)));
        self::assertSame(0, $amountNrCounterMap->count(1));
        self::assertSame(1, $amountNrCounterMap->count(2));
        self::assertSame(0, $amountNrCounterMap->count(3));
    }

//    public function testGetPlaceNrsGreaterThan(): void
//    {
//        $amountNrCounterMap = new AmountNrCounterMap(3);
//        $amountNrCounterMap->incrementPlaceNr(1);
//        $amountNrCounterMap->incrementPlaceNr(2);
//        $amountNrCounterMap->incrementPlaceNr(3);
//
//        self::assertCount(1, $placeNrCounterMap->getPlaceNrsGreaterThan(1));
//        self::assertCount(1, $placeNrCounterMap->getPlaceNrsSmallerThan(1));
//    }

    public function testClone(): void
    {

        $placeNrCounterMap = new AmountNrCounterMap(3);
        $placeNrCounterMap->addHomeAway(new OneVsOneHomeAway(new DuoPlaceNr(1,2)));
        $placeNrCounterMap->addHomeAway(new OneVsOneHomeAway(new DuoPlaceNr(2,3)));
        $placeNrCounterMapClone = clone $placeNrCounterMap;
        $placeNrCounterMap->removeHomeAway(new OneVsOneHomeAway(new DuoPlaceNr(1,2)));

        self::assertSame(1, $placeNrCounterMapClone->count(1));
        self::assertSame(2, $placeNrCounterMapClone->count(2));
    }

    public function testOutput(): void
    {
        $amountNrCounterMap = new AmountNrCounterMap(3);
        $amountNrCounterMap->incrementPlaceNr(1);
        $amountNrCounterMap->incrementPlaceNr(2);

        $logger = $this->createLogger();

        self::expectNotToPerformAssertions();
        $amountNrCounterMap->output(
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
