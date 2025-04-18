<?php

namespace SportsPlanning\Tests\Schedule;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\ScheduleAgainstOneVsOne;
use SportsPlanning\Schedule\ScheduleSport;

class ScheduleSportTest extends TestCase
{
    public function testGetSchedule(): void
    {
        $schedule = new Schedule(5);
        $scheduleSport = new ScheduleAgainstOneVsOne($schedule, 1, new AgainstOneVsOne());
        self::assertSame($schedule, $scheduleSport->getSchedule() );
    }

    public function testGetNumber(): void
    {
        $schedule = new Schedule(5);
        $scheduleSport = new ScheduleAgainstOneVsOne($schedule, 1, new AgainstOneVsOne());
        self::assertSame(1, $scheduleSport->getNumber() );
    }

    public function testGetGames(): void
    {
        $schedule = new Schedule(5);
        $scheduleSport = new ScheduleAgainstOneVsOne($schedule, 1, new AgainstOneVsOne());
        self::assertCount(0, $scheduleSport->getGames() );
    }
}
