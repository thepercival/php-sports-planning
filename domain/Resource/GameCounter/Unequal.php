<?php

namespace SportsPlanning\Resource\GameCounter;

use SportsPlanning\Resource\GameCounter;

class Unequal
{
    private int $pouleNr = 0;

    /**
     * @param int $minNrOfGames
     * @param array<int|string,GameCounter> $minGameCounters
     * @param int $maxNrOfGames
     * @param array<int|string,GameCounter> $maxGameCounters
     */
    public function __construct(
        protected int $minNrOfGames,
        protected array $minGameCounters,
        protected int $maxNrOfGames,
        protected array $maxGameCounters
    ) {
    }

    public function getDifference(): int
    {
        return $this->maxNrOfGames - $this->minNrOfGames;
    }

    public function getMinNrOfGames(): int
    {
        return $this->minNrOfGames;
    }

    public function getMaxNrOfGames(): int
    {
        return $this->maxNrOfGames;
    }

    /**
     * @return array<int|string,GameCounter>
     */
    public function getMinGameCounters(): array
    {
        return $this->minGameCounters;
    }

    /**
     * @return array<int|string,GameCounter>
     */
    public function getMaxGameCounters(): array
    {
        return $this->maxGameCounters;
    }

    public function getPouleNr(): int
    {
        return $this->pouleNr;
    }

    public function setPouleNr(int $pouleNr): void
    {
        $this->pouleNr = $pouleNr;
    }
}