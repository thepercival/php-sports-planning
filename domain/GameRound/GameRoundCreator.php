<?php
declare(strict_types=1);

namespace SportsPlanning\GameRound;

use SportsPlanning\GameGenerator\AssignedCounter;
use SportsPlanning\Poule;

/**
 * @template T
 */
interface GameRoundCreator
{
    /**
       * @param Poule $poule
       * @param AssignedCounter $assignedCounter
       * @param int $totalNrOfGamesPerPlace
       * @return T
       */
    public function createGameRound(
        Poule $poule,
        AssignedCounter $assignedCounter,
        int $totalNrOfGamesPerPlace
    ): mixed;
}
