<?php

namespace SportsPlanning\Tests\Schedules\Sports;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsPlanning\Schedules\ScheduleWithNrOfPlaces;
use SportsPlanning\Schedules\Sports\ScheduleAgainstOneVsOne;

final class ScheduleAgainstOneVsOneTest extends TestCase
{
    public function testGetScheduleWithNrOfPlaces(): void
    {
        $schedule = new ScheduleWithNrOfPlaces(5, []);
        $sportSchedule = new ScheduleAgainstOneVsOne($schedule, 1, new AgainstOneVsOne(), 1);
        self::assertSame($schedule, $sportSchedule->scheduleWithNrOfPlaces );
    }

    public function testGetNumber(): void
    {
        $schedule = new ScheduleWithNrOfPlaces(5, []);
        $scheduleSport = new ScheduleAgainstOneVsOne($schedule, 1, new AgainstOneVsOne(), 1);
        self::assertSame(1, $scheduleSport->number );
    }
}
