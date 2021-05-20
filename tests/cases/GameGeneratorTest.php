<?php
declare(strict_types=1);

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\GameGenerator;
use SportsPlanning\Planning;
use SportsPlanning\Planning\GameCreator;
use SportsPlanning\Planning\Output as PlanningOutput;
use SportsPlanning\TestHelper\PlanningCreator;

class GameGeneratorTest extends TestCase
{
    use PlanningCreator;

    public function testGameInstanceAgainst(): void
    {
        $planning = $this->createPlanning(
            $this->createInput([2], null, 0)
        );
        $gameGenerator = new GameGenerator();
        $gameGenerator->generateUnassignedGames($planning);
        $games = $planning->getGames();
        self::assertInstanceOf(AgainstGame::class, reset($games));
    }

    public function testGameInstanceTogether(): void
    {
        $singleSportVariantWithFields = $this->getSingleSportVariantWithFields(2);
        $planning = $this->createPlanning(
            $this->createInput([2], [$singleSportVariantWithFields], 0)
        );

        $gameGenerator = new GameGenerator();
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

    public function testAgainstMixed(): void
    {
        $sportVariants = [
            $this->getAgainstSportVariantWithFields(1, 2, 2, 0, 3),
        ];
        $planning = new Planning($this->createInput([5], $sportVariants), new SportRange(1, 1), 4);

//        $gameGenerator = new GameGenerator();
//        $gameGenerator->disableThrowOnTimeout();
//        $gameGenerator->generateUnassignedGames($planning);
//        (new PlanningOutput())->outputWithGames($planning, true);

        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createAssignedGames($planning);
//
         // (new PlanningOutput())->outputWithGames($planning, true);
//
        self::assertEquals(Planning::STATE_SUCCEEDED, $planning->getState());
    }
}
