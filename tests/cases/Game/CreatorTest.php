<?php
declare(strict_types=1);

namespace SportsPlanning\Tests\Game;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SportsHelpers\SportRange;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Schedule\Creator\Service as ScheduleCreatorService;
use SportsPlanning\Planning;
use SportsPlanning\Game\Creator as GameCreator;
use SportsPlanning\Game\Assigner as GameAssigner;
use SportsPlanning\Planning\Output as PlanningOutput;
use SportsPlanning\Planning\Validator as PlanningValidator;
use SportsPlanning\TestHelper\PlanningCreator;

class CreatorTest extends TestCase
{
    use PlanningCreator;

    public function testGameInstanceAgainst(): void
    {
        $input = $this->createInput([2], null, null, 0);
        $planning = $this->createPlanning($input);
        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
        $schedules = $scheduleCreatorService->createSchedules($input);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);

        $games = $planning->getGames();
        self::assertInstanceOf(AgainstGame::class, reset($games));
    }

    public function testGameInstanceTogether(): void
    {
        $singleSportVariantWithFields = $this->getSingleSportVariantWithFields(2);
        $input = $this->createInput([2], [$singleSportVariantWithFields], null, 0);
        $planning = $this->createPlanning($input);

//        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
//        $schedules = $scheduleCreatorService->createSchedules($input);
//
//        $gameCreator = new GameCreator($this->getLogger());
//        $gameCreator->createGames($planning, $schedules);

        $games = $planning->getGames();
        self::assertInstanceOf(TogetherGame::class, reset($games));
    }

    public function testMixedGameModes(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(2, 2, 2, 0, 3),
            $this->getSingleSportVariantWithFields(2, 4, 2),
        ];

        $planning = $this->createPlanning($this->createInput([4], $sportVariants));
        self::assertCount(3, $planning->getAgainstGames());
        self::assertCount(8, $planning->getTogetherGames());
    }

    public function testAgainstBasic(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 1, 1, 1, 0),
        ];
        $input = $this->createInput([5], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

//        $gameGenerator = new GameGenerator();
//        $gameGenerator->generateUnassignedGames($planning);
//        (new PlanningOutput())->outputWithGames($planning, true);

        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
        $schedules = $scheduleCreatorService->createSchedules($input);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);

        $gameAssigner = new GameAssigner($this->getLogger());
        $gameAssigner->assignGames($planning);

        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertEquals(Planning::STATE_SUCCEEDED, $planning->getState());
    }

    public function testAgainst(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(2, 1, 1, 2),
        ];
        $input = $this->createInput([5], $sportVariants);
        $planning = new Planning($input, new SportRange(2, 2), 0);

//        $gameGenerator = new GameGenerator();
//        $gameGenerator->generateUnassignedGames($planning);
//        (new PlanningOutput())->outputWithGames($planning, true);

        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
        $schedules = $scheduleCreatorService->createSchedules($input);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);

        $gameAssigner = new GameAssigner($this->getLogger());
        $gameAssigner->assignGames($planning);

        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertEquals(Planning::STATE_SUCCEEDED, $planning->getState());
    }

    // [3]-against : 1vs1 : h2h-nrofgamesperplace => 2-0 f(1)-strat=>eql-ref(0:), batchGames 1->1, gamesInARow 2
    public function testAgainstH2H2(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 1, 1, 2),
        ];
        $input = $this->createInput([3], $sportVariants, null, 0);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
        $schedules = $scheduleCreatorService->createSchedules($input);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);

        // (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning, true);
        self::assertSame(PlanningValidator::VALID, $validity);
    }


    public function testAgainstMixed(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 3),
        ];
        $input = $this->createInput([5], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 4);

//        $gameGenerator = new GameGenerator($this->getLogger());
//        $gameGenerator->generateUnassignedGames($planning);
//        (new PlanningOutput())->outputWithGames($planning, true);

        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
        $schedules = $scheduleCreatorService->createSchedules($input);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);

        $gameAssigner = new GameAssigner($this->getLogger());
        $gameAssigner->assignGames($planning);
//
        // (new PlanningOutput())->outputWithGames($planning, true);
//
        self::assertEquals(Planning::STATE_SUCCEEDED, $planning->getState());
    }

    public function test1Poule12Places(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(6, 1, 1, 1),
        ];
        $input = $this->createInput([14], $sportVariants);
        $planning = new Planning($input, new SportRange(6, 6), 0);

        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
        $schedules = $scheduleCreatorService->createSchedules($input);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);

//        (new PlanningOutput())->outputWithGames($planning, true);
//        (new PlanningOutput())->outputWithTotals($planning, false);

        $validator = new PlanningValidator();

        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
    }

    public function testSportSingle2Poules6Places(): void
    {
        // [6,6] - [single(4) gpp=>2 f(2)] - gpstrat=>eql - ref=>0:
        $sportVariants = [
            $this->getSingleSportVariantWithFields(2, 2, 4),
        ];
        $input = $this->createInput([6,6], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 2), 0);

        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
        $schedules = $scheduleCreatorService->createSchedules($input);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);

//        (new PlanningOutput())->outputWithGames($planning, true);
//        (new PlanningOutput())->outputWithTotals($planning, false);

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
}