<?php

namespace SportsPlanning\Sports\SportWithNrOfPlaces;

use SportsHelpers\Sports\TogetherSport;

final class TogetherSportWithNrOfPlaces extends SportWithNrOfPlacesAbstract implements SportWithNrOfPlacesInterface
{
    public function __construct(int $nrOfPlaces, public TogetherSport $sport ) {
        parent::__construct($nrOfPlaces );
    }

    #[\Override]
    public function calculateNrOfGames(int $nrOfCycles): int
    {
        // THIS IS NOT calculateNrOfGamesPerCycle * $nrOfCycles !!!
        $nrOfGamePlaces = $this->sport->getNrOfGamePlaces() ?? $this->nrOfPlaces;
        return (int)ceil($this->calcTotalNrOfGamePlaces($nrOfCycles) / $nrOfGamePlaces);
    }

    private function calcTotalNrOfGamePlaces(int $nrOfCycles): int
    {
        return $nrOfCycles * $this->nrOfPlaces;
    }

    #[\Override]
    public function calculateNrOfGamesPerCycle(): int
    {
        return (int)ceil($this->nrOfPlaces / ($this->sport->getNrOfGamePlaces() ?? $this->nrOfPlaces));


    }

    #[\Override]
    public function calculateNrOfGamesPerPlace(int $nrOfCycles): int
    {
        return $nrOfCycles;
    }


    #[\Override]
    public function calculateNrOfGamesPerPlacePerCycle(): int
    {
        return 1;
    }

}