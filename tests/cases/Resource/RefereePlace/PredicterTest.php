<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Resource\RefereePlace;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SelfReferee;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsPlanning\Resource\RefereePlace\Predicter;
use SportsPlanning\TestHelper\PlanningCreator;
use SportsPlanning\TestHelper\PlanningReplacer;

class PredicterTest extends TestCase
{
    use PlanningCreator;
    use PlanningReplacer;

    public function testSamePouleEnoughRefereePlaces(): void
    {
        $refereeInfo = new RefereeInfo(SelfReferee::SamePoule);
        $planning = $this->createPlanning(
            $this->createInput([3], null, $refereeInfo)
        );
        $poules = array_values($planning->getInput()->getPoules()->toArray());
        $predicter = new Predicter($poules);
        $firstBatch = $planning->createFirstBatch();
        self::assertTrue(
            $firstBatch instanceof SelfRefereeBatchSamePoule
            || $firstBatch instanceof SelfRefereeBatchOtherPoule
        );
        $canStillAssign = $predicter->canStillAssign($firstBatch, SelfReferee::SamePoule);
        self::assertTrue($canStillAssign);
    }

    public function testSamePouleNotEnoughRefereePlaces(): void
    {
        $refereeInfo = new RefereeInfo(SelfReferee::SamePoule);
        self::expectException(\Exception::class);
        $this->createPlanning(
            $this->createInput([2], null, $refereeInfo)
        );
    }

    public function testOtherPoulesEnoughRefereePlaces(): void
    {
        $refereeInfo = new RefereeInfo(SelfReferee::OtherPoules);
        $planning = $this->createPlanning(
            $this->createInput([3, 3], null, $refereeInfo)
        );
        $poules = array_values($planning->getInput()->getPoules()->toArray());
        $predicter = new Predicter($poules);
        $firstBatch = $planning->createFirstBatch();
        self::assertTrue(
            $firstBatch instanceof SelfRefereeBatchSamePoule
            || $firstBatch instanceof SelfRefereeBatchOtherPoule
        );
        $canStillAssign = $predicter->canStillAssign($firstBatch, SelfReferee::OtherPoules);
        self::assertTrue($canStillAssign);
    }
}
