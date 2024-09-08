<?php

namespace SportsPlanning\Tests\Schedule;

use PHPUnit\Framework\TestCase;
use SportsHelpers\SportVariants\AgainstH2h;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\ScheduleSport;

class ScheduleSportTest extends TestCase
{
    public function testGetSchedule(): void
    {
        $schedule = new Schedule(5, []);
        $againstVariant = new AgainstH2h(1, 1, 1 );
        $scheduleSport = new ScheduleSport($schedule, 1, $againstVariant->toPersistVariant());
        self::assertSame($schedule, $scheduleSport->getSchedule() );
    }

    public function testGetNumber(): void
    {
        $schedule = new Schedule(5, []);
        $againstVariant = new AgainstH2h(1, 1, 1 );
        $scheduleSport = new ScheduleSport($schedule, 1, $againstVariant->toPersistVariant());
        self::assertSame(1, $scheduleSport->getNumber() );
    }

    public function testGetGames(): void
    {
        $schedule = new Schedule(5, []);
        $againstVariant = new AgainstH2h(1, 1, 1 );
        $scheduleSport = new ScheduleSport($schedule, 1, $againstVariant->toPersistVariant());
        self::assertCount(0, $scheduleSport->getGames() );
    }
}
