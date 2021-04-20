<?php
declare(strict_types=1);

namespace SportsPlanning\Tests\Resource\RefereePlace;

use PHPUnit\Framework\TestCase;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeBatchOtherPoule;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeBatchSamePoule;
use SportsPlanning\Resource\RefereePlace\Predicter;
use SportsHelpers\SelfReferee;
use SportsPlanning\TestHelper\PlanningCreator;
use SportsPlanning\TestHelper\PlanningReplacer;

class PredicterTest extends TestCase
{
    use PlanningCreator, PlanningReplacer;

    public function testSamePouleEnoughRefereePlaces(): void
    {
        $planning = $this->createPlanning(
            $this->createInput([3], null, null, SelfReferee::SAMEPOULE)
        );
        $poules = array_values($planning->getInput()->getPoules()->toArray());
        $predicter = new Predicter($poules);
        $firstBatch = $planning->createFirstBatch();
        self::assertTrue($firstBatch instanceof SelfRefereeBatchSamePoule
                         || $firstBatch instanceof SelfRefereeBatchOtherPoule);
        $canStillAssign = $predicter->canStillAssign($firstBatch, SelfReferee::SAMEPOULE);
        self::assertTrue($canStillAssign);
    }

    public function testSamePouleNotEnoughRefereePlaces(): void
    {
        self::expectException(\Exception::class);
        $this->createPlanning(
            $this->createInput([2], null, null, SelfReferee::SAMEPOULE)
        );
    }

    public function testOtherPoulesEnoughRefereePlaces(): void
    {
        $planning = $this->createPlanning(
            $this->createInput([3,3], null, null, SelfReferee::OTHERPOULES)
        );
        $poules = array_values($planning->getInput()->getPoules()->toArray());
        $predicter = new Predicter($poules);
        $firstBatch = $planning->createFirstBatch();
        self::assertTrue($firstBatch instanceof SelfRefereeBatchSamePoule
                         || $firstBatch instanceof SelfRefereeBatchOtherPoule);
        $canStillAssign = $predicter->canStillAssign($firstBatch, SelfReferee::OTHERPOULES);
        self::assertTrue($canStillAssign);
    }
}
