<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Schedule\Creator\Against;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SportsHelpers\SportRange;
use SportsPlanning\Game\Creator as GameCreator;
use SportsPlanning\Planning;
use SportsPlanning\Planning\Output as PlanningOutput;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsPlanning\Schedule\Output as ScheduleOutput;
use SportsPlanning\Planning\Validator as PlanningValidator;
use SportsPlanning\Schedule\Creator as ScheduleCreator;
use SportsPlanning\TestHelper\PlanningCreator;

class GamesPerPlaceTest extends TestCase
{
    use PlanningCreator;

    public function test2V2Places4GamesPerPlace1(): void
    {
        $sportVariants = [
            $this->getAgainstGppSportVariantWithFields(1, 2, 2, 1 /* max = 2 */),
        ];
        $input = $this->createInput([4], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $schedules = $scheduleCreator->createFromInput($input);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(1, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test2V2Places4GamesPerPlace2(): void
    {
        $sportVariants = [
            $this->getAgainstGppSportVariantWithFields(1, 2, 2, 2 /* max = 2 */),
        ];
        $input = $this->createInput([4], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $schedules = $scheduleCreator->createFromInput($input);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(2, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test2V2Places4GamesPerPlace3(): void
    {
        $sportVariants = [
            $this->getAgainstGppSportVariantWithFields(1, 2, 2, 3 /* max = 2 */),
        ];
        $input = $this->createInput([4], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $schedules = $scheduleCreator->createFromInput($input);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(3, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test2V2Places4GamesPerPlace4(): void
    {
        $sportVariants = [
            $this->getAgainstGppSportVariantWithFields(1, 2, 2, 4 /* max = 2 */),
        ];
        $input = $this->createInput([4], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $schedules = $scheduleCreator->createFromInput($input);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        //(new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(4, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test2V2Places5GamesPerPlace1(): void
    {
        $sportVariants = [
            $this->getAgainstGppSportVariantWithFields(1, 2, 2, 1),
        ];
        $input = $this->createInput([5], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $schedules = $scheduleCreator->createFromInput($input);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        //(new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(1, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test2VS2With5PlacesAnd12GamesPerPlace(): void
    {
        $sportVariants = [
            $this->getAgainstGppSportVariantWithFields(1, 2, 2, 12),
        ];
        $input = $this->createInput([5], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $schedules = $scheduleCreator->createFromInput($input);
        // $scheduleCreator->setAllowedGppMargin(3);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(15, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test2VS2With6PlacesAnd30GamesPerPlace(): void
    {
        $sportVariants = [
            $this->getAgainstGppSportVariantWithFields(1, 2, 2, 30),
        ];
        $input = $this->createInput([6], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $scheduleCreator->setAllowedGppMargin(0);
        $schedules = $scheduleCreator->createFromInput($input);
//        (new ScheduleOutput($this->getLogger()))->output($schedules);
//        (new ScheduleOutput($this->getLogger()))->outputTotals($schedules);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(45, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

//    public function test2VS2Places10GamesPerPlace3(): void
//    {
//        // $time_start = microtime(true);
//        $sportVariants = [
//            $this->getAgainstSportVariantWithFields(5, 2, 2, 0, 30),
//        ];
//        $input = $this->createInput([10], $sportVariants);
//        $planning = new Planning($input, new SportRange(1, 1), 0);
//
//        $scheduleCreator = new ScheduleCreator($this->getLogger());
//        $schedules = $scheduleCreator->createFromInput($input);
//        $gameCreator = new GameCreator($this->getLogger());
//        $gameCreator->createGames($planning, $schedules);
//        // (new PlanningOutput())->outputWithGames($planning, true);
//
//        // echo 'Total Execution Time: '. (microtime(true) - $time_start);
//        // self::assertTrue((microtime(true) - $time_start) < 0.3);
//
    ////        self::assertCount(45, $planning->getAgainstGames());
//        $validator = new PlanningValidator();
//        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
//    }

    public function test1VS2With3PlacesAnd3GamesPerPlace(): void
    {
        $sportVariants = [
            $this->getAgainstGppSportVariantWithFields(1, 1, 2, 3),
        ];
        $input = $this->createInput([3], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $schedules = $scheduleCreator->createFromInput($input);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        //(new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(3, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test1VS2With2PlacesAnd1GamesPerPlace(): void
    {
        $sportVariants = [
            $this->getAgainstGppSportVariantWithFields(1, 1, 2),
        ];
        self::expectException(\Exception::class);
        new Planning($this->createInput([2], $sportVariants), new SportRange(1, 1), 0);
    }

    public function test1VS1With4PlacesAnd2Sports(): void
    {
        $sportVariants = [
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 3),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 3),
        ];
        $input = $this->createInput([4], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $schedules = $scheduleCreator->createFromInput($input);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(12, $planning->getAgainstGames());

        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test2Sports1UnequallyAssigned(): void
    {
        $sportVariants = [
            $this->getAgainstGppSportVariantWithFields(1, 2, 2),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 4),
        ];
        $input = $this->createInput([5], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $schedules = $scheduleCreator->createFromInput($input);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(11, $planning->getAgainstGames());
    }

    public function test2V2With6PlacesAnd8GamesPerPlace(): void
    {
        $sportVariants = [
            $this->getAgainstGppSportVariantWithFields(1, 2, 2, 8),
        ];
        $input = $this->createInput([6], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $schedules = $scheduleCreator->createFromInput($input);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(12, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test2V2With7PlacesAnd5GamesPerPlace(): void
    {
        $sportVariants = [
            $this->getAgainstGppSportVariantWithFields(1, 2, 2, 5),
        ];
        $input = $this->createInput([7], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $scheduleCreator->setAllowedGppMargin(0);
        $schedules = $scheduleCreator->createFromInput($input);
//        (new ScheduleOutput($this->getLogger()))->output($schedules);
//        (new ScheduleOutput($this->getLogger()))->outputTotals($schedules);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(8, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test2V2With7PlacesAnd6GamesPerPlace(): void
    {
        $sportVariants = [
            $this->getAgainstGppSportVariantWithFields(1, 2, 2, 6),
        ];
        $input = $this->createInput([7], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $scheduleCreator->setAllowedGppMargin(1);
        $schedules = $scheduleCreator->createFromInput($input);
//        (new ScheduleOutput($this->getLogger()))->output($schedules);
//        (new ScheduleOutput($this->getLogger()))->outputTotals($schedules);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(10, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

//    public function test2V2With7PlacesAnd24GamesPerPlace(): void
//    {
//        $sportVariants = [
//            $this->getAgainstGppSportVariantWithFields(1, 2, 2, 24),
//        ];
//        $input = $this->createInput([7], $sportVariants);
//        $planning = new Planning($input, new SportRange(2, 2), 0);
//
//        $scheduleCreator = new ScheduleCreator($this->getLogger());
//        $scheduleCreator->setAllowedGppMargin(0);
//        $schedules = $scheduleCreator->createFromInput($input);
//        $gameCreator = new GameCreator($this->getLogger());
//        $gameCreator->createGames($planning, $schedules);
//        // (new PlanningOutput())->outputWithGames($planning, true);
//
//        self::assertCount(42, $planning->getAgainstGames());
//        $validator = new PlanningValidator();
//        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
//    }

    public function test2V2With8PlacesAnd16GamesPerPlace(): void
    {
        $sportVariants = [
            $this->getAgainstGppSportVariantWithFields(1, 2, 2, 16),
        ];
        $input = $this->createInput([8], $sportVariants);
        $planning = new Planning($input, new SportRange(2, 2), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        // $scheduleCreator->setAgainstGppMargin(1);
        $schedules = $scheduleCreator->createFromInput($input);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(32, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test1V1With18PlacesAnd1GamesPerPlaceAnd7Sports(): void
    {
        $sportVariants = [
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1)
        ];
        $input = $this->createInput([18], $sportVariants);
        $planning = new Planning($input, new SportRange(9, 9), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $scheduleCreator->setAllowedGppMargin(0);
        $schedules = $scheduleCreator->createFromInput($input);
//        (new ScheduleOutput($this->getLogger()))->output($schedules);
//        (new ScheduleOutput($this->getLogger()))->outputTotals($schedules);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(63, $planning->getAgainstGames());
    }

    public function test1V1With8PlacesAnd1GamesPerPlaceAnd7Sports(): void
    {
        $sportVariants = [
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 1)
        ];
        $input = $this->createInput([8], $sportVariants);
        $planning = new Planning($input, new SportRange(4, 4), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $scheduleCreator->setAllowedGppMargin(0);
        $schedules = $scheduleCreator->createFromInput($input);
//        (new ScheduleOutput($this->getLogger()))->output($schedules);
//        (new ScheduleOutput($this->getLogger()))->outputTotals($schedules);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(28, $planning->getAgainstGames());
    }

//    public function test2V2With5PlacesAnd8GamesPerPlace(): void
//    {
//        $sportVariants = [
//            $this->getAgainstGppSportVariantWithFields(1, 2, 2, 8),
//        ];
//        $input = $this->createInput([5], $sportVariants);
//        $planning = new Planning($input, new SportRange(1, 1), 0);
//
//        $scheduleCreator = new ScheduleCreator($this->getLogger());
//        $scheduleCreator->setAgainstGppMargin(1);
//        $schedules = $scheduleCreator->createFromInput($input);
//        $gameCreator = new GameCreator($this->getLogger());
//        $gameCreator->createGames($planning, $schedules);
//        (new PlanningOutput())->outputWithGames($planning, true);
//        $scheduleOutput = new ScheduleOutput();
//        $scheduleOutput->outputTotals($schedules);
//
//        self::assertCount(10, $planning->getAgainstGames());
//        $validator = new PlanningValidator();
//        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
//    }



//    public function test2V2Places18GamesPerPlace16(): void
//    {
//        $sportVariants = [
//            $this->getAgainstGppSportVariantWithFields(4, 2, 2, 13),
//        ];
//        $input = $this->createInput([8], $sportVariants);
//        $planning = new Planning($input, new SportRange(1, 1), 0);
//
//        $scheduleCreator = new ScheduleCreator($this->getLogger());
//        $schedules = $scheduleCreator->createFromInput($input);
//        $gameCreator = new GameCreator($this->getLogger());
//        $gameCreator->createGames($planning, $schedules);
//        // (new PlanningOutput())->outputWithGames($planning, true);
//
//        self::assertCount(2, $planning->getAgainstGames());
//        $validator = new PlanningValidator();
//        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
//    }


    // commented for performance reasons
//    public function test2VS2Places10GamesPerPlace50(): void
//    {
//        $sportVariants = [
//            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 50),
//        ];
//        $input = $this->createInput([10], $sportVariants);
//        $planning = new Planning($input, new SportRange(1, 1), 0);
//
//        $scheduleCreator = new ScheduleCreator($this->getLogger());
//        $schedules = $scheduleCreator->createFromInput($input);
//        $gameCreator = new GameCreator($this->getLogger());
//        $gameCreator->createGames($planning, $schedules);
//
//        (new PlanningOutput())->outputWithGames($planning, true);
//
//        self::assertCount(125, $planning->getAgainstGames());
//    }

//    public function test1V1With10PlacesAnd999GamesPerPlace(): void
//    {
//        $sportVariants = [
//            $this->getAgainstGppSportVariantWithFields(2, 1, 1, 9),
//            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 9),
//            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 9)
//        ];
//        $input = $this->createInput([10], $sportVariants);
//        $planning = new Planning($input, new SportRange(3, 3), 0);
//
//        $scheduleCreator = new ScheduleCreator($this->getLogger());
//        $scheduleCreator->setAllowedGppMargin(0);
//        $schedules = $scheduleCreator->createFromInput($input);
//        (new ScheduleOutput($this->getLogger()))->output($schedules);
//        (new ScheduleOutput($this->getLogger()))->outputTotals($schedules);
//        $gameCreator = new GameCreator($this->getLogger());
//        $gameCreator->createGames($planning, $schedules);
//        // (new PlanningOutput())->outputWithGames($planning, true);
//
//        self::assertCount(135, $planning->getAgainstGames());
//        $validator = new PlanningValidator();
//        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
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
