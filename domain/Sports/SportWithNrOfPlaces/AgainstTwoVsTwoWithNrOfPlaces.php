<?php

namespace SportsPlanning\Sports\SportWithNrOfPlaces;

use SportsHelpers\SportMath;
use SportsHelpers\Sports\AgainstTwoVsTwo;

class AgainstTwoVsTwoWithNrOfPlaces extends SportWithNrOfPlacesAbstract implements SportWithNrOfPlacesInterface
{
    public function __construct(int $nrOfPlaces, public AgainstTwoVsTwo $sport ) {
        parent::__construct($nrOfPlaces);
    }
    // in function calls public int $nrOfCycles

    public function calculateNrOfGames(int $nrOfCycles): int
    {
        return $this->calculateNrOfGamesPerCycle() * $nrOfCycles;
    }

    public function calculateNrOfGamesPerCycle(): int
    {
        // pure
        if( $this->nrOfPlaces % 4 === 0 || $this->nrOfPlaces % 4 === 1) {
            return (int) (($this->nrOfPlaces * ($this->nrOfPlaces - 1)) / 4);
        }

        // mixed & asymmetric
        if( $this->nrOfPlaces % 4 === 3) {
            return (new AgainstTwoVsTwoWithNrOfPlaces($this->nrOfPlaces - 1, $this->sport))
                ->calculateNrOfGamesPerCycle();
        }

        return (int)floor($this->nrOfPlaces * ($this->nrOfPlaces - 1) / 4 );
    }

    public function calculateNrOfGamesPerPlace(int $nrOfCycles): int
    {
        return $this->calculateNrOfGamesPerPlacePerCycle() * $nrOfCycles;
    }


    public function calculateNrOfGamesPerPlacePerCycle(): int
    {
        if( $this->nrOfPlaces % 4 === 0 || $this->nrOfPlaces % 4 === 1 || $this->nrOfPlaces % 4 === 2) {
            return $this->nrOfPlaces - 1;
        }
        return $this->nrOfPlaces - 3;
    }

}