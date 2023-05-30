<?php

declare(strict_types=1);

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsHelpers\SportRange;
use SportsPlanning\Planning\TimeoutConfig;
use SportsPlanning\Planning\Validator as PlanningValidator;
use SportsPlanning\Planning\Output as PlanningOutput;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsPlanning\TestHelper\PlanningCreator;

class ProductionErrorsTest extends TestCase
{
    use PlanningCreator;

    // [10,2,2] - [against(1vs1) h2h:gpp=>1:0 f(3)] - gpstrat=>eql - ref=>0:OP
    public function test1022(): void
    {
        $nrOfGamesPerBatchRange = new SportRange(1, 3);
        $sportVariantsWithFields = $this->getAgainstH2hSportVariantWithFields(3);
        $refereeInfo = new RefereeInfo(new SelfRefereeInfo(SelfReferee::OtherPoules));
        $planning = $this->createPlanning(
            $this->createInput(
                [10, 2, 2],
                [$sportVariantsWithFields],
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
    public function test18(): void
    {
        $nrOfGamesPerBatchRange = new SportRange(1, 1);
        $sportVariantsWithFields = $this->getAgainstH2hSportVariantWithFields(1, 1, 1, 2);
        $planning = $this->createPlanning(
            $this->createInput(
                [18],
                [$sportVariantsWithFields],
                new RefereeInfo()
            ),
            $nrOfGamesPerBatchRange
        );

        // (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);
    }

    // inputid: 65656 ([7,6] - [single(1) gpp=>5 f(5) & single(1) gpp=>5 f(5) & against(1vs1) h2h:gpp=>0:2 f(1)] - ref=>0:OP)
    public function test76With3Sports(): void
    {
        $nrOfGamesPerBatchRange = new SportRange(3, 6);
        $sportVariantsWithFields = [
            $this->getSingleSportVariantWithFields(5, 5, 1),
            $this->getSingleSportVariantWithFields(5, 5, 1),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 2)
        ];
        $planning = $this->createPlanning(
            $this->createInput(
                [7,6],
                $sportVariantsWithFields,
                new RefereeInfo(new SelfRefereeInfo(SelfReferee::OtherPoules))
            ),
            $nrOfGamesPerBatchRange
        );
//        (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);
    }


//    // [14,14] - [
//    //      against(1vs1) h2h:gpp=>0:13 f(2) &
//    //      against(1vs1) h2h:gpp=>0:13 f(2) &
//    //      against(1vs1) h2h:gpp=>0:13 f(2) &
//    //      against(1vs1) h2h:gpp=>0:13 f(2) &
//    //      against(1vs1) h2h:gpp=>0:13 f(2)] - ref=>0:
//    public function test1414(): void
//    {
//        $nrOfGamesPerBatchRange = new SportRange(10,10);
//        $sportVariantsWithFields = [
//            $this->getAgainstGppSportVariantWithFields(2, 1, 1, 13),
//            $this->getAgainstGppSportVariantWithFields(2, 1, 1, 13),
//            $this->getAgainstGppSportVariantWithFields(2, 1, 1, 13),
//            $this->getAgainstGppSportVariantWithFields(2, 1, 1, 13),
//            $this->getAgainstGppSportVariantWithFields(2, 1, 1, 13),
//        ];
//        $planning = $this->createPlanning(
//            $this->createInput(
//                [14,14],
//                $sportVariantsWithFields,
//                new RefereeInfo()
//            ),
//            $nrOfGamesPerBatchRange,
//            0,
//            true,
//            true
//        );
//
//    //        (new PlanningOutput())->outputWithGames($planning, true);
//
//        $planningValidator = new PlanningValidator();
//        $validity = $planningValidator->validate($planning);
//        self::assertSame(PlanningValidator::VALID, $validity);
//    }

//    public function test10(): void
//    {
//        $nrOfGamesPerBatchRange = new SportRange(5, 5);
//        $sportVariantsWithFields = [
//            $this->getAgainstGppSportVariantWithFields(2, 1, 1, 9),
//            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 9),
//            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 9)
//        ];
//        $refereeInfo = new RefereeInfo();
//        $planning = $this->createPlanning(
//            $this->createInput([10], $sportVariantsWithFields, $refereeInfo),
//            $nrOfGamesPerBatchRange,
//            0, false, false, null, 0
//        );
//
////        (new PlanningOutput())->outputWithGames($planning, true);
////        (new PlanningOutput())->outputWithTotals($planning, true);
//
//        $planningValidator = new PlanningValidator();
//        $validity = $planningValidator->validate($planning);
//        self::assertSame(PlanningValidator::VALID, $validity);
//    }

    // [5,5,5,5,5,5,5,5] - [against(1vs1) h2h:gpp=>1:0 f(14)] -  ref=>0:
    public function test14BatchGames(): void
    {
        $nrOfGamesPerBatchRange = new SportRange(14, 14);
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(14),
        ];
        $refereeInfo = new RefereeInfo();
        $input = $this->createInput(
            [5, 5, 5, 5, 5, 5, 5, 5],
            $sportVariantsWithFields,
            $refereeInfo
        );
        $planning = $this->createPlanning(
            $input,
            $nrOfGamesPerBatchRange,
            0,
            false,
            false,
            (new TimeoutConfig())->nextTimeoutState(null)
        );

//        (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);
    }

//     [8] - [
//        against(1vs1) h2h:gpp=>0:7 f(1) &
//        against(1vs1) h2h:gpp=>0:7 f(1) &
//        against(1vs1) h2h:gpp=>0:7 f(1) &
//        against(1vs1) h2h:gpp=>0:7 f(1) &
//        against(1vs1) h2h:gpp=>0:7 f(1) -
//     ref=>0:
//    public function test5Sports8Places(): void
//    {
//        $nrOfGamesPerBatchRange = new SportRange(4, 4);
//        $sportVariantsWithFields = [
//            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7),
//            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7),
//            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7),
//            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7),
//            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 7)
//        ];
//        $refereeInfo = new RefereeInfo();
//        $input = $this->createInput(
//            [8],
//            $sportVariantsWithFields,
//            $refereeInfo
//        );
//        $planning = $this->createPlanning($input, $nrOfGamesPerBatchRange,
//                                          0/*,
//                                          true,
//                                          true,
//                                          (new TimeoutConfig())->nextTimeoutState(null)*/
//        );
//
//        self::assertLessThanOrEqual(40, $planning->getNrOfBatches());
//
//
//    //        (new PlanningOutput())->outputWithGames($planning, true);
//
//        $planningValidator = new PlanningValidator();
//        $validity = $planningValidator->validate($planning);
//        self::assertSame(PlanningValidator::VALID, $validity);
//    }

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
            new RefereeInfo()
        );
        $planning = $this->createPlanning(
            $input,
            $nrOfGamesPerBatchRange/*,
                      0,
                      true,
                                          true*/
        );

