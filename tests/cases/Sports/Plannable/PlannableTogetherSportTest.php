<?php

namespace SportsPlanning\Tests\Sports\Plannable;

use PHPUnit\Framework\TestCase;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Input;
use SportsPlanning\Referee\PlanningRefereeInfo;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;
use SportsPlanning\Schedules\Sports\ScheduleAgainstOneVsOne;
use SportsPlanning\Sports\Plannable\PlannableTogetherSport;
use SportsPlanning\Sports\SportWithNrOfFieldsAndNrOfCycles;
use SportsPlanning\TestHelper\PlanningCreator;

class PlannableTogetherSportTest extends TestCase
{
    use PlanningCreator;

    public function testConstruct(): void
    {
        $togetherSport = new TogetherSport(2);
        $input = new Input(
            new Input\Configuration(
                new PouleStructure(3),
                [new SportWithNrOfFieldsAndNrOfCycles($togetherSport, 1, 1)],
                new PlanningRefereeInfo(),
                false
            )
        );

        $plannableTogetherSport = new PlannableTogetherSport($togetherSport, 1, $input);
        self::assertSame($input, $plannableTogetherSport->getInput());
        self::assertSame($togetherSport, $plannableTogetherSport->sport);
    }
}
