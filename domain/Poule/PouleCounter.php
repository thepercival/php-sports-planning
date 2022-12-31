<?php

declare(strict_types=1);

namespace SportsPlanning\Poule;

use SportsPlanning\Poule;
use SportsHelpers\Counter;

class PouleCounter
{
    /**
     * @var Counter<Poule>
     */
    protected Counter $gameCounter;

    public function __construct(protected Poule $poule, protected int $nrOfPlacesAssigned = 0)
    {
        $this->gameCounter = new Counter($poule);
    }

    public function getPoule(): Poule
    {
        return $this->poule;
    }

    public function reset(): void
    {
        $this->gameCounter = new Counter($this->poule);
        $this->nrOfPlacesAssigned = 0;
    }

    public function add(int $nrOfPlacesToAssign): void
    {
        $this->addNrOfGames(1);
        $this->addNrOfAssignedPlaces($nrOfPlacesToAssign);
    }

    public function addNrOfGames(int $nrOfGames): void
    {
        $this->gameCounter->increase($nrOfGames);
    }

    public function addNrOfAssignedPlaces(int $nrOfAssignedPlaces): void
    {
        $this->nrOfPlacesAssigned += $nrOfAssignedPlaces;
    }

    public function remove(int $nrOfPlacesToUnassign): void
    {
        $this->gameCounter = $this->gameCounter->decrement2();
        $this->nrOfPlacesAssigned -= $nrOfPlacesToUnassign;
    }

    public function getNrOfPlacesAssigned(bool|null $addRefereePlace = null): int
    {
        if ($addRefereePlace === true) {
            return $this->nrOfPlacesAssigned + $this->gameCounter->count();
        }
        return $this->nrOfPlacesAssigned;
    }

    public function getNrOfGames(): int
    {
        return $this->gameCounter->count();
    }
}
