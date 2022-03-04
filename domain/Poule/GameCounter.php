<?php

declare(strict_types=1);

namespace SportsPlanning\Poule;

use SportsHelpers\Counter;
use SportsPlanning\Poule;

/**
 * @template-extends Counter<Poule>
 */
class GameCounter extends Counter
{
    public function __construct(Poule $poule, int $nrOfGames = 0)
    {
        parent::__construct($poule, $nrOfGames);
    }

    public function getPoule(): Poule
    {
        return $this->countedObject;
    }

    public function addNrOfGames(int $nrOfGames): void
    {
        $this->increase($nrOfGames);
    }

    public function getNrOfGames(): int
    {
        return $this->count();
    }
}
