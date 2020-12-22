<?php

namespace SportsPlanning\Tests\Resource\RefereePlace;

use SportsPlanning\Resource\RefereePlace\Predicter;
use SportsPlanning\Planning;
use SportsPlanning\Input;
use SportsPlanning\TestHelper\PlanningCreator;
use SportsPlanning\TestHelper\PlanningReplacer;

class PredicterTest extends \PHPUnit\Framework\TestCase
{
    use PlanningCreator, PlanningReplacer;

    public function testSamePouleEnoughRefereePlaces()
    {
        $planning = $this->createPlanning(
            $this->createInput( [3], null, null, null, Input::SELFREFEREE_SAMEPOULE )
        );

        $predicter = new Predicter($planning->getPoules()->toArray());
        $canStillAssign = $predicter->canStillAssign($planning->createFirstBatch(), Input::SELFREFEREE_SAMEPOULE);
        self::assertTrue($canStillAssign);
    }

    public function testSamePouleNotEnoughRefereePlaces()
    {
        self::expectException(\Exception::class);
        $planning = $this->createPlanning(
            $this->createInput( [2], null, null, null, Input::SELFREFEREE_SAMEPOULE )
        );
    }

    public function testOtherPoulesEnoughRefereePlaces()
    {
        $planning = $this->createPlanning(
            $this->createInput( [3,3], null, null, null, Input::SELFREFEREE_OTHERPOULES )
        );

        $predicter = new Predicter($planning->getPoules()->toArray());
        $canStillAssign = $predicter->canStillAssign($planning->createFirstBatch(), Input::SELFREFEREE_OTHERPOULES);
        self::assertTrue($canStillAssign);
    }
}
