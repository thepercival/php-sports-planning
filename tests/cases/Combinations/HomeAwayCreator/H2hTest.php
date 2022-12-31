<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Combinations\HomeAwayCreator;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\VariantWithFields;
use SportsPlanning\SportVariant\WithPoule\Against\H2h as AgainstH2hWithPoule;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsPlanning\Combinations\HomeAwayCreator\H2h as HomeAwayCreator;
use SportsPlanning\TestHelper\PlanningCreator;

class H2hTest extends TestCase
{
    use PlanningCreator;

    public function testSimple1VS1Pl2(): void
    {
        $sportVariant = new AgainstH2h(1, 1, 1);
        $sportVariantWithFields = new VariantWithFields($sportVariant, 1);
        $input = $this->createInput([2], [$sportVariantWithFields]);
        $poule = $input->getPoule(1);
        $creator = new HomeAwayCreator();
        $homeAways = $creator->createForOneH2H(new AgainstH2hWithPoule($poule, $sportVariant));
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(1, $homeAways);
    }

    public function testSimple1VS1Pl3(): void
    {
        $sportVariant = new AgainstH2h(1, 1, 1);
        $sportVariantWithFields = new VariantWithFields($sportVariant, 1);
        $input = $this->createInput([3],[$sportVariantWithFields]);
        $poule = $input->getPoule(1);
        $creator = new HomeAwayCreator();
        $homeAways = $creator->createForOneH2H(new AgainstH2hWithPoule($poule, $sportVariant));
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(3, $homeAways);
    }

    public function testSimple1VS1Pl4(): void
    {
        $sportVariant = new AgainstH2h(1, 1, 1);
        $sportVariantWithFields = new VariantWithFields($sportVariant, 1);
        $input = $this->createInput([4],[$sportVariantWithFields]);
        $poule = $input->getPoule(1);
        $creator = new HomeAwayCreator();
        $homeAways = $creator->createForOneH2H(new AgainstH2hWithPoule($poule, $sportVariant));
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(6, $homeAways);
    }

    public function testSimple1VS1Pl5(): void
    {
        $sportVariant = new AgainstH2h(1, 1, 1);
        $sportVariantWithFields = new VariantWithFields($sportVariant, 1);
        $input = $this->createInput([5], [$sportVariantWithFields]);
        $poule = $input->getPoule(1);
        $creator = new HomeAwayCreator();
        $homeAways = $creator->createForOneH2H(new AgainstH2hWithPoule($poule, $sportVariant));
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(10, $homeAways);
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
