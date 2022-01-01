<?php

declare(strict_types=1);

namespace SportsPlanning;

class PouleCounter
{
    protected int $nrOfGames = 0;

    public function __construct(protected Poule $poule, protected int $nrOfPlacesAssigned = 0)
    {
    }

    public function getPoule(): Poule
    {
        return $this->poule;
    }

    public function reset(): void
    {
        $this->nrOfGames = 0;
        $this->nrOfPlacesAssigned = 0;
    }

    public function add(int $nrOfPlacesToAssign): void
    {
        $this->addNrOfGames(1);
        $this->addNrOfAssignedPlaces($nrOfPlacesToAssign);
    }

    public function addNrOfGames(int $nrOfGames): void
    {
        $this->nrOfGames += $nrOfGames;
    }

    public function addNrOfAssignedPlaces(int $nrOfAssignedPlaces): void
    {
        $this->nrOfPlacesAssigned += $nrOfAssignedPlaces;
    }

    public function remove(int $nrOfPlacesToUnassign): void
    {
        $this->nrOfGames--;
        $this->nrOfPlacesAssigned -= $nrOfPlacesToUnassign;
    }

    public function getNrOfPlacesAssigned(bool|null $addRefereePlace = null): int
    {
        if ($addRefereePlace === true) {
            return $this->nrOfPlacesAssigned + $this->nrOfGames;
        }
        return $this->nrOfPlacesAssigned;
    }

    public function getNrOfGames(): int
    {
        return $this->nrOfGames;
    }
}
