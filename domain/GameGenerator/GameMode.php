<?php
declare(strict_types=1);

namespace SportsPlanning\GameGenerator;

use SportsPlanning\Poule;
use SportsPlanning\Sport;

interface GameMode
{
    /**
     * @param Poule $poule
     * @param list<Sport> $sports
     */
    public function generate(Poule $poule, array $sports): void;
}