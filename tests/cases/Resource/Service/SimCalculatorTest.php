<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Resource\Service;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SelfReferee;
use SportsHelpers\SportRange;
use SportsPlanning\Planning\Output as PlanningOutput;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsPlanning\Resource\Service\InfoToAssign;
use SportsPlanning\Resource\Service\SimCalculator;
use SportsPlanning\TestHelper\PlanningCreator;

class SimCalculatorTest extends TestCase
{
    use PlanningCreator;

    public function testMultipleUnknown(): void
    {
        $sportVariantsWithFields = [
            $this->getAgainstGppSportVariantWithFields(2, 1, 1, 9),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 9),
            $this->getAgainstGppSportVariantWithFields(1, 1, 1, 9),
        ];
        $refereeInfo = new RefereeInfo(SelfReferee::Disabled);
        $input = $this->createInput([10], $sportVariantsWithFields, $refereeInfo);
        $planning = $this->createPlanning($input, new SportRange(3, 3)/*, 0, true*/);

        $calculator = new SimCalculator($input);
        $infoToAssign = new InfoToAssign($planning->getGames());
        $maxNrOfSimultaneousGames = $calculator->getMaxNrOfGamesPerBatch($infoToAssign);

//        (new PlanningOutput())->outputWithGames($planning, true);

        self::assertSame(4, $maxNrOfSimultaneousGames);
    }
}
