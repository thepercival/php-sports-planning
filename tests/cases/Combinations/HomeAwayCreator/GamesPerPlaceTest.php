<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Combinations\HomeAwayCreator;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsPlanning\Combinations\HomeAwayCreator\GamesPerPlace as HomeAwayCreator;
use SportsPlanning\TestHelper\PlanningCreator;
use SportsPlanning\SportVariant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsPlanning\Combinations\Output\HomeAway as HomeAwayOutput;

class GamesPerPlaceTest extends TestCase
{
    use PlanningCreator;

    public function testSimple1VS1(): void
    {
        $sportVariant = new AgainstGpp(1, 1, 1);
        $input = $this->createInput([5]);
        $poule = $input->getPoule(1);
        $creator = new HomeAwayCreator();
        $variantWithPoule = new AgainstGppWithPoule($poule, $sportVariant);
        $homeAways = $creator->create($variantWithPoule);
        // (new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(10, $homeAways);
    }

    public function testSimple1VS2Pl3(): void
    {
        $sportVariant = new AgainstGpp(1, 2, 1);
        $input = $this->createInput([3]);
        $poule = $input->getPoule(1);
        $creator = new HomeAwayCreator();
        $variantWithPoule = new AgainstGppWithPoule($poule, $sportVariant);
        $homeAways = $creator->create($variantWithPoule);
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(3, $homeAways);
    }

    public function testSimple1VS2Pl4(): void
    {
        $sportVariant = new AgainstGpp(1, 2, 1);
        $input = $this->createInput([4]);
        $poule = $input->getPoule(1);
        $creator = new HomeAwayCreator();
        $variantWithPoule = new AgainstGppWithPoule($poule, $sportVariant);
        $homeAways = $creator->create($variantWithPoule);
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(12, $homeAways);
    }

    public function testSimple2VS2Pl4(): void
    {
        $sportVariant = new AgainstGpp(2, 2, 1);
        $input = $this->createInput([4]);
        $poule = $input->getPoule(1);
        $creator = new HomeAwayCreator();
        $variantWithPoule = new AgainstGppWithPoule($poule, $sportVariant);
        $homeAways = $creator->create($variantWithPoule);
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(3, $homeAways);
    }

    public function testSimple2VS2Pl5(): void
    {
        $sportVariant = new AgainstGpp(2, 2, 1);
        $input = $this->createInput([5]);
        $poule = $input->getPoule(1);
        $creator = new HomeAwayCreator();
        $variantWithPoule = new AgainstGppWithPoule($poule, $sportVariant);
        $homeAways = $creator->create($variantWithPoule);
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(15, $homeAways);
    }

    public function testSimple2VS2Pl6(): void
    {
        $sportVariant = new AgainstGpp(2, 2, 1);
        $input = $this->createInput([6]);
        $poule = $input->getPoule(1);
        $creator = new HomeAwayCreator();
        $variantWithPoule = new AgainstGppWithPoule($poule, $sportVariant);
        $homeAways = $creator->create($variantWithPoule);
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(45, $homeAways);
    }

    public function testSimple2VS2Pl7(): void
    {
        $sportVariant = new AgainstGpp(2, 2, 1);
        $input = $this->createInput([7]);
        $poule = $input->getPoule(1);
        $creator = new HomeAwayCreator();
        $variantWithPoule = new AgainstGppWithPoule($poule, $sportVariant);
        $homeAways = $creator->create($variantWithPoule);
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(105, $homeAways);
    }

//    public function test1Poule12Places(): void
//    {
//        $sportVariant = new AgainstSportVariant(1, 1, 1, 0);
//        $input = $this->createInput([7]);
//        $poule = $input->getPoule(1);
//        $creator = new HomeAwayCreator($poule, $sportVariant);
//        $homeAways = $creator->createForOneH2H();
//        (new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
//        (new HomeAwayOutput($this->getLogger()))->outputTotals($homeAways);
//        // self::assertCount(66, $homeAways);
//
    ////        $place11 = $poule->getPlace(11);
    ////        $homes = array_filter($homeAways, fn ($homeAway) => $homeAway->getHome()->has($place11));
    ////
    ////        self::assertCount(6, $homes);
//    }

    protected function getLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', Logger::INFO);
        $logger->pushHandler($handler);
        return $logger;
    }
}