        self::assertLessThan(6, $planning->getNrOfBatches());

//        (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);
    }

    //  [7,6] - [against(1vs1) h2h:gpp=>1:0 f(6)] - ref=>0:
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
            new RefereeInfo()
        );
        $planning = $this->createPlanning($input, $nrOfGamesPerBatchRange);

        self::assertLessThan(8, $planning->getNrOfBatches());

//        (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);
    }

    // [7,7,6,6] - [against(1vs1) h2h:gpp=>1:0 f(8)] - ref=>0:
    public function testBatchDiffProd(): void
    {
        $nrOfGamesPerBatchRange = new SportRange(8, 8);
        $sportVariantsWithFields = [
            $this->getAgainstH2hSportVariantWithFields(8)
        ];
        $input = $this->createInput(
            [7, 7, 6, 6],
            $sportVariantsWithFields,
            new RefereeInfo()
        );
        $planning = $this->createPlanning(
            $input,
            $nrOfGamesPerBatchRange/*,
            0,
            false,
            false,
            (new TimeoutConfig())->nextTimeoutState(null)*/
        );

        self::assertEquals(9, $planning->getNrOfBatches());

//        (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);
    }

    // [5] - [against(2vs2) h2h:gpp=>0:1 f(1)] - ref=>0:
    public function testCDK(): void
    {
        $nrOfGamesPerBatchRange = new SportRange(8, 8);
        $sportVariantsWithFields = [
            $this->getAgainstGppSportVariantWithFields(1, 2, 2, 1)
        ];
        $input = $this->createInput(
            [5],
            $sportVariantsWithFields,
            new RefereeInfo()
        );
        $planning = $this->createPlanning(
            $input,
            $nrOfGamesPerBatchRange/*,
            0,
            false,
            false,
            (new TimeoutConfig())->nextTimeoutState(null)*/
        );

        self::assertEquals(1, $planning->getNrOfBatches());

//        (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);
    }

    // ----------------     NOT OK FROM HERE   --------------------------------

    // [13] -
    // [against(1vs1) h2h:gpp=>0:1 f(1) & against(1vs1) h2h:gpp=>0:1 f(1)
    // & against(1vs1) h2h:gpp=>0:1 f(1) & against(1vs1) h2h:gpp=>0:1 f(1)] -
    // ref=>0:
