<?php
declare(strict_types=1);

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SelfReferee;
use SportsHelpers\SportRange;
use SportsPlanning\Combinations\GamePlaceStrategy;
use SportsPlanning\Planning\Validator as PlanningValidator;
use SportsPlanning\Planning\Output as PlanningOutput;
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
            $this->createInput([5,4,4,4,4,4], [$sportVariantsWithFields], GamePlaceStrategy::EquallyAssigned, 0, SelfReferee::SAMEPOULE),
            $nrOfGamesPerBatchRange
        );

        // (new PlanningOutput())->outputWithGames($planning, true);

        $planningValidator = new PlanningValidator();
        $validity = $planningValidator->validate($planning);
        self::assertSame(PlanningValidator::VALID, $validity);

        //(new PlanningOutput())->outputWithGames($planning, true);
        // echo "============ " . (microtime(true) - $time_start);

        self::assertLessThan(0.5, microtime(true) - $time_start);
    }
}
