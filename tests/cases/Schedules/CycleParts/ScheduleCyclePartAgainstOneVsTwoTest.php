<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Schedules\CycleParts;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Against\AgainstSide;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\Schedules\CycleParts\ScheduleCyclePartAgainstOneVsTwo;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstOneVsTwo;
use SportsPlanning\Schedules\GamePlaces\ScheduleGamePlaceAgainst;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstOneVsTwo;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;
use SportsPlanning\Schedules\Sports\ScheduleAgainstOneVsTwo;
use SportsPlanning\Sports\SportWithNrOfCycles;

final class ScheduleCyclePartAgainstOneVsTwoTest extends TestCase
{
    public function testCyclePartNr(): void
    {
        $sportsWithNrOfCycles = [ new SportWithNrOfCycles(new AgainstOneVsTwo(), 1)];
        $scheduleWithNrOfPlaces = new ScheduleWithNrOfPlaces(5, $sportsWithNrOfCycles);
        $sportSchedule = $scheduleWithNrOfPlaces->getSportSchedule(1);
        self::assertInstanceOf(ScheduleAgainstOneVsTwo::class, $sportSchedule);

        $cycle = new ScheduleCycleAgainstOneVsTwo($sportSchedule);
        $cyclePart = new ScheduleCyclePartAgainstOneVsTwo($cycle);

        self::assertSame(1, $cyclePart->getNumber() );
    }

    public function testIsParticipating(): void
    {
        $sportsWithNrOfCycles = [ new SportWithNrOfCycles(new AgainstOneVsTwo(), 1)];
        $scheduleWithNrOfPlaces = new ScheduleWithNrOfPlaces(5, $sportsWithNrOfCycles);
        $sportSchedule = $scheduleWithNrOfPlaces->getSportSchedule(1);
        self::assertInstanceOf(ScheduleAgainstOneVsTwo::class, $sportSchedule);

        $cycle = new ScheduleCycleAgainstOneVsTwo($sportSchedule);
        $cyclePart = new ScheduleCyclePartAgainstOneVsTwo($cycle);

        self::assertFalse($cyclePart->isParticipating(1));
    }

    public function testCreateNext(): void
    {
        $sportsWithNrOfCycles = [ new SportWithNrOfCycles(new AgainstOneVsTwo(), 1)];
        $scheduleWithNrOfPlaces = new ScheduleWithNrOfPlaces(5, $sportsWithNrOfCycles);
        $sportSchedule = $scheduleWithNrOfPlaces->getSportSchedule(1);
        self::assertInstanceOf(ScheduleAgainstOneVsTwo::class, $sportSchedule);

        $cycle = new ScheduleCycleAgainstOneVsTwo($sportSchedule);
        $cyclePart = new ScheduleCyclePartAgainstOneVsTwo($cycle);
        self::assertInstanceOf(ScheduleCyclePartAgainstOneVsTwo::class, $cyclePart->createNext());
    }

    public function testAdd(): void
    {
        $sportsWithNrOfCycles = [ new SportWithNrOfCycles(new AgainstOneVsTwo(), 1)];
        $scheduleWithNrOfPlaces = new ScheduleWithNrOfPlaces(4, $sportsWithNrOfCycles);
        $sportSchedule = $scheduleWithNrOfPlaces->getSportSchedule(1);
        self::assertInstanceOf(ScheduleAgainstOneVsTwo::class, $sportSchedule);

        $cycle = new ScheduleCycleAgainstOneVsTwo($sportSchedule);
        $cyclePart = new ScheduleCyclePartAgainstOneVsTwo($cycle);

        $this->addOneVsTwo($cyclePart, 1, new DuoPlaceNr(2,3));
        self::assertCount(1, $cyclePart->getGamesAsHomeAways());
    }

    public function testAddException(): void
    {
        $sportsWithNrOfCycles = [ new SportWithNrOfCycles(new AgainstOneVsTwo(), 1)];
        $scheduleWithNrOfPlaces = new ScheduleWithNrOfPlaces(4, $sportsWithNrOfCycles);
        $sportSchedule = $scheduleWithNrOfPlaces->getSportSchedule(1);
        self::assertInstanceOf(ScheduleAgainstOneVsTwo::class, $sportSchedule);

        $cycle = new ScheduleCycleAgainstOneVsTwo($sportSchedule);
        $cyclePart = new ScheduleCyclePartAgainstOneVsTwo($cycle);

        $this->addOneVsTwo($cyclePart, 1, new DuoPlaceNr(2,3));

        $game = new ScheduleGameAgainstOneVsTwo($cyclePart);
        new ScheduleGamePlaceAgainst($game, AgainstSide::Home, 1);
        new ScheduleGamePlaceAgainst($game, AgainstSide::Away, 2);
        new ScheduleGamePlaceAgainst($game, AgainstSide::Away, 4);
        self::expectException(\Exception::class);
        $cyclePart->addGame($game);
    }


//    public function testSwapSidesOfHomeAwayNew(): void
//    {
//        $gameRound = new AgainstGameRound(4);
//        $gameRound->add(new OneVsTwoHomeAway(1,2));
//        $gameRound->swapSidesOfHomeAway(new OneVsTwoHomeAway(2,1));
//
//        $homeAways = $gameRound->getHomeAways();
//        $homeAway = $homeAways[0];
//        self::assertInstanceOf(OneVsTwoHomeAway::class, $homeAway);
//        self::assertSame(2, $homeAway->getHome());
//        self::assertSame(1, $homeAway->getAway());
//    }

