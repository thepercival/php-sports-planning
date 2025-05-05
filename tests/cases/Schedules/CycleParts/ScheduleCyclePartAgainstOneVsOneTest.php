<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Schedules\CycleParts;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Against\AgainstSide;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\Schedules\CycleParts\ScheduleCyclePartAgainstOneVsOne;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainstOneVsOne;
use SportsPlanning\Schedules\GamePlaces\ScheduleGamePlaceAgainst;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstOneVsOne;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;
use SportsPlanning\Schedules\Sports\ScheduleAgainstOneVsOne;
use SportsPlanning\Sports\SportWithNrOfCycles;

class ScheduleCyclePartAgainstOneVsOneTest extends TestCase
{
    public function testCyclePartNr(): void
    {
        $sportsWithNrOfCycles = [ new SportWithNrOfCycles(new AgainstOneVsOne(), 1)];
        $scheduleWithNrOfPlaces = new ScheduleWithNrOfPlaces(5, $sportsWithNrOfCycles);
        $sportSchedule = $scheduleWithNrOfPlaces->getSportSchedule(1);
        self::assertInstanceOf(ScheduleAgainstOneVsOne::class, $sportSchedule);

        $cycle = new ScheduleCycleAgainstOneVsOne($sportSchedule);
        $cyclePart = new ScheduleCyclePartAgainstOneVsOne($cycle);

        self::assertSame(1, $cyclePart->getNumber() );
    }

    public function testIsParticipating(): void
    {
        $sportsWithNrOfCycles = [ new SportWithNrOfCycles(new AgainstOneVsOne(), 1)];
        $scheduleWithNrOfPlaces = new ScheduleWithNrOfPlaces(5, $sportsWithNrOfCycles);
        $sportSchedule = $scheduleWithNrOfPlaces->getSportSchedule(1);
        self::assertInstanceOf(ScheduleAgainstOneVsOne::class, $sportSchedule);

        $cycle = new ScheduleCycleAgainstOneVsOne($sportSchedule);
        $cyclePart = new ScheduleCyclePartAgainstOneVsOne($cycle);

        self::assertFalse($cyclePart->isParticipating(1));
    }

    public function testCreateNext(): void
    {
        $sportsWithNrOfCycles = [ new SportWithNrOfCycles(new AgainstOneVsOne(), 1)];
        $scheduleWithNrOfPlaces = new ScheduleWithNrOfPlaces(5, $sportsWithNrOfCycles);
        $sportSchedule = $scheduleWithNrOfPlaces->getSportSchedule(1);
        self::assertInstanceOf(ScheduleAgainstOneVsOne::class, $sportSchedule);

        $cycle = new ScheduleCycleAgainstOneVsOne($sportSchedule);
        $cyclePart = new ScheduleCyclePartAgainstOneVsOne($cycle);
        self::assertInstanceOf(ScheduleCyclePartAgainstOneVsOne::class, $cyclePart->createNext());
    }

    public function testAdd(): void
    {
        $sportsWithNrOfCycles = [ new SportWithNrOfCycles(new AgainstOneVsOne(), 1)];
        $scheduleWithNrOfPlaces = new ScheduleWithNrOfPlaces(4, $sportsWithNrOfCycles);
        $sportSchedule = $scheduleWithNrOfPlaces->getSportSchedule(1);
        self::assertInstanceOf(ScheduleAgainstOneVsOne::class, $sportSchedule);

        $cycle = new ScheduleCycleAgainstOneVsOne($sportSchedule);
        $cyclePart = new ScheduleCyclePartAgainstOneVsOne($cycle);

        $this->addOneVsOne($cyclePart, 1, 2);
        self::assertCount(1, $cyclePart->getGamesAsHomeAways());
    }

    public function testAddException(): void
    {
        $sportsWithNrOfCycles = [ new SportWithNrOfCycles(new AgainstOneVsOne(), 1)];
        $scheduleWithNrOfPlaces = new ScheduleWithNrOfPlaces(4, $sportsWithNrOfCycles);
        $sportSchedule = $scheduleWithNrOfPlaces->getSportSchedule(1);
        self::assertInstanceOf(ScheduleAgainstOneVsOne::class, $sportSchedule);

        $cycle = new ScheduleCycleAgainstOneVsOne($sportSchedule);
        $cyclePart = new ScheduleCyclePartAgainstOneVsOne($cycle);

        $this->addOneVsOne($cyclePart, 1, 2);

        $game = new ScheduleGameAgainstOneVsOne($cyclePart);
        new ScheduleGamePlaceAgainst($game, AgainstSide::Home, 2);
        new ScheduleGamePlaceAgainst($game, AgainstSide::Away, 3);
        self::expectException(\Exception::class);
        $cyclePart->addGame($game);
    }


//    public function testSwapSidesOfHomeAwayNew(): void
//    {
//        $gameRound = new AgainstGameRound(4);
//        $gameRound->add(new OneVsOneHomeAway(1,2));
//        $gameRound->swapSidesOfHomeAway(new OneVsOneHomeAway(2,1));
//
//        $homeAways = $gameRound->getHomeAways();
//        $homeAway = $homeAways[0];
//        self::assertInstanceOf(OneVsOneHomeAway::class, $homeAway);
//        self::assertSame(2, $homeAway->getHome());
//        self::assertSame(1, $homeAway->getAway());
//    }

