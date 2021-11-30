<?php
declare(strict_types=1);

namespace SportsPlanning\Tests\Schedule\Creator;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SportsHelpers\SportRange;
use SportsPlanning\Game\Creator as GameCreator;
use SportsPlanning\Planning;
use SportsPlanning\Planning\Validator as PlanningValidator;
use SportsPlanning\Schedule\Creator\Service as ScheduleCreatorService;
use SportsPlanning\TestHelper\PlanningCreator;

class AgainstTest extends TestCase
{
    use PlanningCreator;

    public function test1V1Places2H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 1, 1, 1, 0),
        ];
        $input = $this->createInput([2], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
        $schedules = $scheduleCreatorService->createSchedules($input);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(1, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test1V1Places3H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 1, 1, 1, 0),
        ];
        $input = $this->createInput([3], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
        $schedules = $scheduleCreatorService->createSchedules($input);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(3, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test1V1Places4H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 1, 1, 1, 0),
        ];
        $input = $this->createInput([4], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
        $schedules = $scheduleCreatorService->createSchedules($input);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(6, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test1V1Places5H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 1, 1, 1, 0),
        ];
        $input = $this->createInput([5], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
        $schedules = $scheduleCreatorService->createSchedules($input);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(10, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test1V1Places6H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 1, 1, 1, 0),
        ];
        $input = $this->createInput([6], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
        $schedules = $scheduleCreatorService->createSchedules($input);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(15, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }


    public function test2V2Places4GamesPerPlace1(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 1 /* max = 2 */),
        ];
        $input = $this->createInput([4], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
        $schedules = $scheduleCreatorService->createSchedules($input);
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
            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 2 /* max = 2 */),
        ];
        $input = $this->createInput([4], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
        $schedules = $scheduleCreatorService->createSchedules($input);
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
            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 3 /* max = 2 */),
        ];
        $input = $this->createInput([4], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
        $schedules = $scheduleCreatorService->createSchedules($input);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(3, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test2V2Places4GamesPerPlace4(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 4 /* max = 2 */),
        ];
        $input = $this->createInput([4], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
        $schedules = $scheduleCreatorService->createSchedules($input);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(4, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test2V2Places5GamesPerPlace1(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 1),
        ];
        $input = $this->createInput([5], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
        $schedules = $scheduleCreatorService->createSchedules($input);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(2, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test2VS2Places5GamesPerPlace12(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 12),
        ];
        $input = $this->createInput([5], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
        $schedules = $scheduleCreatorService->createSchedules($input);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(15, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test2VS2Places6GamesPerPlace30(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 30),
        ];
        $input = $this->createInput([6], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
        $schedules = $scheduleCreatorService->createSchedules($input);
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
//        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
//        $schedules = $scheduleCreatorService->createSchedules($input);
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

    public function test1VS2Places3GamesPerPlace3(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 1, 2, 0, 3),
        ];
        $input = $this->createInput([3], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
        $schedules = $scheduleCreatorService->createSchedules($input);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(3, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test1VS2Places2GamesPerPlace1(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 1, 2, 0, 1),
        ];
        self::expectException(\Exception::class);
        new Planning($this->createInput([2], $sportVariants), new SportRange(1, 1), 0);
    }

    public function test1VS1Places4Sports2(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 1, 1, 1, 0),
            $this->getAgainstSportVariantWithFields(1, 1, 1, 1, 0),
        ];
        $input = $this->createInput([4], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
        $schedules = $scheduleCreatorService->createSchedules($input);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);

        self::assertCount(12, $planning->getAgainstGames());
    }

    public function test2Sports1UnequallyAssigned(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 1),
            $this->getAgainstSportVariantWithFields(1, 1, 1, 1, 0),
        ];
        $input = $this->createInput([5], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
        $schedules = $scheduleCreatorService->createSchedules($input);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);

        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(12, $planning->getAgainstGames());
    }




    // commented for performance reasons
//    public function test2VS2Places10GamesPerPlace50(): void
//    {
//        $sportVariants = [
//            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 50),
//        ];
//        $input = $this->createInput([10], $sportVariants);
//        $planning = new Planning($input, new SportRange(1, 1), 0);
//
//        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
//        $schedules = $scheduleCreatorService->createSchedules($input);
//        $gameCreator = new GameCreator($this->getLogger());
//        $gameCreator->createGames($planning, $schedules);
//
//        (new PlanningOutput())->outputWithGames($planning, true);
//
//        self::assertCount(125, $planning->getAgainstGames());
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
