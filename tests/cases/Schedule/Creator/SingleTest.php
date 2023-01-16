<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Schedule\Creator;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsPlanning\Game\Creator as GameCreator;
use SportsPlanning\Game\Place\Together as TogetherGamePlace;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Planning;
use SportsPlanning\Schedule\Creator as ScheduleCreator;
use SportsPlanning\TestHelper\PlanningCreator;

class SingleTest extends TestCase
{
    use PlanningCreator;

    public function testSimple(): void
    {
        $sportVariant = $this->getSingleSportVariantWithFields(2, 2, 2);
        $input = $this->createInput([7], [$sportVariant]);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        // (new ScheduleOutput($this->getLogger()))->output($schedules);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(7, $planning->getTogetherGames());
    }

    public function test5Places2GamePlaces1GamePerPlace(): void
    {
        $sportVariant = $this->getSingleSportVariantWithFields(2, 1, 2);
        $input = $this->createInput([5], [$sportVariant]);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(3, $planning->getTogetherGames());
    }

    public function test5Places2GamePlaces2GamesPerPlace(): void
    {
        $sportVariant = $this->getSingleSportVariantWithFields(2, 2, 2);
        $input = $this->createInput([5], [$sportVariant]);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(5, $planning->getTogetherGames());
    }

    public function test5Places2GamePlaces2GamesPerPlaceRandom(): void
    {
        $sportVariant = $this->getSingleSportVariantWithFields(2, 2, 2);
        $input = $this->createInput([5], [$sportVariant]);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(5, $planning->getTogetherGames());
    }

    public function testTwoSingleSports(): void
    {
        $singleSport1 = $this->getSingleSportVariantWithFields(2, 1, 2);
        $singleSport2 = $this->getSingleSportVariantWithFields(2, 1, 2);
        $input = $this->createInput([5], [$singleSport1, $singleSport2]);
        $planning = new Planning($input, new SportRange(1, 1), 0);

//        $getPlacesDescription = function (array $togetherGamePlaces): string {
//            $description = "";
//            foreach ($togetherGamePlaces as $togetherGamePlace) {
//                $description .= $togetherGamePlace->getPlace()->getLocation() . " , ";
//            }
//            return $description;
//        };

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);
//        (new PlanningOutput())->outputWithTotals($planning, false);

        self::assertCount(6, $planning->getTogetherGames());
        // check if GameRoundGenerator should be removed !!!!!!!!!!!!!!!!!
    }

    public function test4Places1GamePlaces1GamesPerPlace(): void
    {
        $sportVariants = [
            $this->getSingleSportVariantWithFields(1, 1, 1),
            $this->getSingleSportVariantWithFields(1, 1, 1)
        ];
        $input = $this->createInput([4], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(8, $planning->getTogetherGames());
    }



    public function test3Places2GamePlaces1GamesPerPlace(): void
    {
        $sportVariants = [$this->getSingleSportVariantWithFields(1, 1, 2)];
        $input = $this->createInput([3], $sportVariants);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $maxGppMargin = $scheduleCreator->getMaxGppMargin($input, $input->getPoule(1));
        $schedules = $scheduleCreator->createFromInput($input, $maxGppMargin);
        // (new ScheduleOutput($this->getLogger()))->output($schedules);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        $place3 = $input->getPoule(1)->getPlace(3);

        $togetherGames = $planning->getTogetherGames();
        self::assertCount(2, $togetherGames);

        $secondGame = $togetherGames->last();
        self::assertInstanceOf(TogetherGame::class, $secondGame);
        $secondGameGamePlaces = $secondGame->getPlaces();
        $secondGameOnlyGamePlace = $secondGameGamePlaces->first();
        self::assertInstanceOf(TogetherGamePlace::class, $secondGameOnlyGamePlace);
        self::assertSame($secondGameOnlyGamePlace->getPlace(), $place3);

        self::assertSame(1, $secondGameOnlyGamePlace->getGameRoundNumber());
    }
}
