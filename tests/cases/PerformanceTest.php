<?php

declare(strict_types=1);

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SelfReferee;
use SportsHelpers\SportRange;
use SportsPlanning\Combinations\GamePlaceStrategy;
use SportsPlanning\Planning\Validator as PlanningValidator;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsPlanning\TestHelper\PlanningCreator;

class PerformanceTest extends TestCase
{
    use PlanningCreator;

    // [5,4,4,4,4,4] - [against(1vs1) h2h:gpp=>1:0 f(6)] - gpstrat=>eql - ref=>0:SP
    public function testUnbalancedHighMinNrOfBatchGames(): void
    {
        $time_start = microtime(true);
        $nrOfGamesPerBatchRange = new SportRange(4, 4);
        $sportVariantsWithFields = $this->getAgainstSportVariantWithFields(6);
        $planning = $this->createPlanning(
            $this->createInput(
                [5, 4, 4, 4, 4, 4],
                [$sportVariantsWithFields],
                GamePlaceStrategy::EquallyAssigned,
                new RefereeInfo(SelfReferee::SamePoule)
            ),
            $nrOfGamesPerBatchRange
        );

        // (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);

        //(new PlanningOutput())->outputWithGames($planning, true);
        // echo "============ " . (microtime(true) - $time_start);

        self::assertLessThan(0.6, microtime(true) - $time_start);
    }

    public function testSelfRefereeRange7to7(): void
    {
        $time_start = microtime(true);
        $nrOfGamesPerBatchRange = new SportRange(7, 7);
        $sportVariantsWithFields = $this->getAgainstSportVariantWithFields(9);
        $planning = $this->createPlanning(
            $this->createInput(
                [7, 7, 7, 7],
                [$sportVariantsWithFields],
                GamePlaceStrategy::EquallyAssigned,
                new RefereeInfo(SelfReferee::SamePoule)
            ),
            $nrOfGamesPerBatchRange/*,
            0, false, true*/
        );

        // (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);
//
        // (new PlanningOutput())->outputWithGames($planning, true);
//        (new BatchOutput())->output($planning->createFirstBatch(), null, null, null, true);
//        echo "============ " . (microtime(true) - $time_start);

//        (new PlanningOutput())->outputWithTotals($planning,  false);

//
        self::assertLessThan(1.5, microtime(true) - $time_start);
    }

    // [7,7,7,7] - [against(1vs1) h2h:gpp=>1:0 f(9)] - gpstrat=>eql - ref=>0:SP
    public function testSelfReferee(): void
    {
        $time_start = microtime(true);
        $nrOfGamesPerBatchRange = new SportRange(8, 8);
        $sportVariantsWithFields = $this->getAgainstSportVariantWithFields(9);
        $planning = $this->createPlanning(
            $this->createInput(
                [7, 7, 7, 7],
                [$sportVariantsWithFields],
                GamePlaceStrategy::EquallyAssigned,
                new RefereeInfo(SelfReferee::SamePoule)
            ),
            $nrOfGamesPerBatchRange,
            4
        );

//        (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);
//
        // (new PlanningOutput())->outputWithGames($planning, true);
//        (new BatchOutput())->output($planning->createFirstBatch(), null, null, null, true);
//         echo "============ " . (microtime(true) - $time_start);
//
//        (new PlanningOutput())->outputWithTotals($planning,  false);

//
        self::assertLessThan(2, microtime(true) - $time_start);
    }

    // [2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2] - [against(1vs1) h2h:gpp=>1:0 f(4)] - gpstrat=>eql - ref=>0:
    public function testOnMinNrOfBatches(): void
    {
        // $time_start = microtime(true);
        $nrOfGamesPerBatchRange = new SportRange(4, 4);
        $sportVariantsWithFields = $this->getAgainstSportVariantWithFields(4);
        $input = $this->createInput(
            [2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2, 2],
            // 16 poules, 8 wedstrijden => 4 velden dus 4 wedstrijden dus 4 batches
            [$sportVariantsWithFields],
            GamePlaceStrategy::EquallyAssigned,
            new RefereeInfo(0)
        );
        $planning = $this->createPlanning($input, $nrOfGamesPerBatchRange, 0, true);
        self::assertEquals(
            '[2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2] - [against(1vs1) h2h:gpp=>1:0 f(4)] - gpstrat=>eql - ref=>0:',
            $input->getUniqueString()
        );

//        (new PlanningOutput())->outputWithGames($planning, true);

//        $planningValidator = new PlanningValidator();
//        $validity = $planningValidator->validate($planning);
//        self::assertSame(PlanningValidator::VALID, $validity);
//
        // (new PlanningOutput())->outputWithGames($planning, true);
//        (new BatchOutput())->output($planning->createFirstBatch(), null, null, null, true);
//         echo "============ " . (microtime(true) - $time_start);
//
//        (new PlanningOutput())->outputWithTotals($planning,  false);

//
        self::assertEquals(4, $planning->createFirstBatch()->getLeaf()->getNumber());
    }
}
