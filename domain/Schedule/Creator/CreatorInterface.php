<?php
declare(strict_types=1);

namespace SportsPlanning\Schedule\Creator;

use SportsPlanning\Schedule;
use SportsPlanning\Poule;
use SportsPlanning\Sport;

interface CreatorInterface
{
    /**
     * @param Poule $poule
     * @param list<Sport> $sports
     * @param AssignedCounter $assignedCounter
     * @return Schedule
     */
    public function create(Poule $poule, array $sports, AssignedCounter $assignedCounter): Schedule;
}