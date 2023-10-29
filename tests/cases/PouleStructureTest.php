<?php

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructure;
use SportsHelpers\PouleStructure as PouleStructureBase;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsHelpers\SelfRefereeInfo;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsPlanning\PouleStructure as PlanningPouleStructure;
use SportsHelpers\SelfReferee;
use SportsHelpers\Sport\VariantWithFields;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGppSportVariant;
use SportsPlanning\TestHelper\PlanningCreator;

class PouleStructureTest extends TestCase
{
    use PlanningCreator;

    public function testSelfRefereeSamePouleIsInvalid(): void
    {
        $sportVariant = new AgainstGppSportVariant(2, 2, 3);
        $pouleStructureBase = new PouleStructureBase(5, 4);
        $sportVariantsWithFields = [new VariantWithFields($sportVariant, 1)];
        $refereeInfo = new RefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule));
        self::expectException(\Exception::class);
        new PlanningPouleStructure($pouleStructureBase, $sportVariantsWithFields, $refereeInfo);
    }

    public function testSelfRefereeSamePouleIsValid(): void
    {
        $sportVariant = new AgainstGppSportVariant(2, 2, 1);
        $pouleStructureBase = new PouleStructureBase(5);
        $sportVariantsWithFields = [new VariantWithFields($sportVariant, 1)];
        $refereeInfo = new RefereeInfo(new SelfRefereeInfo(SelfReferee::SamePoule));
        self::expectNotToPerformAssertions();
        new PlanningPouleStructure($pouleStructureBase, $sportVariantsWithFields, $refereeInfo);
    }

    public function testSelfRefereeOtherPoulesIsInvalid(): void
    {
        $sportVariant = new AgainstGppSportVariant(2, 2, 1);
        $pouleStructureBase = new PouleStructureBase(4);
        $sportVariantsWithFields = [new VariantWithFields($sportVariant, 1)];
        $refereeInfo = new RefereeInfo(new SelfRefereeInfo(SelfReferee::OtherPoules));
        self::expectException(\Exception::class);
        new PlanningPouleStructure($pouleStructureBase, $sportVariantsWithFields, $refereeInfo);
    }

    public function testSelfRefereeOtherPoulesIsValid(): void
    {
        $sportVariant = new AgainstGppSportVariant(2, 2, 1);
        $pouleStructureBase = new PouleStructureBase(4, 4);
        $sportVariantsWithFields = [new VariantWithFields($sportVariant, 1)];
        $refereeInfo = new RefereeInfo(new SelfRefereeInfo(SelfReferee::OtherPoules));
        self::expectNotToPerformAssertions();
        new PlanningPouleStructure($pouleStructureBase, $sportVariantsWithFields, $refereeInfo);
    }

    public function testSelfRefereeDisabledIsValid(): void
    {
        $sportVariant = new AgainstGppSportVariant(2, 2, 1);
        $pouleStructureBase = new PouleStructureBase(4);
        $sportVariantsWithFields = [new VariantWithFields($sportVariant, 1)];
        $refereeInfo = new RefereeInfo(new SelfRefereeInfo(SelfReferee::Disabled));
        self::expectNotToPerformAssertions();
        new PlanningPouleStructure($pouleStructureBase, $sportVariantsWithFields, $refereeInfo);
    }

    public function testMaxNrOfGamesPerBatchSimple(): void
    {
        $pouleStructure = new PlanningPouleStructure(
            new PouleStructureBase(3, 2, 2),
            [$this->getAgainstH2hSportVariantWithFields(4)],
            new RefereeInfo());

        $maxNrOfGamesSim = $pouleStructure->getMaxNrOfGamesPerBatch();
        self::assertSame(3, $maxNrOfGamesSim);
    }

    public function testMaxNrOfGamesPerBatch6Places3Fields1Referee(): void
    {
        $sportVariantWithFields = $this->getAgainstH2hSportVariantWithFields(3);

        $pouleStructure = new PlanningPouleStructure(
            new PouleStructureBase(6),
            [$sportVariantWithFields],
            new RefereeInfo(1));

        $maxNrOfGamesInARow = $pouleStructure->getMaxNrOfGamesPerBatch();
        self::assertSame(1, $maxNrOfGamesInARow);
    }

    // [2,2,2,2,2,2] - [against(1vs1) h2h:gpp=>1:0 f(6)] - gpstrat=>eql - ref=>0:OP
    public function testMaxNrOfGamesPerBatchOtherPouleSimple(): void
    {
        $pouleStructure = new PlanningPouleStructure(
            new PouleStructureBase(2, 2, 2, 2, 2, 2),
            [$this->getAgainstH2hSportVariantWithFields(6)],
            new RefereeInfo(new SelfRefereeInfo(SelfReferee::OtherPoules))
        );

        $maxNrOfGamesPerBatch = $pouleStructure->getMaxNrOfGamesPerBatch();
        self::assertSame(4, $maxNrOfGamesPerBatch);
    }

    // [2,2,2,2] - [against(1vs1) h2h:gpp=>1:0 f(2)] - gpstrat=>eql - ref=>0:OP
    public function testMaxNrOfGamesPerBatchOtherPouleSimple2(): void
    {
        $pouleStructure = new PlanningPouleStructure(
            new PouleStructureBase(2, 2, 2, 2),
            [$this->getAgainstH2hSportVariantWithFields(2)],
            new RefereeInfo(new SelfRefereeInfo(SelfReferee::OtherPoules))
        );

        $maxNrOfGamesPerBatch = $pouleStructure->getMaxNrOfGamesPerBatch();
        self::assertSame(2, $maxNrOfGamesPerBatch);
    }

    public function testMaxNrOfGamesPerBatch6Places3Fields2Referees(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(3)
        ];

        $pouleStructure = new PlanningPouleStructure(
            new PouleStructureBase(6),
            $sportVariantsWithFields,
            new RefereeInfo(2));

        $maxNrOfGamesPerBatch = $pouleStructure->getMaxNrOfGamesPerBatch();
        self::assertSame(2, $maxNrOfGamesPerBatch);
    }

    // [3,2,2,2] - [allinone gpp=>1 f(2)] - gpstrat=>eql - ref=>0:
    public function testMaxNrOfGamesPerBatchAllInOneGame(): void
    {
        $pouleStructure = new PlanningPouleStructure(
            new PouleStructureBase(3, 2, 2, 2),
            [new VariantWithFields(new AllInOneGame(1), 2)],
            new RefereeInfo());

        $maxNrOfGamesPerBatch = $pouleStructure->getMaxNrOfGamesPerBatch();
        self::assertSame(2, $maxNrOfGamesPerBatch);
    }

    public function testMaxNrOfGamesPerBatchSportsExceedNrOfPlaces(): void
    {
        $pouleStructure = new PlanningPouleStructure(
            new PouleStructureBase(5),
            [
                new VariantWithFields(new AgainstGpp(1, 1, 1), 2),
                new VariantWithFields(new AgainstGpp(1, 1, 1), 2),
            ],
            new RefereeInfo());

        $maxNrOfGamesPerBatch = $pouleStructure->getMaxNrOfGamesPerBatch();
        self::assertSame(2, $maxNrOfGamesPerBatch);
    }

    public function testMaxNrOfGamesInARow6Places2Fields(): void
    {
        $pouleStructure = new PlanningPouleStructure(
            new PouleStructureBase(6),
            [$this->getAgainstH2hSportVariantWithFields(2)],
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
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7)
        ];

        $pouleStructure = new PlanningPouleStructure(
            new PouleStructureBase(8),
            $sportVariantsWithFields,
            new RefereeInfo());

        $maxNrOfGamesInARow = $pouleStructure->getMaxNrOfGamesInARow();
        self::assertSame(7, $maxNrOfGamesInARow);
    }

    // [10,10,10,10] - [against(1vs1) h2h:gpp=>2:0 f(20)] - gpstrat=>eql - ref=>0:
    public function testMaxNrOfGamesInARowH2h210Places(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(20, 1, 1, 2)
        ];

        $pouleStructure = new PlanningPouleStructure(
            new PouleStructureBase(10, 10, 10, 10),
            $sportVariantsWithFields,
            new RefereeInfo());

        $maxNrOfGamesInARow = $pouleStructure->getMaxNrOfGamesInARow();
        self::assertSame(9, $maxNrOfGamesInARow);
    }



}