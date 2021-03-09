<?php

namespace SportsPlanning\Tests\Resource\RefereePlace;

use SportsPlanning\Resource\RefereePlace\Predicter;
use SportsPlanning\SelfReferee;
use SportsPlanning\TestHelper\PlanningCreator;
use SportsPlanning\TestHelper\PlanningReplacer;

class PredicterTest extends \PHPUnit\Framework\TestCase
{
    use PlanningCreator, PlanningReplacer;

    public function testSamePouleEnoughRefereePlaces()
    {
        $planning = $this->createPlanning(
            $this->createInputNew([3], null, null, SelfReferee::SAMEPOULE)
        );

        $predicter = new Predicter($planning->getPoules()->toArray());
        $canStillAssign = $predicter->canStillAssign($planning->createFirstBatch(), SelfReferee::SAMEPOULE);
        self::assertTrue($canStillAssign);
    }

    public function testSamePouleNotEnoughRefereePlaces()
    {
        self::expectException(\Exception::class);
        $planning = $this->createPlanning(
            $this->createInputNew([2], null, null, SelfReferee::SAMEPOULE)
        );
    }

    public function testOtherPoulesEnoughRefereePlaces()
    {
        $planning = $this->createPlanning(
            $this->createInputNew([3,3], null, null, SelfReferee::OTHERPOULES)
        );

        $predicter = new Predicter($planning->getPoules()->toArray());
        $canStillAssign = $predicter->canStillAssign($planning->createFirstBatch(), SelfReferee::OTHERPOULES);
        self::assertTrue($canStillAssign);
    }
}