    public function testIsSomeHomeAwayPlaceNrParticipating(): void
    {
        $sportsWithNrOfCycles = [ new SportWithNrOfCycles(new AgainstOneVsTwo(), 1)];
        $scheduleWithNrOfPlaces = new ScheduleWithNrOfPlaces(5, $sportsWithNrOfCycles);
        $sportSchedule = $scheduleWithNrOfPlaces->getSportSchedule(1);
        self::assertInstanceOf(ScheduleAgainstOneVsTwo::class, $sportSchedule);

        $cycle = new ScheduleCycleAgainstOneVsTwo($sportSchedule);
        $cyclePart = new ScheduleCyclePartAgainstOneVsTwo($cycle);
        $this->addOneVsTwo($cyclePart, 1, new DuoPlaceNr(2,3));

        self::assertTrue($cyclePart->isSomeHomeAwayPlaceNrParticipating(new OneVsOneHomeAway(1,2)));
        self::assertTrue($cyclePart->isSomeHomeAwayPlaceNrParticipating(new OneVsOneHomeAway(2,3)));
        self::assertTrue($cyclePart->isSomeHomeAwayPlaceNrParticipating(new OneVsOneHomeAway(3,2)));
        self::assertTrue($cyclePart->isSomeHomeAwayPlaceNrParticipating(new OneVsOneHomeAway(1,3)));
        self::assertTrue($cyclePart->isSomeHomeAwayPlaceNrParticipating(new OneVsOneHomeAway(3,1)));
        self::assertFalse($cyclePart->isSomeHomeAwayPlaceNrParticipating(new OneVsOneHomeAway(4,5)));
    }

    public function testGetSelfAndAllPreviousNrOfHomeAways(): void
    {
        $sportsWithNrOfCycles = [ new SportWithNrOfCycles(new AgainstOneVsTwo(), 1)];
        $scheduleWithNrOfPlaces = new ScheduleWithNrOfPlaces(6, $sportsWithNrOfCycles);
        $sportSchedule = $scheduleWithNrOfPlaces->getSportSchedule(1);
        self::assertInstanceOf(ScheduleAgainstOneVsTwo::class, $sportSchedule);

        $cycle = new ScheduleCycleAgainstOneVsTwo($sportSchedule);

        $cyclePartOne = new ScheduleCyclePartAgainstOneVsTwo($cycle);

        $this->addOneVsTwo($cyclePartOne, 1, new DuoPlaceNr(2,3));
        $this->addOneVsTwo($cyclePartOne, 4, new DuoPlaceNr(5,6));

        $cyclePartTwo = $cyclePartOne->createNext();
        $this->addOneVsTwo($cyclePartTwo, 2, new DuoPlaceNr(1,3));
        $this->addOneVsTwo($cyclePartTwo, 5, new DuoPlaceNr(4,6));

        self::assertSame(2, $cyclePartOne->getSelfAndAllPreviousNrOfHomeAways());
        self::assertSame(4, $cyclePartTwo->getSelfAndAllPreviousNrOfHomeAways());
    }

//    public function testGetAllHomeAways(): void
//    {
//        $nrOfPlaces = 4;
//        $cycle = new ScheduleCycleAgainst($nrOfPlaces);
//
//        $cyclePartOne = new ScheduleCyclePartAgainst($cycle);
//        $this->addOneVsTwo($cyclePartOne, 1, 2);
//        $this->addOneVsTwo($cyclePartOne, 3, 4);
//
//        $cyclePartTwo = $cyclePartOne->createNext();
//        $this->addOneVsTwo($cyclePartTwo, 1, 3);
//        $this->addOneVsTwo($cyclePartTwo, 2, 4);
//
//        $cyclePartThree = $cyclePartTwo->createNext();
//        $this->addOneVsTwo($cyclePartThree, 1, 4);
//        $this->addOneVsTwo($cyclePartThree, 2, 3);
//    }

    private function addOneVsTwo(
        ScheduleCyclePartAgainstOneVsTwo $cyclePart,
        int                              $homePlaceNr,
        DuoPlaceNr                       $awayDuoPlace ): ScheduleGameAgainstOneVsTwo{
        $game = new ScheduleGameAgainstOneVsTwo($cyclePart);
        new ScheduleGamePlaceAgainst($game, AgainstSide::Home, $homePlaceNr);
        new ScheduleGamePlaceAgainst($game, AgainstSide::Away, $awayDuoPlace->placeNrOne);
        new ScheduleGamePlaceAgainst($game, AgainstSide::Away, $awayDuoPlace->placeNrTwo);
        $cyclePart->addGame($game);
        return $game;
    }
}
