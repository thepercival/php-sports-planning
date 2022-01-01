<?php

declare(strict_types=1);

namespace SportsPlanning\Schedule\Creator;

use SportsPlanning\Poule;
use SportsPlanning\Schedule;
use SportsPlanning\Sport;

interface CreatorInterface
{
    /**
     * @param Schedule $schedule
     * @param Poule $poule
     * @param list<Sport> $sports
     * @param AssignedCounter $assignedCounter
     */
    public function createSportSchedules(Schedule $schedule, Poule $poule, array $sports, AssignedCounter $assignedCounter): void;
}
