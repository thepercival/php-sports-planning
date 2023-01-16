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
use SportsPlanning\Planning\Validator as PlanningValidator;
use SportsPlanning\Schedule\Creator as ScheduleCreator;
use SportsPlanning\TestHelper\PlanningCreator;

class H2hTest extends TestCase
{
    use PlanningCreator;

    public function test1V1Places2H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([2], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(1, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    protected function getLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler('php://stdout', Logger::INFO);
        $logger->pushHandler($handler);
        return $logger;
    }

    public function test1V1Places3H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([3], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
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
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([4], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
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
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([5], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
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
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([6], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(15, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test1VS1Places15H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([15], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(105, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test1VS1Places16H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([16], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(120, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test1VS1Places17H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([17], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(136, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test1VS1Places18H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([18], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(153, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test1VS1Places19H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([19], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(171, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test1VS1Places20H2H1(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1),
        ];
        $input = $this->createInput([20], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(190, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function test1V1Places4H2H2(): void
    {
        $sportVariants = [
            $this->getAgainstH2hSportVariantWithFields(1, 1, 1, 2),
        ];
        $input = $this->createInput([4], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);

//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(12, $planning->getAgainstGames());
        $validator = new PlanningValidator();
        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }
}
