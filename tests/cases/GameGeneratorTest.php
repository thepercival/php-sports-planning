<?php
declare(strict_types=1);

namespace SportsPlanning\Tests;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SportsHelpers\SportRange;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\GameGenerator;
use SportsPlanning\Planning;
use SportsPlanning\Planning\GameCreator;
use SportsPlanning\Planning\Output as PlanningOutput;
use SportsPlanning\Planning\Validator as PlanningValidator;
use SportsPlanning\TestHelper\PlanningCreator;

class GameGeneratorTest extends TestCase
{
    use PlanningCreator;

    public function testGameInstanceAgainst(): void
    {
        $planning = $this->createPlanning(
            $this->createInput([2], null, null, 0)
        );
        $gameGenerator = new GameGenerator($this->getLogger());
        $gameGenerator->generateUnassignedGames($planning);
        $games = $planning->getGames();
        self::assertInstanceOf(AgainstGame::class, reset($games));
    }

    public function testGameInstanceTogether(): void
    {
        $singleSportVariantWithFields = $this->getSingleSportVariantWithFields(2);
        $planning = $this->createPlanning(
            $this->createInput([2], [$singleSportVariantWithFields], null, 0)
        );

        $gameGenerator = new GameGenerator($this->getLogger());
        $gameGenerator->generateUnassignedGames($planning);
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
        $planning = new Planning($this->createInput([5], $sportVariants), new SportRange(1, 1), 0);

//        $gameGenerator = new GameGenerator();
//        $gameGenerator->generateUnassignedGames($planning);
//        (new PlanningOutput())->outputWithGames($planning, true);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createAssignedGames($planning);

        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertEquals(Planning::STATE_SUCCEEDED, $planning->getState());
    }

    public function testAgainst(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(2, 1, 1, 2),
        ];
        $planning = new Planning($this->createInput([5], $sportVariants), new SportRange(2, 2), 0);

//        $gameGenerator = new GameGenerator();
//        $gameGenerator->generateUnassignedGames($planning);
//        (new PlanningOutput())->outputWithGames($planning, true);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createAssignedGames($planning);

        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertEquals(Planning::STATE_SUCCEEDED, $planning->getState());
    }

    // [3]-against : 1vs1 : h2h-nrofgamesperplace => 2-0 f(1)-strat=>eql-ref(0:), batchGames 1->1, gamesInARow 2
    public function testAgainstH2H2(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 1, 1, 2),
        ];
        $planning = new Planning($this->createInput([3], $sportVariants, null, 0), new SportRange(1, 1), 0);

        $gameGenerator = new GameGenerator($this->getLogger());
        $gameGenerator->generateUnassignedGames($planning);
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
        $planning = new Planning($this->createInput([5], $sportVariants), new SportRange(1, 1), 4);

//        $gameGenerator = new GameGenerator($this->getLogger());
//        $gameGenerator->generateUnassignedGames($planning);
//        (new PlanningOutput())->outputWithGames($planning, true);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createAssignedGames($planning);
//
         // (new PlanningOutput())->outputWithGames($planning, true);
//
        self::assertEquals(Planning::STATE_SUCCEEDED, $planning->getState());
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
