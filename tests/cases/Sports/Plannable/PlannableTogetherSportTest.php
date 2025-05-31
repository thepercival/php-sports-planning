<?php

namespace SportsPlanning\Tests\Sports\Plannable;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Input;
use SportsPlanning\Referee\PlanningRefereeInfo;
use SportsPlanning\Sports\Plannable\TogetherSportWithNrAndFields;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsPlanning\TestHelper\PlanningCreator;

class PlannableTogetherSportTest extends TestCase
{
    use PlanningCreator;

    public function testConstruct(): void
    {
        $togetherSport = new TogetherSport(2);
        $nrOfFields = 1;
        $input = new Input(
            new \SportsPlanning\PlanningConfiguration(
                new PouleStructure(3),
                [new SportWithNrOfFieldsAndNrOfCycles($togetherSport, $nrOfFields, 1)],
                new PlanningRefereeInfo(),
                false
            )
        );

        $plannableTogetherSport = new TogetherSportWithNrAndFields(1, $togetherSport, $nrOfFields);
        self::assertSame($togetherSport, $plannableTogetherSport->sport);
        self::assertSame($nrOfFields, count($plannableTogetherSport->fields));
    }
}
