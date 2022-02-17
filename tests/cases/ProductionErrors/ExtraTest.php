<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\ProductionErrors;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SelfReferee;
use SportsHelpers\SportRange;
use SportsPlanning\Combinations\GamePlaceStrategy;
use SportsPlanning\Planning\Validator as PlanningValidator;
use SportsPlanning\Planning\Output as PlanningOutput;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsPlanning\TestHelper\PlanningCreator;

class ExtraTest extends TestCase
{
    use PlanningCreator;

    // [10,2,2] - [against(1vs1) h2h:gpp=>1:0 f(3)] - gpstrat=>eql - ref=>0:OP
    public function test1022(): void
    {
        $nrOfGamesPerBatchRange = new SportRange(1, 3);
        $sportVariantsWithFields = $this->getAgainstH2hSportVariantWithFields(3);
        $refereeInfo = new RefereeInfo(SelfReferee::OtherPoules);
        $planning = $this->createPlanning(
            $this->createInput(
                [10, 2, 2],
                [$sportVariantsWithFields],
                GamePlaceStrategy::EquallyAssigned,
                $refereeInfo
            ),
            $nrOfGamesPerBatchRange/*,
            0,
            true*/
        );

        // (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);

        //(new PlanningOutput())->outputWithGames($planning, true);
        // echo "============ " . (microtime(true) - $time_start);
    }

    // [18] - [against(1vs1) h2h:gpp=>2:0 f(1)] - gpstrat=>eql - ref=>0:
//    public function test18(): void
//    {
//        $nrOfGamesPerBatchRange = new SportRange(1, 1);
//        $sportVariantsWithFields = $this->getAgainstSportVariantWithFields(1, 1, 1, 2);
//        $planning = $this->createPlanning(
//            $this->createInput(
//                [18],
//                [$sportVariantsWithFields],
//                GamePlaceStrategy::EquallyAssigned,
//                0,
//                SelfReferee::Disabled
//            ),
//            $nrOfGamesPerBatchRange,
//            0,
//            true
//        );
//
//        // (new PlanningOutput())->outputWithGames($planning, true);
//
//        $planningValidator = new PlanningValidator();
//        $validity = $planningValidator->validate($planning);
//        self::assertSame(PlanningValidator::VALID, $validity);
//    }

//    // [14,14] - [
//    //      against(1vs1) h2h:gpp=>1:0 f(2) &
//    //      against(1vs1) h2h:gpp=>1:0 f(2) &
//    //      against(1vs1) h2h:gpp=>1:0 f(2) &
//    //      against(1vs1) h2h:gpp=>1:0 f(2) &
//    //      against(1vs1) h2h:gpp=>1:0 f(2)] - gpstrat=>eql - ref=>0:
//    public function test1414(): void
//    {
//        $nrOfGamesPerBatchRange = new SportRange(10,10);
//        $sportVariantsWithFields = [
//            $this->getAgainstSportVariantWithFields(2),
//            $this->getAgainstSportVariantWithFields(2),
//            $this->getAgainstSportVariantWithFields(2),
//            $this->getAgainstSportVariantWithFields(2),
//            $this->getAgainstSportVariantWithFields(2),
//        ];
//        $planning = $this->createPlanning(
//            $this->createInput(
//                [14,14],
//                $sportVariantsWithFields,
//                GamePlaceStrategy::EquallyAssigned,
//                0,
//                SelfReferee::Disabled
//            ),
//            $nrOfGamesPerBatchRange,
//            0,
//            true
//        );
//
    ////        (new PlanningOutput())->outputWithGames($planning, true);
//
//        $planningValidator = new PlanningValidator();
//        $validity = $planningValidator->validate($planning);
//        self::assertSame(PlanningValidator::VALID, $validity);
//    }

    public function test10(): void
    {
        $nrOfGamesPerBatchRange = new SportRange(3, 3);
        $sportVariantsWithFields = [
            $this->getAgainstGppSportVariantWithFields(2, 1, 1, 9),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 9),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 9),
        ];
        $refereeInfo = new RefereeInfo(SelfReferee::Disabled);
        $planning = $this->createPlanning(
            $this->createInput([10], $sportVariantsWithFields, GamePlaceStrategy::EquallyAssigned, $refereeInfo),
            $nrOfGamesPerBatchRange/*,
            0,
            true, true*/
        );

//        (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);
    }

    // [5,5,5,5,5,5,5,5] - [against(1vs1) h2h:gpp=>1:0 f(14)] - gpstrat=>eql - ref=>0:
    public function test14BatchGames(): void
    {
        $nrOfGamesPerBatchRange = new SportRange(14, 14);
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(14),
        ];
        $refereeInfo = new RefereeInfo(0);
        $input = $this->createInput(
            [5, 5, 5, 5, 5, 5, 5, 5],
            $sportVariantsWithFields,
            GamePlaceStrategy::EquallyAssigned,
            $refereeInfo
        );
        $planning = $this->createPlanning($input, $nrOfGamesPerBatchRange/*, 0, true, true*/);

//        (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);
    }