    public function testIsSomeHomeAwayPlaceNrParticipating(): void
    {
        $sportsWithNrOfCycles = [ new SportWithNrOfCycles(new AgainstOneVsOne(), 1)];
        $scheduleWithNrOfPlaces = new ScheduleWithNrOfPlaces(4, $sportsWithNrOfCycles);
        $sportSchedule = $scheduleWithNrOfPlaces->getSportSchedule(1);
        self::assertInstanceOf(ScheduleAgainstOneVsOne::class, $sportSchedule);

        $cycle = new ScheduleCycleAgainstOneVsOne($sportSchedule);
        $cyclePart = new ScheduleCyclePartAgainstOneVsOne($cycle);
        $this->addOneVsOne($cyclePart, 1, 2);

        self::assertTrue($cyclePart->isSomeHomeAwayPlaceNrParticipating(new OneVsOneHomeAway(1,2)));
        self::assertTrue($cyclePart->isSomeHomeAwayPlaceNrParticipating(new OneVsOneHomeAway(2,3)));
        self::assertTrue($cyclePart->isSomeHomeAwayPlaceNrParticipating(new OneVsOneHomeAway(3,2)));
        self::assertTrue($cyclePart->isSomeHomeAwayPlaceNrParticipating(new OneVsOneHomeAway(1,3)));
        self::assertTrue($cyclePart->isSomeHomeAwayPlaceNrParticipating(new OneVsOneHomeAway(3,1)));
        self::assertFalse($cyclePart->isSomeHomeAwayPlaceNrParticipating(new OneVsOneHomeAway(3,4)));
    }

    public function testGetSelfAndAllPreviousNrOfHomeAways(): void
    {
        $sportsWithNrOfCycles = [ new SportWithNrOfCycles(new AgainstOneVsOne(), 1)];
        $scheduleWithNrOfPlaces = new ScheduleWithNrOfPlaces(4, $sportsWithNrOfCycles);
        $sportSchedule = $scheduleWithNrOfPlaces->getSportSchedule(1);
        self::assertInstanceOf(ScheduleAgainstOneVsOne::class, $sportSchedule);

        $cycle = new ScheduleCycleAgainstOneVsOne($sportSchedule);

        $cyclePartOne = new ScheduleCyclePartAgainstOneVsOne($cycle);
        $this->addOneVsOne($cyclePartOne, 1, 2);
        $this->addOneVsOne($cyclePartOne, 3, 4);

        $cyclePartTwo = $cyclePartOne->createNext();
        $this->addOneVsOne($cyclePartTwo, 1, 3);
        $this->addOneVsOne($cyclePartTwo, 2, 4);

        $cyclePartThree = $cyclePartTwo->createNext();
        $this->addOneVsOne($cyclePartThree, 1, 4);
        $this->addOneVsOne($cyclePartThree, 2, 3);

        self::assertSame(2, $cyclePartOne->getSelfAndAllPreviousNrOfHomeAways());
        self::assertSame(4, $cyclePartTwo->getSelfAndAllPreviousNrOfHomeAways());
        self::assertSame(6, $cyclePartThree->getSelfAndAllPreviousNrOfHomeAways());
    }

//    public function testGetAllHomeAways(): void
//    {
//        $nrOfPlaces = 4;
//        $cycle = new ScheduleCycleAgainst($nrOfPlaces);
//
//        $cyclePartOne = new ScheduleCyclePartAgainst($cycle);
//        $this->addOneVsOne($cyclePartOne, 1, 2);
//        $this->addOneVsOne($cyclePartOne, 3, 4);
//
//        $cyclePartTwo = $cyclePartOne->createNext();
//        $this->addOneVsOne($cyclePartTwo, 1, 3);
//        $this->addOneVsOne($cyclePartTwo, 2, 4);
//
//        $cyclePartThree = $cyclePartTwo->createNext();
//        $this->addOneVsOne($cyclePartThree, 1, 4);
//        $this->addOneVsOne($cyclePartThree, 2, 3);
//    }

    private function addOneVsOne(
        ScheduleCyclePartAgainstOneVsOne $cyclePart,
        int                              $homePlaceNr,
        int                              $awayPlaceNr ): ScheduleGameAgainstOneVsOne{
        $game = new ScheduleGameAgainstOneVsOne($cyclePart);
        new ScheduleGamePlaceAgainst($game, AgainstSide::Home, $homePlaceNr);
        new ScheduleGamePlaceAgainst($game, AgainstSide::Away, $awayPlaceNr);
        $cyclePart->addGame($game);
        return $game;
    }
}
