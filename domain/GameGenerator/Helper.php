<?php
declare(strict_types=1);

namespace SportsPlanning\GameGenerator;

use SportsPlanning\Poule;
use SportsPlanning\Sport;

interface Helper
{
    /**
     * @param Poule $poule
     * @param list<Sport> $sports
     * @param AssignedCounter $assignedCounter
     */
    public function generate(Poule $poule, array $sports, AssignedCounter $assignedCounter): void;
}