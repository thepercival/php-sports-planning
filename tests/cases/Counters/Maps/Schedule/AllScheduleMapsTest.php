<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Counters\Maps\Schedule;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SportsHelpers\Against\Side;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Counters\Maps\Schedule\AgainstNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\AllScheduleMaps;
use SportsPlanning\Counters\Maps\Schedule\SideNrCounterMap;
use SportsPlanning\Counters\Maps\Schedule\TogetherNrCounterMap;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\HomeAways\OneVsTwoHomeAway;

class AllScheduleMapsTest extends TestCase
{
    public function testCountItOne(): void
    {
        $allScheduleMaps = new AllScheduleMaps(4);
        $allScheduleMaps->addHomeAways(
            [
                new OneVsTwoHomeAway(1, new DuoPlaceNr(2, 3))
            ]
        );
        self::assertSame(1, $allScheduleMaps->getAmountCounterMap()->count(1));
        self::assertSame(1, $allScheduleMaps->getHomeCounterMap()->count(1));
        self::assertSame(0, $allScheduleMaps->getAwayCounterMap()->count(1));
        self::assertSame(1, $allScheduleMaps->getWithCounterMap()->count(new DuoPlaceNr(2, 3)));
        self::assertSame(0, $allScheduleMaps->getAgainstCounterMap()->count(new DuoPlaceNr(2, 3)));
        self::assertSame(1, $allScheduleMaps->getTogetherCounterMap()->count(new DuoPlaceNr(2, 3)));

    }

    public function testClone(): void
    {
        $allScheduleMaps = new AllScheduleMaps(4);
        $allScheduleMapsClone = clone $allScheduleMaps;
        $allScheduleMaps->addHomeAways(
            [
                new OneVsOneHomeAway(2,3)
            ]
        );
        self::assertSame(1, $allScheduleMaps->getAmountCounterMap()->count(2));
        self::assertSame(0, $allScheduleMapsClone->getAmountCounterMap()->count(2));
    }

    public function testSetHomeMap(): void
    {
        $allScheduleMaps = new AllScheduleMaps(4);
        $allScheduleMaps->addHomeAways(
            [
                new OneVsOneHomeAway(2,3)
            ]
        );
        $allScheduleMaps->setHomeCounterMap(new SideNrCounterMap(Side::Home, 4));
        self::assertSame(0, $allScheduleMaps->getHomeCounterMap()->count(2));
    }

    public function testSetAwayMap(): void
    {
        $allScheduleMaps = new AllScheduleMaps(4);
        $allScheduleMaps->addHomeAways(
            [
                new OneVsOneHomeAway(2,3)
            ]
        );
        $allScheduleMaps->setAwayCounterMap(new SideNrCounterMap(Side::Away, 4));
        self::assertSame(0, $allScheduleMaps->getHomeCounterMap()->count(3));
    }

    public function testSetTogetherMap(): void
    {
        $allScheduleMaps = new AllScheduleMaps(4);
        $allScheduleMaps->addHomeAways(
            [
                new OneVsOneHomeAway(2,3)
            ]
        );
        $allScheduleMaps->setTogetherCounterMap(new TogetherNrCounterMap(4));
        self::assertSame(0, $allScheduleMaps->getTogetherCounterMap()->count(new DuoPlaceNr(2,3)));
    }

//
//    public function testOutput(): void
//    {
//        $againstNrCounterMap = new AgainstNrCounterMap(5);
//        $againstNrCounterMap->incrementDuoPlaceNr(new DuoPlaceNr(1,2));
//        $againstNrCounterMap->incrementDuoPlaceNr(new DuoPlaceNr(1,3));
//
//        $logger = $this->createLogger();
//
//        self::expectNotToPerformAssertions();
//        $againstNrCounterMap->output(
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
