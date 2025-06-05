<?php

namespace SportsPlanning\Tests\Sports\SportWithNrOfPlaces;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\PlanningOrchestration;
use SportsPlanning\Referee\PlanningRefereeInfo;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;
use SportsPlanning\Schedules\Sports\ScheduleAgainstOneVsOne;
use SportsPlanning\Sports\SportsWithNrAndFields\TogetherSportWithNrAndFields;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsPlanning\Sports\SportWithNrOfPlaces\AgainstTwoVsTwoWithNrOfPlaces;
use SportsPlanning\TestHelper\PlanningCreator;

 final class AgainstTwoVsTwoWithNrOfPlacesTest extends TestCase
{
    use PlanningCreator;

    public function testCalculateNrOfGamesPerPlace(): void {
        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(4, new AgainstTwoVsTwo());
        self::assertSame(3, $sportWithNrOfPlaces->calculateNrOfGamesPerPlace(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(5, new AgainstTwoVsTwo());
        self::assertSame(4, $sportWithNrOfPlaces->calculateNrOfGamesPerPlace(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(6, new AgainstTwoVsTwo());
        self::assertSame(5, $sportWithNrOfPlaces->calculateNrOfGamesPerPlace(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(7, new AgainstTwoVsTwo());
        self::assertSame(4, $sportWithNrOfPlaces->calculateNrOfGamesPerPlace(1));
    }

    public function testCalculateNrOfGames(): void {
        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(4, new AgainstTwoVsTwo());
        self::assertSame(3, $sportWithNrOfPlaces->calculateNrOfGames(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(5, new AgainstTwoVsTwo());
        self::assertSame(5, $sportWithNrOfPlaces->calculateNrOfGames(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(6, new AgainstTwoVsTwo());
        self::assertSame(7, $sportWithNrOfPlaces->calculateNrOfGames(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(7, new AgainstTwoVsTwo());
        self::assertSame(7, $sportWithNrOfPlaces->calculateNrOfGames(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(8, new AgainstTwoVsTwo());
        self::assertSame(14, $sportWithNrOfPlaces->calculateNrOfGames(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(9, new AgainstTwoVsTwo());
        self::assertSame(18, $sportWithNrOfPlaces->calculateNrOfGames(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(10, new AgainstTwoVsTwo());
        self::assertSame(22, $sportWithNrOfPlaces->calculateNrOfGames(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(11, new AgainstTwoVsTwo());
        self::assertSame(22, $sportWithNrOfPlaces->calculateNrOfGames(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(12, new AgainstTwoVsTwo());
        self::assertSame(33, $sportWithNrOfPlaces->calculateNrOfGames(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(13, new AgainstTwoVsTwo());
        self::assertSame(39, $sportWithNrOfPlaces->calculateNrOfGames(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(14, new AgainstTwoVsTwo());
        self::assertSame(45, $sportWithNrOfPlaces->calculateNrOfGames(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(15, new AgainstTwoVsTwo());
        self::assertSame(45, $sportWithNrOfPlaces->calculateNrOfGames(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(16, new AgainstTwoVsTwo());
        self::assertSame(60, $sportWithNrOfPlaces->calculateNrOfGames(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(17, new AgainstTwoVsTwo());
        self::assertSame(68, $sportWithNrOfPlaces->calculateNrOfGames(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(18, new AgainstTwoVsTwo());
        self::assertSame(76, $sportWithNrOfPlaces->calculateNrOfGames(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(19, new AgainstTwoVsTwo());
        self::assertSame(76, $sportWithNrOfPlaces->calculateNrOfGames(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(20, new AgainstTwoVsTwo());
        self::assertSame(95, $sportWithNrOfPlaces->calculateNrOfGames(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(21, new AgainstTwoVsTwo());
        self::assertSame(105, $sportWithNrOfPlaces->calculateNrOfGames(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(22, new AgainstTwoVsTwo());
        self::assertSame(115, $sportWithNrOfPlaces->calculateNrOfGames(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(23, new AgainstTwoVsTwo());
        self::assertSame(115, $sportWithNrOfPlaces->calculateNrOfGames(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(24, new AgainstTwoVsTwo());
        self::assertSame(138, $sportWithNrOfPlaces->calculateNrOfGames(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(25, new AgainstTwoVsTwo());
        self::assertSame(150, $sportWithNrOfPlaces->calculateNrOfGames(1));

        $sportWithNrOfPlaces = new AgainstTwoVsTwoWithNrOfPlaces(26, new AgainstTwoVsTwo());
        self::assertSame(162, $sportWithNrOfPlaces->calculateNrOfGames(1));
    }
}
