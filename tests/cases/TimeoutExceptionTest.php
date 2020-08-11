<?php

namespace SportsPlanning\Tests\Planning;

use Voetbal\Output\Planning as PlanningOutput;
use Voetbal\Output\Planning\Batch as PlanningBatchOutput;
use SportsPlanning\Batch;
use Voetbal\Field;
use SportsPlanning;
use SportsPlanning\Input;
use SportsPlanning\Resource\RefereePlace\Service as RefereePlaceService;
use Voetbal\TestHelper\CompetitionCreator;
use Voetbal\TestHelper\PlanningCreator;
use Voetbal\TestHelper\PlanningReplacer;
use Voetbal\Structure\Service as StructureService;
use SportsPlanning\Validator as PlanningValidator;
use SportsPlanning\Game;
use Voetbal\Game as GameBase;
use SportsPlanning\Referee as PlanningReferee;
use SportsPlanning\Place as PlanningPlace;
use SportsPlanning\Field as PlanningField;
use Voetbal\Referee;
use Exception;

class TimeoutExceptionTest extends \PHPUnit\Framework\TestCase
{
    use CompetitionCreator, PlanningCreator, PlanningReplacer;

    public function testThrow()
    {
        self::expectException(Planning\TimeoutException::class);
        throw new Planning\TimeoutException("just a test", E_ERROR);
    }
}
