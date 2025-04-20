<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Schedules\GameRounds;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsPlanning\HomeAways\OneVsOneHomeAway;
use SportsPlanning\Planning;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsPlanning\Schedules\GameRounds\AgainstGameRound;
use SportsPlanning\TestHelper\PlanningCreator;

class AgainstGameRoundTest extends TestCase
{
    public function testIsParticipating(): void
    {
        $gameRound = new AgainstGameRound(4);
        self::assertFalse($gameRound->isParticipating(1));
    }

    public function testCreateNext(): void
    {
        $gameRound = new AgainstGameRound(4);
        self::assertInstanceOf(AgainstGameRound::class, $gameRound->createNext());
    }

    public function testAdd(): void
    {
        $gameRound = new AgainstGameRound(4);
        $gameRound->add(new OneVsOneHomeAway(1,2));
        self::assertCount(1, $gameRound->getHomeAways());
    }

    public function testAddException(): void
    {
        $gameRound = new AgainstGameRound(4);
        $gameRound->add(new OneVsOneHomeAway(1,2));
        self::expectException(\Exception::class);
        $gameRound->add(new OneVsOneHomeAway(2,3));
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
        $gameRound = new AgainstGameRound(4);
        $gameRound->add(new OneVsOneHomeAway(1,2));

        self::assertTrue($gameRound->isSomeHomeAwayPlaceNrParticipating(new OneVsOneHomeAway(1,2)));
        self::assertTrue($gameRound->isSomeHomeAwayPlaceNrParticipating(new OneVsOneHomeAway(2,3)));
        self::assertTrue($gameRound->isSomeHomeAwayPlaceNrParticipating(new OneVsOneHomeAway(3,2)));
        self::assertTrue($gameRound->isSomeHomeAwayPlaceNrParticipating(new OneVsOneHomeAway(1,3)));
        self::assertTrue($gameRound->isSomeHomeAwayPlaceNrParticipating(new OneVsOneHomeAway(3,1)));
        self::assertFalse($gameRound->isSomeHomeAwayPlaceNrParticipating(new OneVsOneHomeAway(3,4)));
    }

    public function testGetSelfAndAllPreviousNrOfHomeAways(): void
    {
        $gameRoundOne = new AgainstGameRound(4);
        $gameRoundOne->add(new OneVsOneHomeAway(1,2));
        $gameRoundOne->add(new OneVsOneHomeAway(3,4));
        $gameRoundTwo = $gameRoundOne->createNext();
        $gameRoundTwo->add(new OneVsOneHomeAway(1,3));
        $gameRoundTwo->add(new OneVsOneHomeAway(2,4));
        $gameRoundThree = $gameRoundTwo->createNext();
        $gameRoundThree->add(new OneVsOneHomeAway(1,4));
        $gameRoundThree->add(new OneVsOneHomeAway(2,3));

        self::assertSame(2, $gameRoundOne->getSelfAndAllPreviousNrOfHomeAways());
        self::assertSame(4, $gameRoundTwo->getSelfAndAllPreviousNrOfHomeAways());
        self::assertSame(6, $gameRoundThree->getSelfAndAllPreviousNrOfHomeAways());
    }

    public function testGetAllHomeAways(): void
    {
        $gameRoundOne = new AgainstGameRound(4);
        $gameRoundOne->add(new OneVsOneHomeAway(1,2));
        $gameRoundOne->add(new OneVsOneHomeAway(3,4));
        $gameRoundTwo = $gameRoundOne->createNext();
        $gameRoundTwo->add(new OneVsOneHomeAway(1,3));
        $gameRoundTwo->add(new OneVsOneHomeAway(2,4));
        $gameRoundThree = $gameRoundTwo->createNext();
        $gameRoundThree->add(new OneVsOneHomeAway(1,4));
        $gameRoundThree->add(new OneVsOneHomeAway(2,3));

        self::assertCount(6, $gameRoundOne->getAllHomeAways());
        self::assertCount(6, $gameRoundTwo->getAllHomeAways());
        self::assertCount(6, $gameRoundThree->getAllHomeAways());
    }
}
