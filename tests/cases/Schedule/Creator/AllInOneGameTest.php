<?php

declare(strict_types=1);

namespace SportsPlanning\Tests\Schedule\Creator;

use SportsPlanning\Combinations\GamePlaceStrategy;
use SportsPlanning\Schedule\Output as ScheduleOutput;
use SportsPlanning\Game\Creator as GameCreator;
use SportsPlanning\Schedule\Creator\Service as ScheduleCreatorService;
use PHPUnit\Framework\TestCase;
use SportsHelpers\SportRange;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Game\Place\Together as TogetherGamePlace;
use SportsPlanning\Planning;
use SportsPlanning\TestHelper\PlanningCreator;

class AllInOneGameTest extends TestCase
{
    use PlanningCreator;

    public function testSimple(): void
    {
        $sportVariant = $this->getAllInOneGameSportVariantWithFields(2, 3);
        $input = $this->createInput([3,3,3], [$sportVariant]);
        $planning = new Planning($input, new SportRange(1, 1), 0);

        $scheduleCreatorService = new ScheduleCreatorService($this->getLogger());
        $schedules = $scheduleCreatorService->createSchedules($input);
        // (new ScheduleOutput($this->getLogger()))->output($schedules);
        $gameCreator = new GameCreator($this->getLogger());
        $gameCreator->createGames($planning, $schedules);
        // (new PlanningOutput())->outputWithGames($planning, true);

        self::assertCount(9, $planning->getTogetherGames());
    }
}
