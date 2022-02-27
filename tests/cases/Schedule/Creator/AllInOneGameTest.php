<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Schedule\Creator;

use SportsPlanning\Schedule\Output as ScheduleOutput;
use SportsPlanning\Game\Creator as GameCreator;
use SportsPlanning\Schedule\Creator as ScheduleCreator;
use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsPlanning\Planning;
use SportsPlanning\Planning\Output as PlanningOutput;
use SportsPlanning\TestHelper\PlanningCreator;

class AllInOneGameTest extends TestCase
{
    use PlanningCreator;

    public function testSimple(): void
    {
        $sportVariant = $this->getAllInOneGameSportVariantWithFields(2, 3);
        $input = $this->createInput([3, 3, 3], [$sportVariant]);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreator = new ScheduleCreator($this->getLogger());
        $schedules = $scheduleCreator->createFromInput($input);
        // (new ScheduleOutput($this->getLogger()))->output($schedules);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        //(new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(9, $planning->getTogetherGames());
    }
}
