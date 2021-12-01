<?php
declare(strict_types=1);

namespace SportsPlanning\Tests\Resource\RefereePlace;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SelfReferee;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;
use SportsPlanning\Combinations\GamePlaceStrategy;
use SportsPlanning\Resource\RefereePlace\Predicter;
use SportsPlanning\TestHelper\PlanningCreator;
use SportsPlanning\TestHelper\PlanningReplacer;

class PredicterTest extends TestCase
{
    use PlanningCreator, PlanningReplacer;

    public function testSamePouleEnoughRefereePlaces(): void
    {
        $planning = $this->createPlanning(
            $this->createInput([3], null, GamePlaceStrategy::EquallyAssigned, null, SelfReferee::SamePoule)
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
        self::expectException(\Exception::class);
        $this->createPlanning(
            $this->createInput([2], null, GamePlaceStrategy::EquallyAssigned, null, SelfReferee::SamePoule)
        );
    }

    public function testOtherPoulesEnoughRefereePlaces(): void
    {
        $planning = $this->createPlanning(
            $this->createInput([3, 3], null, GamePlaceStrategy::EquallyAssigned, null, SelfReferee::OtherPoules)
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
