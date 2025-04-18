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
        $nrOfCombinations = (new SportMath())->above($this->nrOfPlaces, $this->sport->getNrOfGamePlaces());
        return (int)($nrOfCombinations * $this->sport->getNrOfHomeAwayCombinations());
    }

    public function calculateNrOfGamesPerPlace(int $nrOfCycles): int
    {
        return $this->calculateNrOfGamesPerPlacePerCycle() * $nrOfCycles;
    }


    public function calculateNrOfGamesPerPlacePerCycle(): int
    {
        throw new \Exception('implement calculateNrOfGamesPerPlacePerCycle');
    }

}