<?php

namespace SportsPlanning\Sports\SportWithNrOfPlaces;

use SportsHelpers\SportMath;
use SportsHelpers\Sports\AgainstOneVsTwo;

/**
 *  | sport  | nrOfPlaces | totalNrOfGames | nrOfGamesPerPlace |
 *  | ------ | ---------- | ---------------| ----------------- |
 *  | 1 vs 1 | 7          | 21             | 6                 |
 *  | 1 vs 1 | 6          | 15             | 5                 |
 *  | 1 vs 1 | 5          | 10             | 4                 |
 *  | 1 vs 1 | 4          | 6              | 3                 |
 *  | 1 vs 1 | 3          | 4              | 2                 |
 *  | 1 vs 1 | 2          | 1              | 1                 |
 *  | 1 vs 2 | 3          | 3 =>3, 4=>9(12-3), 5=>21(30-9)
 */
final class AgainstOneVsTwoWithNrOfPlaces extends SportWithNrOfPlacesAbstract implements SportWithNrOfPlacesInterface
{
    public function __construct(int $nrOfPlaces, public AgainstOneVsTwo $sport ) {
        parent::__construct($nrOfPlaces);
    }
    // in function calls public int $nrOfCycles

    #[\Override]
    public function calculateNrOfGames(int $nrOfCycles): int
    {
        return $this->calculateNrOfGamesPerCycle() * $nrOfCycles;
    }

    #[\Override]
    public function calculateNrOfGamesPerCycle(): int
    {
        $nrOfCombinations = (new SportMath())->above($this->nrOfPlaces, $this->sport->getNrOfGamePlaces());
        return (int)($nrOfCombinations * $this->sport->getNrOfHomeAwayCombinations());
    }

    #[\Override]
    public function calculateNrOfGamesPerPlace(int $nrOfCycles): int
    {
        return $this->calculateNrOfGamesPerPlacePerCycle() * $nrOfCycles;
    }


    #[\Override]
    public function calculateNrOfGamesPerPlacePerCycle(): int
    {
        throw new \Exception('implement calculateNrOfGamesPerPlacePerCycle');
    }
}