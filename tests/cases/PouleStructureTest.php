<?php

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsHelpers\SelfRefereeInfo;
use SportsPlanning\PlanningPouleStructure as PlanningPouleStructure;
use SportsHelpers\SelfReferee;
use SportsPlanning\TestHelper\PlanningCreator;

class PouleStructureTest extends TestCase
{
    use PlanningCreator;

    public function testSelfRefereeSamePouleIsInvalid(): void
    {
        $pouleStructure = new PouleStructure(5, 4);
        $sportsWithNrOfFieldsAndNrOfCycles = [$this->createAgainstOneVsOneSportWithNrOfFieldsAndNrOfCycles(1, 3)];

        $refereeInfo = new RefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule));
        self::expectNotToPerformAssertions();
        new PlanningPouleStructure($pouleStructure, $sportsWithNrOfFieldsAndNrOfCycles, $refereeInfo);
    }

    public function testSelfRefereeSamePouleIsValid(): void
    {
        $pouleStructure = new PouleStructure(5);
        $sportsWithNrOfFieldsAndNrOfCycles = [$this->createAgainstTwoVsTwoSportWithNrOfFieldsAndNrOfCycles(1)];
        $refereeInfo = new RefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule));
        self::expectNotToPerformAssertions();
        new PlanningPouleStructure($pouleStructure, $sportsWithNrOfFieldsAndNrOfCycles, $refereeInfo);
    }

    public function testSelfRefereeOtherPoulesIsInvalid(): void
    {
        $pouleStructure = new PouleStructure(4);
        $sportsWithNrOfFieldsAndNrOfCycles = [$this->createAgainstTwoVsTwoSportWithNrOfFieldsAndNrOfCycles(1)];
        $refereeInfo = new RefereeInfo(new SelfRefereeInfo(SelfReferee::OtherPoules));
        self::expectException(\Exception::class);
        new PlanningPouleStructure($pouleStructure, $sportsWithNrOfFieldsAndNrOfCycles, $refereeInfo);
    }

    public function testSelfRefereeOtherPoulesIsValid(): void
    {
        $pouleStructure = new PouleStructure(4, 4);
        $sportsWithNrOfFieldsAndNrOfCycles = [$this->createAgainstTwoVsTwoSportWithNrOfFieldsAndNrOfCycles(1)];
        $refereeInfo = new RefereeInfo(new SelfRefereeInfo(SelfReferee::OtherPoules));
        self::expectNotToPerformAssertions();
        new PlanningPouleStructure($pouleStructure, $sportsWithNrOfFieldsAndNrOfCycles, $refereeInfo);
    }

    public function testSelfRefereeDisabledIsValid(): void
    {
        $PouleStructure = new PouleStructure(4);
        $sportsWithNrOfFieldsAndNrOfCycles = [$this->createAgainstTwoVsTwoSportWithNrOfFieldsAndNrOfCycles(1)];
        $refereeInfo = new RefereeInfo(new SelfRefereeInfo(SelfReferee::Disabled));
        self::expectNotToPerformAssertions();
        new PlanningPouleStructure($PouleStructure, $sportsWithNrOfFieldsAndNrOfCycles, $refereeInfo);
    }

    public function testMaxNrOfGamesPerBatchSimple(): void
    {
        self::expectException(\Exception::class);
        $pouleStructure = new PlanningPouleStructure(
            new PouleStructure(3, 2, 2),
            [$this->createAgainstTwoVsTwoSportWithNrOfFieldsAndNrOfCycles(4)],
            new RefereeInfo());

        $maxNrOfGamesSim = $pouleStructure->getMaxNrOfGamesPerBatch();
        self::assertSame(3, $maxNrOfGamesSim);
    }

    public function testMaxNrOfGamesPerBatch6Places3Fields1Referee(): void
    {
        $sportVariantWithFields = $this->createAgainstOneVsOneSportWithNrOfFieldsAndNrOfCycles(3);

        $pouleStructure = new PlanningPouleStructure(
            new PouleStructure(6),
            [$sportVariantWithFields],
            new RefereeInfo(1));

        $maxNrOfGamesInARow = $pouleStructure->getMaxNrOfGamesPerBatch();
        self::assertSame(1, $maxNrOfGamesInARow);
    }

    // [2,2,2,2,2,2] - [against(1vs1) h2h:gpp=>1:0 f(6)] - gpstrat=>eql - ref=>0:OP
    public function testMaxNrOfGamesPerBatchOtherPouleSimple(): void
    {
        $pouleStructure = new PlanningPouleStructure(
            new PouleStructure(2, 2, 2, 2, 2, 2),
            [$this->createAgainstOneVsOneSportWithNrOfFieldsAndNrOfCycles(6)],
            new RefereeInfo(new SelfRefereeInfo(SelfReferee::OtherPoules))
        );

        $maxNrOfGamesPerBatch = $pouleStructure->getMaxNrOfGamesPerBatch();
        self::assertSame(4, $maxNrOfGamesPerBatch);
    }

    // [2,2,2,2] - [against(1vs1) h2h:gpp=>1:0 f(2)] - gpstrat=>eql - ref=>0:OP
    public function testMaxNrOfGamesPerBatchOtherPouleSimple2(): void
    {
        $pouleStructure = new PlanningPouleStructure(
            new PouleStructure(2, 2, 2, 2),
            [$this->createAgainstOneVsOneSportWithNrOfFieldsAndNrOfCycles(2)],
            new RefereeInfo(new SelfRefereeInfo(SelfReferee::OtherPoules))
        );

        $maxNrOfGamesPerBatch = $pouleStructure->getMaxNrOfGamesPerBatch();
        self::assertSame(2, $maxNrOfGamesPerBatch);
    }

    public function testMaxNrOfGamesPerBatch6Places3Fields2Referees(): void
    {
        $sportVariantsWithFields = [
            $this->createAgainstOneVsOneSportWithNrOfFieldsAndNrOfCycles(3)
        ];

        $pouleStructure = new PlanningPouleStructure(
            new PouleStructure(6),
            $sportVariantsWithFields,
            new RefereeInfo(2));

        $maxNrOfGamesPerBatch = $pouleStructure->getMaxNrOfGamesPerBatch();
        self::assertSame(2, $maxNrOfGamesPerBatch);
    }

    // [3,2,2,2] - [allinone gpp=>1 f(2)] - gpstrat=>eql - ref=>0:
    public function testMaxNrOfGamesPerBatchAllInOneGame(): void
    {

        $pouleStructure = new PlanningPouleStructure(
            new PouleStructure(3, 2, 2, 2),
            [$this->createTogetherSportWithNrOfFieldsAndNrOfCycles(2)],
            new RefereeInfo());

        $maxNrOfGamesPerBatch = $pouleStructure->getMaxNrOfGamesPerBatch();
        self::assertSame(2, $maxNrOfGamesPerBatch);
    }

    public function testMaxNrOfGamesPerBatchSportsExceedNrOfPlaces(): void
    {
        $pouleStructure = new PlanningPouleStructure(
            new PouleStructure(5),
            [
                $this->createAgainstOneVsOneSportWithNrOfFieldsAndNrOfCycles(2),
                $this->createAgainstOneVsOneSportWithNrOfFieldsAndNrOfCycles(2),
            ],
            new RefereeInfo());

        $maxNrOfGamesPerBatch = $pouleStructure->getMaxNrOfGamesPerBatch();
        self::assertSame(2, $maxNrOfGamesPerBatch);
    }

    public function testMaxNrOfGamesInARow6Places2Fields(): void
    {
        $pouleStructure = new PlanningPouleStructure(
            new PouleStructure(6),
            [$this->createAgainstOneVsOneSportWithNrOfFieldsAndNrOfCycles(2)],
            new RefereeInfo());

        $maxNrOfGamesInARow = $pouleStructure->getMaxNrOfGamesInARow();
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
        $sportVariantsWithFields = [
            $this->createAgainstOneVsOneSportWithNrOfFieldsAndNrOfCycles(1, 7),
            $this->createAgainstOneVsOneSportWithNrOfFieldsAndNrOfCycles( 1, 7),
            $this->createAgainstOneVsOneSportWithNrOfFieldsAndNrOfCycles( 1, 7),
            $this->createAgainstOneVsOneSportWithNrOfFieldsAndNrOfCycles( 1, 7),
            $this->createAgainstOneVsOneSportWithNrOfFieldsAndNrOfCycles( 1, 7)
        ];

        $pouleStructure = new PlanningPouleStructure(
            new PouleStructure(8),
            $sportVariantsWithFields,
            new RefereeInfo());

        $maxNrOfGamesInARow = $pouleStructure->getMaxNrOfGamesInARow();
        self::assertSame(7, $maxNrOfGamesInARow);
    }

    // [10,10,10,10] - [against(1vs1) h2h:gpp=>2:0 f(20)] - gpstrat=>eql - ref=>0:
    public function testMaxNrOfGamesInARowOneVsOneWith2CyclesAnd10Places(): void
    {
        $sportVariantsWithFields = [
            $this->createAgainstOneVsOneSportWithNrOfFieldsAndNrOfCycles(20, 2)
        ];

        $pouleStructure = new PlanningPouleStructure(
            new PouleStructure(10, 10, 10, 10),
            $sportVariantsWithFields,
            new RefereeInfo());

        $maxNrOfGamesInARow = $pouleStructure->getMaxNrOfGamesInARow();
        self::assertSame(9, $maxNrOfGamesInARow);
    }



    public function testMaxNrOfGamesInARow6Places3Fields2Referees(): void
    {
        $sportPersistVariantWithFields = $this->createAgainstOneVsOneSportWithNrOfFieldsAndNrOfCycles(3);

        $pouleStructure = new PlanningPouleStructure(
            new PouleStructure(6),
            [$sportPersistVariantWithFields],
            new RefereeInfo(2)
        );

        $maxNrOfGamesPerBatch = $pouleStructure->getMaxNrOfGamesPerBatch();
        self::assertSame(2, $maxNrOfGamesPerBatch);
    }



}