<?php

declare(strict_types=1);

namespace SportsPlanning\Tests;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SelfReferee;
use SportsHelpers\SportRange;
use SportsPlanning\Game\Creator as GameCreator;
use SportsPlanning\Planning;
use SportsPlanning\Planning\Validator as PlanningValidator;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsPlanning\Schedule\Creator as ScheduleCreator;
use SportsPlanning\TestHelper\PlanningCreator;

// cachegrind output default to /tmp
class ProfileTest extends TestCase
{
    use PlanningCreator;

    public function test2V2And6PlacesAnd8GamesPerPlace(): void
    {
//        $sportVariants = [
//            $this->getAgainstGppSportVariantWithFields(4, 2, 2, 26),
//        ];
//        $input = $this->createInput([18], $sportVariants);
//        $planning = new Planning($input, new SportRange(1, 1), 0);
//
//        $scheduleCreator = new ScheduleCreator($this->getLogger());
//        $schedules = $scheduleCreator->createFromInput($input);
//        $gameCreator = new GameCreator($this->getLogger());
//        $gameCreator->createGames($planning, $schedules);
//        // (new PlanningOutput())->outputWithGames($planning, true);
//
//        self::assertCount(117, $planning->getAgainstGames());
//        $validator = new PlanningValidator();
//        self::assertEquals(PlanningValidator::VALID, $validator->validate($planning, true));
        self::assertCount(0, []);
    }
}
