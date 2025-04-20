<?php

namespace SportsPlanning\Tests\Schedules\Sports;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;
use SportsPlanning\Schedules\Sports\ScheduleAgainstOneVsOne;

class ScheduleAgainstOneVsOneTest extends TestCase
{
    public function testGetSchedule(): void
    {
        $schedule = new ScheduleWithNrOfPlaces(5, []);
        $scheduleSport = new ScheduleAgainstOneVsOne($schedule, 1, new AgainstOneVsOne());
        self::assertSame($schedule, $scheduleSport->schedule );
    }

    public function testGetNumber(): void
    {
        $schedule = new ScheduleWithNrOfPlaces(5, []);
        $scheduleSport = new ScheduleAgainstOneVsOne($schedule, 1, new AgainstOneVsOne());
        self::assertSame(1, $scheduleSport->number );
    }

    public function testGetGames(): void
    {
        $schedule = new ScheduleWithNrOfPlaces(5, []);
        $scheduleSport = new ScheduleAgainstOneVsOne($schedule, 1, new AgainstOneVsOne());
        self::assertCount(0, $scheduleSport->getGames() );
    }
}
