<?php
declare(strict_types=1);

namespace SportsPlanning\Tests\Combinations;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsPlanning\Combinations\HomeAwayCreator;
use SportsPlanning\Combinations\Output\HomeAway as HomeAwayOutput;
use SportsPlanning\TestHelper\PlanningCreator;

class HomeAwayCreatorTest extends TestCase
{
    use PlanningCreator;

    public function testSimple1VS1Pl2(): void
    {
        $sportVariant = new AgainstSportVariant(1, 1, 1, 0);
        $input = $this->createInput([2]);
        $poule = $input->getPoule(1);
        $creator = new HomeAwayCreator($poule, $sportVariant);
        $homeAways = $creator->createForOneH2H();
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(1, $homeAways);
    }

    public function testSimple1VS1Pl3(): void
    {
        $sportVariant = new AgainstSportVariant(1, 1, 1, 0);
        $input = $this->createInput([3]);
        $poule = $input->getPoule(1);
        $creator = new HomeAwayCreator($poule, $sportVariant);
        $homeAways = $creator->createForOneH2H();
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(3, $homeAways);
    }

    public function testSimple1VS1Pl4(): void
    {
        $sportVariant = new AgainstSportVariant(1, 1, 1, 0);
        $input = $this->createInput([4]);
        $poule = $input->getPoule(1);
        $creator = new HomeAwayCreator($poule, $sportVariant);
        $homeAways = $creator->createForOneH2H();
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(6, $homeAways);
    }

    public function testSimple1VS1Pl5(): void
    {
        $sportVariant = new AgainstSportVariant(1, 1, 1, 0);
        $input = $this->createInput([5]);
        $poule = $input->getPoule(1);
        $creator = new HomeAwayCreator($poule, $sportVariant);
        $homeAways = $creator->createForOneH2H();
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(10, $homeAways);
    }

    public function testSimple1VS2Pl3(): void
    {
        $sportVariant = new AgainstSportVariant(1, 2, 0, 1);
        $input = $this->createInput([3]);
        $poule = $input->getPoule(1);
        $creator = new HomeAwayCreator($poule, $sportVariant);
        $homeAways = $creator->createForOneH2H();
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(3, $homeAways);
    }

    public function testSimple1VS2Pl4(): void
    {
        $sportVariant = new AgainstSportVariant(1, 2, 0, 1);
        $input = $this->createInput([4]);
        $poule = $input->getPoule(1);
        $creator = new HomeAwayCreator($poule, $sportVariant);
        $homeAways = $creator->createForOneH2H();
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(12, $homeAways);
    }

    public function testSimple2VS2Pl4(): void
    {
        $sportVariant = new AgainstSportVariant(2, 2, 0, 1);
        $input = $this->createInput([4]);
        $poule = $input->getPoule(1);
        $creator = new HomeAwayCreator($poule, $sportVariant);
        $homeAways = $creator->createForOneH2H();
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(3, $homeAways);
    }

    public function testSimple2VS2Pl5(): void
    {
        $sportVariant = new AgainstSportVariant(2, 2, 0, 1);
        $input = $this->createInput([5]);
        $poule = $input->getPoule(1);
        $creator = new HomeAwayCreator($poule, $sportVariant);
        $homeAways = $creator->createForOneH2H();
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(15, $homeAways);
    }

    public function testSimple2VS2Pl6(): void
    {
        $sportVariant = new AgainstSportVariant(2, 2, 0, 1);
        $input = $this->createInput([6]);
        $poule = $input->getPoule(1);
        $creator = new HomeAwayCreator($poule, $sportVariant);
        $homeAways = $creator->createForOneH2H();
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(45, $homeAways);
    }

    public function testSimple2VS2Pl7(): void
    {
        $sportVariant = new AgainstSportVariant(2, 2, 0, 1);
        $input = $this->createInput([7]);
        $poule = $input->getPoule(1);
        $creator = new HomeAwayCreator($poule, $sportVariant);
        $homeAways = $creator->createForOneH2H();
        //(new HomeAwayOutput($this->getLogger()))->outputHomeAways($homeAways);
        self::assertCount(105, $homeAways);
    }

    protected function getLogger(): LoggerInterface {
        $logger = new Logger("test-logger");
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', LOG_INFO);
        $logger->pushHandler($handler);
        return $logger;
    }
}
