<?php

namespace SportsPlanning\Tests\Schedule;

use PHPUnit\Framework\TestCase;
use SportsHelpers\Against\Side;
use SportsHelpers\SportVariants\AgainstH2h;
use SportsPlanning\Schedule;
use SportsPlanning\Schedule\ScheduleGame;
use SportsPlanning\Schedule\ScheduleSport;

class ScheduleGameTest extends TestCase
{
    public function testGetGameRundNumber(): void
    {
        $schedule = new Schedule(5, []);
        $againstVariant = new AgainstH2h(1, 1, 1 );
        $scheduleSport = new ScheduleSport($schedule, 1, $againstVariant->toPersistVariant());
        $game = new ScheduleGame($scheduleSport, 1);
        self::assertSame(1, $game->getGameRoundNumber() );
    }

    public function testGetGameRundNumberException(): void
    {
        $schedule = new Schedule(5, []);
        $againstVariant = new AgainstH2h(1, 1, 1 );
        $scheduleSport = new ScheduleSport($schedule, 1, $againstVariant->toPersistVariant());
        $game = new ScheduleGame($scheduleSport);
        self::expectException(\Exception::class);
        $game->getGameRoundNumber();
    }

    public function testGetSidePlaceNrs(): void
    {
        $schedule = new Schedule(5, []);
        $againstVariant = new AgainstH2h(1, 1, 1 );
        $scheduleSport = new ScheduleSport($schedule, 1, $againstVariant->toPersistVariant());
        $game = new ScheduleGame($scheduleSport, 1);
        $gamePlace = new Schedule\ScheduleGamePlace($game, 1);
        $gamePlace->setAgainstSide(Side::Home);
        self::assertCount(1, $game->getSidePlaceNrs(Side::Home) );
    }
}
