<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Schedules\CycleParts;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Against\AgainstSide;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\Schedules\CycleParts\ScheduleCyclePartAgainst;
use SportsPlanning\Schedules\Cycles\ScheduleCycleAgainst;
use SportsPlanning\Schedules\GamePlaces\ScheduleGamePlaceAgainst;
use SportsPlanning\Schedules\Games\ScheduleGameAgainstOneVsOne;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;
use SportsPlanning\Schedules\Sports\ScheduleAgainstOneVsOne;

class ScheduleCyclePartAgainstTest extends TestCase
{
    public function testCyclePartNr(): void
    {
        $nrOfPlaces = 5;
        $cycle = new ScheduleCycleAgainst($nrOfPlaces);
        $cyclePart = new ScheduleCyclePartAgainst($cycle);

        self::assertSame(1, $cyclePart->getNumber() );
    }

    public function testIsParticipating(): void
    {
        $nrOfPlaces = 5;
        $cycle = new ScheduleCycleAgainst($nrOfPlaces);
        $cyclePart = new ScheduleCyclePartAgainst($cycle);

        self::assertFalse($cyclePart->isParticipating(1));
    }

    public function testCreateNext(): void
    {
        $nrOfPlaces = 5;
        $cycle = new ScheduleCycleAgainst($nrOfPlaces);
        $cyclePart = new ScheduleCyclePartAgainst($cycle);
        self::assertInstanceOf(ScheduleCyclePartAgainst::class, $cyclePart->createNext());
    }

    public function testAdd(): void
    {
        $nrOfPlaces = 4;
        $cycle = new ScheduleCycleAgainst($nrOfPlaces);
        $cyclePart = new ScheduleCyclePartAgainst($cycle);

        $this->addOneVsOne($cyclePart, 1, 2);
        self::assertCount(1, $cyclePart->getGamesAsHomeAways());
    }

    public function testAddException(): void
    {
        $nrOfPlaces = 4;
        $cycle = new ScheduleCycleAgainst($nrOfPlaces);
        $cyclePart = new ScheduleCyclePartAgainst($cycle);

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
        $nrOfPlaces = 4;
        $cycle = new ScheduleCycleAgainst($nrOfPlaces);
        $cyclePart = new ScheduleCyclePartAgainst($cycle);
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
        $nrOfPlaces = 4;
        $cycle = new ScheduleCycleAgainst($nrOfPlaces);

        $cyclePartOne = new ScheduleCyclePartAgainst($cycle);
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
        ScheduleCyclePartAgainst $cyclePart,
        int $homePlaceNr,
        int $awayPlaceNr ): ScheduleGameAgainstOneVsOne{
        $game = new ScheduleGameAgainstOneVsOne($cyclePart);
        new ScheduleGamePlaceAgainst($game, AgainstSide::Home, $homePlaceNr);
        new ScheduleGamePlaceAgainst($game, AgainstSide::Away, $awayPlaceNr);
        $cyclePart->addGame($game);
        return $game;
    }
}
