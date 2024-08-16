<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Counters\Maps;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SportsPlanning\Combinations\DuoDuoPlaceNr;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\CounterForDuoDuoPlaceNr;
use SportsPlanning\Counters\CounterForDuoPlaceNr;
use SportsPlanning\Counters\Maps\DuoPlaceNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\Counters\Reports\DuoPlaceNrCountersReport;
class DuoPlaceNrCounterMapTest extends TestCase
{

    public function testCountItOne(): void
    {
        $duoPlaceNrOne = new DuoPlaceNr(1, 2);
        $counterForDuoPlaceNrOne = new CounterForDuoPlaceNr($duoPlaceNrOne);
        $duoPlaceNrCounterMap = new DuoPlaceNrCounterMap(
            [ $counterForDuoPlaceNrOne->getIndex() => $counterForDuoPlaceNrOne ]
        );
        $duoPlaceNrCounterMap->incrementDuoPlaceNr($duoPlaceNrOne);
        self::assertSame(1, $duoPlaceNrCounterMap->count($duoPlaceNrOne));
    }

    public function testCountItTwo(): void
    {
        $duoPlaceNrOne = new DuoPlaceNr(1, 2);
        $duoPlaceNrTwo = new DuoPlaceNr(3, 4);
        $duoPlaceNrCounterMap = new DuoPlaceNrCounterMap(
            [
                $duoPlaceNrOne->getIndex() => new CounterForDuoPlaceNr($duoPlaceNrOne),
                $duoPlaceNrTwo->getIndex() => new CounterForDuoPlaceNr($duoPlaceNrTwo)
            ]
        );
        $duoPlaceNrCounterMap->incrementDuoPlaceNr($duoPlaceNrOne);
        $duoPlaceNrCounterMap->incrementDuoPlaceNr($duoPlaceNrTwo);

        self::assertSame(1, $duoPlaceNrCounterMap->count($duoPlaceNrOne));
        self::assertSame(2, $duoPlaceNrCounterMap->count());
        self::assertSame(0, $duoPlaceNrCounterMap->count(new DuoPlaceNr(1, 5)));
    }

    public function testIncrementDuoPlaceNrs(): void
    {
        $duoPlaceNrOne = new DuoPlaceNr(1, 2);
        $duoPlaceNrTwo = new DuoPlaceNr(3, 4);
        $duoPlaceNrCounterMap = new DuoPlaceNrCounterMap(
            [
                $duoPlaceNrOne->getIndex() => new CounterForDuoPlaceNr($duoPlaceNrOne),
                $duoPlaceNrTwo->getIndex() => new CounterForDuoPlaceNr($duoPlaceNrTwo)
            ]
        );
        $duoPlaceNrCounterMap->incrementDuoPlaceNrs([$duoPlaceNrOne,$duoPlaceNrTwo]);

        self::assertSame(1, $duoPlaceNrCounterMap->count($duoPlaceNrOne));
        self::assertSame(2, $duoPlaceNrCounterMap->count());
    }

    public function testDecrementDuoPlaceNr(): void
    {
        $duoPlaceNrOne = new DuoPlaceNr(1, 2);
        $duoPlaceNrCounterMap = new DuoPlaceNrCounterMap(
            [
                $duoPlaceNrOne->getIndex() => new CounterForDuoPlaceNr($duoPlaceNrOne, 2)
            ]
        );
        $duoPlaceNrCounterMap->decrementDuoPlaceNr($duoPlaceNrOne);

        self::assertSame(1, $duoPlaceNrCounterMap->count($duoPlaceNrOne));
    }

    public function testClone(): void
    {
        $duoPlaceNrOne = new DuoPlaceNr(1, 2);
        $duoPlaceNrTwo = new DuoPlaceNr(3, 4);
        $duoPlaceNrCounterMap = new DuoPlaceNrCounterMap(
            [
                $duoPlaceNrOne->getIndex() => new CounterForDuoPlaceNr($duoPlaceNrOne),
                $duoPlaceNrTwo->getIndex() => new CounterForDuoPlaceNr($duoPlaceNrTwo)
            ]
        );
        $duoPlaceNrCounterMapClone = clone $duoPlaceNrCounterMap;
        $duoPlaceNrCounterMap->incrementDuoPlaceNrs([$duoPlaceNrOne,$duoPlaceNrTwo]);

        self::assertSame(0, $duoPlaceNrCounterMapClone->count($duoPlaceNrOne));
    }

    public function testCalculateReportAndOutput(): void
    {
        $counterForDuoPlaceNrOne = new CounterForDuoPlaceNr(new DuoPlaceNr(1,2),1);
        $counterForDuoPlaceNrTwo = new CounterForDuoPlaceNr(new DuoPlaceNr(3,4), 2);
        $counterForDuoPlaceNrThree = new CounterForDuoPlaceNr(new DuoPlaceNr(1,3), 2);
        $counterForDuoPlaceNrFour = new CounterForDuoPlaceNr(new DuoPlaceNr(2,4), 2);
        $counterForDuoPlaceNrFive = new CounterForDuoPlaceNr(new DuoPlaceNr(1,4), 2);
        $counterForDuoPlaceNrSix = new CounterForDuoPlaceNr(new DuoPlaceNr(2,3), 2);
        $duoPlaceNrCounterMap = new DuoPlaceNrCounterMap(
            [
                $counterForDuoPlaceNrOne->getIndex() => $counterForDuoPlaceNrOne,
                $counterForDuoPlaceNrTwo->getIndex() => $counterForDuoPlaceNrTwo,
                $counterForDuoPlaceNrThree->getIndex() => $counterForDuoPlaceNrThree,
                $counterForDuoPlaceNrFour->getIndex() => $counterForDuoPlaceNrFour,
                $counterForDuoPlaceNrFive->getIndex() => $counterForDuoPlaceNrFive,
                $counterForDuoPlaceNrSix->getIndex() => $counterForDuoPlaceNrSix
            ]
        );

        $logger = $this->createLogger();

        $duoPlaceNrCounterMap->output(
            $logger, 'prefix ', ' header'
        );

        $duoPlaceNrCountersReport = $duoPlaceNrCounterMap->calculateReport();
        self::assertInstanceOf(DuoPlaceNrCountersReport::class, $duoPlaceNrCountersReport);

    }

    protected function createLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
//        $handler = new StreamHandler('php://stdout', Logger::INFO);
//        $logger->pushHandler($handler);
        return $logger;
    }
}
