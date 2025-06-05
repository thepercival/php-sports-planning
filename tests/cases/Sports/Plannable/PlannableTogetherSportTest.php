<?php

namespace SportsPlanning\Tests\Sports\Plannable;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Sports\SportsWithNrAndFields\TogetherSportWithNrAndFields;
use SportsPlanning\TestHelper\PlanningCreator;

final class PlannableTogetherSportTest extends TestCase
{
    use PlanningCreator;

    public function testConstruct(): void
    {
        $togetherSport = new TogetherSport(2);
        $nrOfFields = 1;
//        new PlanningOrchestration(
//            new PlanningConfiguration(
//                new PouleStructure(3),
//                [new SportWithNrOfFieldsAndNrOfCycles($togetherSport, $nrOfFields, 1)],
//                new PlanningRefereeInfo(),
//                false
//            )
//        );

        $plannableTogetherSport = new TogetherSportWithNrAndFields(1, $togetherSport, $nrOfFields);
        self::assertSame($togetherSport, $plannableTogetherSport->sport);
        self::assertSame($nrOfFields, count($plannableTogetherSport->fields));
    }
}
