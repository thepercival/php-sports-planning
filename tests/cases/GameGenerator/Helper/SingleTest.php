<?php
declare(strict_types=1);

namespace SportsPlanning\Tests\GameGenerator\Helper;

use SportsPlanning\Combinations\GamePlaceStrategy;
use SportsPlanning\GameGenerator;
use SportsPlanning\GameGenerator\AssignedCounter;
use SportsPlanning\Planning\Output as PlanningOutput;
use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Game\Place\Together as TogetherGamePlace;
use SportsPlanning\GameGenerator\Helper\Single as SingleGameGeneratorHelper;
use SportsPlanning\Planning;
use SportsPlanning\TestHelper\PlanningCreator;

class SingleTest extends TestCase
{
    use PlanningCreator;

    public function testSimple(): void
    {
        $sportVariant = $this->getSingleSportVariantWithFields(2, 2, 2);
        $planning = new Planning(
            $this->createInput([7], [$sportVariant]),
            new SportRange(1, 1),
            0);

        $gameGenerator = new SingleGameGeneratorHelper($planning, $this->getLogger());
        $poule = $planning->getInput()->getPoule(1);
        $sports = array_values($planning->getInput()->getSports()->toArray());
        $sportVariants = array_values($planning->getInput()->createSportVariants()->toArray());
        $assignedCounter = new AssignedCounter($poule, $sportVariants);
        $gameGenerator->generate($poule, $sports, $assignedCounter);
        // (new PlanningOutput())->outputWithGames($planning, true);
        self::assertCount(7, $planning->getTogetherGames());
    }

    public function test5Places2GamePlaces1GamePerPlace(): void
    {
        $sportVariant = $this->getSingleSportVariantWithFields(2, 1, 2);
        $planning = new Planning(
            $this->createInput([5], [$sportVariant]),
            new SportRange(1, 1),
            0);

        $gameGenerator = new SingleGameGeneratorHelper($planning, $this->getLogger());
        $poule = $planning->getInput()->getPoule(1);
        $sports = array_values($planning->getInput()->getSports()->toArray());
        $sportVariants = array_values($planning->getInput()->createSportVariants()->toArray());
        $assignedCounter = new AssignedCounter($poule, $sportVariants);
        $gameGenerator->generate($poule, $sports, $assignedCounter);
        // (new PlanningOutput())->outputWithGames($planning, true);
        self::assertCount(3, $planning->getTogetherGames());
    }

    public function test5Places2GamePlaces2GamesPerPlace(): void
    {
        $sportVariant = $this->getSingleSportVariantWithFields(2, 2, 2);
        $planning = new Planning(
            $this->createInput([5], [$sportVariant]),
            new SportRange(1, 1),
            0);

        $gameGenerator = new SingleGameGeneratorHelper($planning, $this->getLogger());
        $poule = $planning->getInput()->getPoule(1);
        $sports = array_values($planning->getInput()->getSports()->toArray());
        $sportVariants = array_values($planning->getInput()->createSportVariants()->toArray());
        $assignedCounter = new AssignedCounter($poule, $sportVariants);
        $gameGenerator->generate($poule, $sports, $assignedCounter);
        // (new PlanningOutput())->outputWithGames($planning, true);
        self::assertCount(5, $planning->getTogetherGames());
    }

    public function test5Places2GamePlaces2GamesPerPlaceRandom(): void
    {
        $sportVariant = $this->getSingleSportVariantWithFields(2, 2, 2);
        $planning = new Planning(
            $this->createInput([5], [$sportVariant], GamePlaceStrategy::RandomlyAssigned),
            new SportRange(1, 1),
            0);

        $gameGenerator = new SingleGameGeneratorHelper($planning, $this->getLogger());
        $poule = $planning->getInput()->getPoule(1);
        $sports = array_values($planning->getInput()->getSports()->toArray());
        $sportVariants = array_values($planning->getInput()->createSportVariants()->toArray());
        $assignedCounter = new AssignedCounter($poule, $sportVariants);
        $gameGenerator->generate($poule, $sports, $assignedCounter);
        // (new PlanningOutput())->outputWithGames($planning, true);
        self::assertCount(4, $planning->getTogetherGames());
    }

    public function testTwoSingleSports(): void
    {
        $singleSport1 = $this->getSingleSportVariantWithFields(2, 1, 2);
        $singleSport2 = $this->getSingleSportVariantWithFields(2, 1, 2);
        $planning = new Planning(
            $this->createInput([5], [$singleSport1,$singleSport2]),
            new SportRange(1, 1),
            0);

//        $getPlacesDescription = function (array $togetherGamePlaces): string {
//            $description = "";
//            foreach ($togetherGamePlaces as $togetherGamePlace) {
//                $description .= $togetherGamePlace->getPlace()->getLocation() . " , ";
//            }
//            return $description;
//        };

        // alle tests zitten ook in de validator, dus een beeteje dubbel om hier
        // ook nog eens alles te testen!!!!
        $gameGenerator = new SingleGameGeneratorHelper($planning, $this->getLogger());
        $poule = $planning->getInput()->getPoule(1);
        $sports = array_values($planning->getInput()->getSports()->toArray());
        $sportVariants = array_values($planning->getInput()->createSportVariants()->toArray());
        $assignedCounter = new AssignedCounter($poule, $sportVariants);
        $gameGenerator->generate($poule, $sports, $assignedCounter);

//        (new PlanningOutput())->outputWithGames($planning, false);
//        $planningOutput->outputWithTotals($planning, false);

        self::assertCount(6, $planning->getTogetherGames());
        // check if GameRoundGenerator should be removed !!!!!!!!!!!!!!!!!
    }

    public function test4Places1GamePlaces1GamesPerPlace(): void
    {
        $sportVariants = [
            $this->getSingleSportVariantWithFields(1, 1, 1),
            $this->getSingleSportVariantWithFields(1, 1, 1)
        ];
        $planning = new Planning(
            $this->createInput([4], $sportVariants),
            new SportRange(1, 1),
            0);

        $gameGenerator = new GameGenerator($this->getLogger());
        $gameGenerator->generateUnassignedGames($planning);

        // (new PlanningOutput())->outputWithGames($planning, true);
        self::assertCount(8, $planning->getTogetherGames());
    }



    public function test3Places2GamePlaces1GamesPerPlace(): void
    {
        $sportVariants = [
            $this->getSingleSportVariantWithFields(1, 1, 2)
        ];
        $planning = new Planning(
            $this->createInput([3], $sportVariants),
            new SportRange(1, 1),
            0);

        $gameGenerator = new GameGenerator($this->getLogger());
        $gameGenerator->generateUnassignedGames($planning);

        // (new PlanningOutput())->outputWithGames($planning, true);

        $place3 = $planning->getInput()->getPoule(1)->getPlace(3);

        $togetherGames = $planning->getTogetherGames();
        self::assertCount(2, $togetherGames);

        $secondGame = $togetherGames->last();
        self::assertInstanceOf(TogetherGame::class, $secondGame);
        $gamePlaces = $secondGame->getPlaces();
        $gamePlace3 = $gamePlaces->first();
        self::assertInstanceOf(TogetherGamePlace::class, $gamePlace3);
        self::assertSame($gamePlace3->getPlace(), $place3);

        self::assertSame(1, $gamePlace3->getGameRoundNumber());
    }
}
