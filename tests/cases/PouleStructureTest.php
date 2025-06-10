<?php

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\RefereeInfo;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;
use SportsHelpers\SelfRefereeInfo;
use SportsPlanning\PlanningPouleStructure as PlanningPouleStructure;
use SportsHelpers\SelfReferee;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;

final class PouleStructureTest extends TestCase
{

    public function testSelfRefereeSamePouleIsInvalid(): void
    {
        $pouleStructure = new PouleStructure([5, 4]);
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 3)
        ];
        $refereeInfo = RefereeInfo::fromSelfRefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule));
        self::expectNotToPerformAssertions();
        new PlanningPouleStructure($pouleStructure, $sportsWithNrOfFieldsAndNrOfCycles, $refereeInfo);
    }

    public function testSelfRefereeSamePouleIsValid(): void
    {
        $pouleStructure = new PouleStructure([5]);
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstTwoVsTwo(), 1, 1),
        ];
        $refereeInfo = RefereeInfo::fromSelfRefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule));
        self::expectNotToPerformAssertions();
        new PlanningPouleStructure($pouleStructure, $sportsWithNrOfFieldsAndNrOfCycles, $refereeInfo);
    }

    public function testSelfRefereeOtherPoulesIsInvalid(): void
    {
        $pouleStructure = new PouleStructure([4]);
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstTwoVsTwo(), 1, 1),
        ];
        $refereeInfo = RefereeInfo::fromSelfRefereeInfo(new SelfRefereeInfo(SelfReferee::OtherPoules));
        self::expectException(\Exception::class);
        new PlanningPouleStructure($pouleStructure, $sportsWithNrOfFieldsAndNrOfCycles, $refereeInfo);
    }

    public function testSelfRefereeOtherPoulesIsValid(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstTwoVsTwo(), 1, 1),
        ];
        $pouleStructure = new PouleStructure([4, 4]);
        $refereeInfo = RefereeInfo::fromSelfRefereeInfo(new SelfRefereeInfo(SelfReferee::OtherPoules));
        self::expectNotToPerformAssertions();
        new PlanningPouleStructure($pouleStructure, $sportsWithNrOfFieldsAndNrOfCycles, $refereeInfo);
    }

    public function testSelfRefereeDisabledIsValid(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstTwoVsTwo(), 1, 1),
        ];
        $pouleStructure = new PouleStructure([4]);
        self::expectNotToPerformAssertions();
        new PlanningPouleStructure($pouleStructure, $sportsWithNrOfFieldsAndNrOfCycles, null);
    }

    public function testMaxNrOfGamesPerBatchSimple(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 4, 1),
        ];
        $planningPouleStructure = new PlanningPouleStructure(
            new PouleStructure([3, 2, 2]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null);

        $maxNrOfGamesPerBatch = $planningPouleStructure->calculateMaxNrOfGamesPerBatch();
        self::assertSame(3, $maxNrOfGamesPerBatch);
    }

    public function testMaxNrOfGamesPerBatch6Places3Fields1Referee(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 3, 1),
        ];
        $planningPouleStructure = new PlanningPouleStructure(
            new PouleStructure([6]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            RefereeInfo::fromNrOfReferees(1));

        $maxNrOfGamesInARow = $planningPouleStructure->calculateMaxNrOfGamesPerBatch();
        self::assertSame(1, $maxNrOfGamesInARow);
    }

    // [2,2,2,2,2,2] - [against(1vs1) h2h:gpp=>1:0 f(6)] - gpstrat=>eql - ref=>0:OP
    public function testMaxNrOfGamesPerBatchOtherPouleSimple(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 6, 1),
        ];
        $planningPouleStructure = new PlanningPouleStructure(
            new PouleStructure([2, 2, 2, 2, 2, 2]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            RefereeInfo::fromSelfRefereeInfo(new SelfRefereeInfo(SelfReferee::OtherPoules))
        );

        $maxNrOfGamesPerBatch = $planningPouleStructure->calculateMaxNrOfGamesPerBatch();
        self::assertSame(4, $maxNrOfGamesPerBatch);
    }

    // [2,2,2,2] - [against(1vs1) h2h:gpp=>1:0 f(2)] - gpstrat=>eql - ref=>0:OP
    public function testMaxNrOfGamesPerBatchOtherPouleSimple2(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1),
        ];
        $planningPouleStructure = new PlanningPouleStructure(
            new PouleStructure([2, 2, 2, 2]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            RefereeInfo::fromSelfRefereeInfo(new SelfRefereeInfo(SelfReferee::OtherPoules))
        );

        $maxNrOfGamesPerBatch = $planningPouleStructure->calculateMaxNrOfGamesPerBatch();
        self::assertSame(2, $maxNrOfGamesPerBatch);
    }

    public function testMaxNrOfGamesPerBatch6Places3Fields2Referees(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 3, 1),
        ];
        $planningPouleStructure = new PlanningPouleStructure(
            new PouleStructure([6]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            RefereeInfo::fromNrOfReferees(2));

        $maxNrOfGamesPerBatch = $planningPouleStructure->calculateMaxNrOfGamesPerBatch();
        self::assertSame(2, $maxNrOfGamesPerBatch);
    }

    // [3,2,2,2] - [allinone gpp=>1 f(2)] - gpstrat=>eql - ref=>0:
    public function testMaxNrOfGamesPerBatchAllInOneGame(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new TogetherSport(1), 2, 1),
        ];
        $planningPouleStructure = new PlanningPouleStructure(
            new PouleStructure([3, 2, 2, 2]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null);

        $maxNrOfGamesPerBatch = $planningPouleStructure->calculateMaxNrOfGamesPerBatch();
        self::assertSame(2, $maxNrOfGamesPerBatch);
    }

    public function testMaxNrOfGamesPerBatchSportsExceedNrOfPlaces(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1),
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1),
        ];
        $planningPouleStructure = new PlanningPouleStructure(
            new PouleStructure([5]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null
        );

        $maxNrOfGamesPerBatch = $planningPouleStructure->calculateMaxNrOfGamesPerBatch();
        self::assertSame(2, $maxNrOfGamesPerBatch);
    }

    public function testMaxNrOfGamesInARow6Places2Fields(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 2, 1),
        ];
        $planningPouleStructure = new PlanningPouleStructure(
            new PouleStructure([4]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null
            );

        $maxNrOfGamesInARow = $planningPouleStructure->calculateMaxNrOfGamesInARow();
        self::assertSame(3, $maxNrOfGamesInARow);
    }

    //     [8] - [
    //        against(1vs1) h2h:gpp=>1:0 f(1) &
    //        against(1vs1) h2h:gpp=>1:0 f(1) &
    //        against(1vs1) h2h:gpp=>1:0 f(1) &
    //        against(1vs1) h2h:gpp=>1:0 f(1) &
    //        against(1vs1) h2h:gpp=>1:0 f(1)] -
    //     gpstrat=>eql - ref=>0:
    public function testMaxNrOfGamesInARow5Sports8Places(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 7),
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 7),
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 7),
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 7),
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 1, 7),
        ];

        $planningPouleStructure = new PlanningPouleStructure(
            new PouleStructure([8]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null
        );

        $maxNrOfGamesInARow = $planningPouleStructure->calculateMaxNrOfGamesInARow();
        self::assertSame(7, $maxNrOfGamesInARow);
    }

    // [10,10,10,10] - [against(1vs1) h2h:gpp=>2:0 f(20)] - gpstrat=>eql - ref=>0:
    public function testMaxNrOfGamesInARowOneVsOneWith2CyclesAnd10Places(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 20, 2)
        ];
        $planningPouleStructure = new PlanningPouleStructure(
            new PouleStructure([10, 10, 10, 10]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            null
        );

        $maxNrOfGamesInARow = $planningPouleStructure->calculateMaxNrOfGamesInARow();
        self::assertSame(9, $maxNrOfGamesInARow);
    }



    public function testMaxNrOfGamesInARow6Places3Fields2Referees(): void
    {
        $sportsWithNrOfFieldsAndNrOfCycles = [
            new SportWithNrOfFieldsAndNrOfCycles(new AgainstOneVsOne(), 3, 1)
        ];
        $planningPouleStructure = new PlanningPouleStructure(
            new PouleStructure([6]),
            $sportsWithNrOfFieldsAndNrOfCycles,
            RefereeInfo::fromNrOfReferees(2)
        );

        $maxNrOfGamesPerBatch = $planningPouleStructure->calculateMaxNrOfGamesPerBatch();
        self::assertSame(2, $maxNrOfGamesPerBatch);
    }
}