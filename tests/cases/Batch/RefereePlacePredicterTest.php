<?php

namespace SportsPlanning\Tests\Batch;

use SportsPlanning\Output\Planning as PlanningOutput;
use SportsPlanning\Output\Planning\Batch as PlanningBatchOutput;
use SportsPlanning\Batch;
use SportsPlanning\Batch\RefereePlacePredicter;
use SportsPlanning\Field;
use SportsPlanning;
use SportsPlanning\Input;
use SportsPlanning\Resource\RefereePlace\Service as RefereePlaceService;
use SportsPlanning\TestHelper\PlanningCreator;
use SportsPlanning\TestHelper\PlanningReplacer;
use SportsPlanning\Structure\Service as StructureService;
use SportsPlanning\Validator as PlanningValidator;
use SportsPlanning\Game;
use SportsPlanning\Game as GameBase;
use SportsPlanning\Referee as PlanningReferee;
use SportsPlanning\Place as PlanningPlace;
use SportsPlanning\Field as PlanningField;
use SportsPlanning\Referee;
use Exception;

class RefereePlacePredicterTest extends \PHPUnit\Framework\TestCase
{
    use PlanningCreator, PlanningReplacer;

    public function testSamePouleEnoughRefereePlaces()
    {
        $planning = $this->createPlanning(
            $this->createInput( [3] )
        );

        $predicter = new RefereePlacePredicter($planning->getPoules());
        $canStillAssign = $predicter->canStillAssign($planning->createFirstBatch(), Input::SELFREFEREE_SAMEPOULE);
        self::assertTrue($canStillAssign);
    }

    public function testSamePouleNotEnoughRefereePlaces()
    {
        $planning = $this->createPlanning(
            $this->createInput( [2] )
        );

        $predicter = new RefereePlacePredicter($planning->getPoules());
        $canStillAssign = $predicter->canStillAssign($planning->createFirstBatch(), Input::SELFREFEREE_SAMEPOULE);
        self::assertFalse($canStillAssign);
    }

    public function testOtherPoulesEnoughRefereePlaces()
    {
        $planning = $this->createPlanning(
            $this->createInput( [3,3] )
        );

        $predicter = new RefereePlacePredicter($planning->getPoules());
        $canStillAssign = $predicter->canStillAssign($planning->createFirstBatch(), Input::SELFREFEREE_OTHERPOULES);
        self::assertTrue($canStillAssign);
    }

    public function testOtherPoulesNotEnoughRefereePlaces()
    {
        $planning = $this->createPlanning(
            $this->createInput( [2,2] )
        );

        $predicter = new RefereePlacePredicter($planning->getPoules());
        $canStillAssign = $predicter->canStillAssign($planning->createFirstBatch(), Input::SELFREFEREE_OTHERPOULES);
        self::assertFalse($canStillAssign);
    }
}