//     [8] - [
//        against(1vs1) h2h:gpp=>1:0 f(1) &
//        against(1vs1) h2h:gpp=>1:0 f(1) &
//        against(1vs1) h2h:gpp=>1:0 f(1) &
//        against(1vs1) h2h:gpp=>1:0 f(1) &
//        against(1vs1) h2h:gpp=>1:0 f(1) -
//     gpstrat=>eql - ref=>0:
    public function test5Sports8Places(): void
    {
        $nrOfGamesPerBatchRange = new SportRange(3, 4);
        $sportVariantsWithFields = [
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7)
        ];
        $refereeInfo = new RefereeInfo(0);
        $input = $this->createInput(
            [8],
            $sportVariantsWithFields,
            GamePlaceStrategy::EquallyAssigned,
            $refereeInfo
        );
        $planning = $this->createPlanning($input, $nrOfGamesPerBatchRange/*,0, true, true*/);

        self::assertLessThanOrEqual(40, $planning->getNrOfBatches());


//        (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);
    }

    //    [5,5,4,4] - [against(1vs1) h2h:gpp=>1:0 f(9)] - gpstrat=>eql - ref=>0:
    //  Need minimal 5 batches
    public function test5554SingleAgainstSport(): void
    {
        $nrOfGamesPerBatchRange = new SportRange(7, 7);
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(9)
        ];
        $input = $this->createInput(
            [5, 5, 4, 4],
            $sportVariantsWithFields,
            GamePlaceStrategy::EquallyAssigned,
            new RefereeInfo(0)
        );
        $planning = $this->createPlanning($input, $nrOfGamesPerBatchRange/*,0, true, true*/);

        self::assertLessThan(6, $planning->getNrOfBatches());

//        (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);
    }

    //  [7,6] - [against(1vs1) h2h:gpp=>1:0 f(6)] - gpstrat=>eql - ref=>0:
    //  aan het eind kan nog maar 3 wedstrijden tegelijk
    // dus in dit geval: bij unbalanced en 2 pouls dan wordt minimum 3!!!!!
    public function test76SingleAgainstSport(): void
    {
        $nrOfGamesPerBatchRange = new SportRange(3, 6);
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(6)
        ];
        $input = $this->createInput(
            [7, 6],
            $sportVariantsWithFields,
            GamePlaceStrategy::EquallyAssigned,
            new RefereeInfo(0)
        );
        $planning = $this->createPlanning($input, $nrOfGamesPerBatchRange/*,0, true, true*/);

        self::assertLessThan(8, $planning->getNrOfBatches());

//        (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);
    }

    // ----------------     NOT OK FROM HERE   --------------------------------
    // [11] - [single(2) gpp=>2 f(1) & single(2) gpp=>2 f(1) & single(2) gpp=>2 f(1) & single(2) gpp=>2 f(1) & single(2) gpp=>2 f(1)] - gpstrat=>eql - ref=>0:
    // [5,4,4] - [against(1vs1) h2h:gpp=>2:0 f(6)] - gpstrat=>eql - ref=>0:
//    public function test5SingleSports11Places(): void
//    {
//        $nrOfGamesPerBatchRange = new SportRange(5, 5);
//        $sportVariantsWithFields = [
//            $this->getSingleSportVariantWithFields(1, 2, 2),
//            $this->getSingleSportVariantWithFields(1, 2, 2),
//            $this->getSingleSportVariantWithFields(1, 2, 2),
//            $this->getSingleSportVariantWithFields(1, 2, 2),
//            $this->getSingleSportVariantWithFields(1, 2, 2)
//        ];
//        $input = $this->createInput(
//            [11],
//            $sportVariantsWithFields,
//            GamePlaceStrategy::EquallyAssigned,
//            new RefereeInfo(0)
//        );
//        $planning = $this->createPlanning($input, $nrOfGamesPerBatchRange, 0, true, true);
//
//        self::assertLessThan(8, $planning->getNrOfBatches());
//
//        (new PlanningOutput())->outputWithGames($planning, true);
//
//        $planningValidator = new PlanningValidator();
//        $validity = $planningValidator->validate($planning);
//        self::assertSame(PlanningValidator::VALID, $validity);
//    }
//
//    // [5,4,4] - [against(1vs1) h2h:gpp=>2:0 f(6)] - gpstrat=>eql - ref=>0:
//    public function testAgainstSportUnbalancedStructure(): void
//    {
//        $nrOfGamesPerBatchRange = new SportRange(4, 6);
//        $sportVariantsWithFields = [
//            $this->getAgainstSportVariantWithFields(6, 1, 1, 2)
//        ];
//        $input = $this->createInput(
//            [5, 4, 4],
//            $sportVariantsWithFields,
//            GamePlaceStrategy::EquallyAssigned,
//            new RefereeInfo(0)
//        );
//        $planning = $this->createPlanning($input, $nrOfGamesPerBatchRange,0, true, true);
//
//        self::assertLessThan(8, $planning->getNrOfBatches());
//
//        (new PlanningOutput())->outputWithGames($planning, true);
//
//        $planningValidator = new PlanningValidator();
//        $validity = $planningValidator->validate($planning);
//        self::assertSame(PlanningValidator::VALID, $validity);
//    }
}