//    public function test4SingleSports11Places(): void
//    {
//        $nrOfGamesPerBatchRange = new SportRange(4, 4);
//        $sportVariantsWithFields = [
//            $this->getAgainstGppSportVariantWithFields(1,1,1, 1),
//            $this->getAgainstGppSportVariantWithFields(1,1,1, 1),
//            $this->getAgainstGppSportVariantWithFields(1,1,1, 1),
//            $this->getAgainstGppSportVariantWithFields(1,1,1, 1)
//        ];
//        $input = $this->createInput(
//            [11],
//            $sportVariantsWithFields,
//            new RefereeInfo()
//        );
//        $planning = $this->createPlanning(
//            $input,
//            $nrOfGamesPerBatchRange,
//            0,
//            true,
//            true/*,
//            (new TimeoutConfig())->nextTimeoutState(null)*/
//        );
//
//        // 6 games x 5 sports = 30 games / 5 = 6 batches
//        self::assertLessThan(12, $planning->getNrOfBatches());
//
//        (new PlanningOutput())->outputWithGames($planning, true);
//
//        $planningValidator = new PlanningValidator();
//        $validity = $planningValidator->validate($planning);
//        self::assertSame(PlanningValidator::VALID, $validity);
//    }

    // [11] - [single(2) gpp=>2 f(1) & single(2) gpp=>2 f(1) & single(2) gpp=>2 f(1) & single(2) gpp=>2 f(1) & single(2) gpp=>2 f(1)] - gpstrat=>eql - ref=>0:
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
//            new RefereeInfo()
//        );
//        $planning = $this->createPlanning(
//            $input,
//            $nrOfGamesPerBatchRange,
//            0,
//            true,
//            true
//            // ,(new TimeoutConfig())->nextTimeoutState(null)
//        );
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
    // [5,4,4] - [against(1vs1) h2h:gpp=>2:0 f(6)] - gpstrat=>eql - ref=>0:
//    public function testAgainstSportUnbalancedStructure(): void
//    {
//        $nrOfGamesPerBatchRange = new SportRange(4, 6);
//        $sportVariantsWithFields = [
//            $this->getAgainstH2hSportVariantWithFields(6, 1, 1, 2)
//        ];
//        $input = $this->createInput(
//            [5, 4, 4],
//            $sportVariantsWithFields,
//            new RefereeInfo()
//        );
//        $planning = $this->createPlanning($input, $nrOfGamesPerBatchRange,
//                                          0,
//                                          true,
//                                          true,
//                                null);
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
