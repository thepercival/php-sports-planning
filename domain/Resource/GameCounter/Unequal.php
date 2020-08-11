<?php

namespace SportsPlanning\Resource\GameCounter;

use SportsPlanning\Resource\GameCounter;

class Unequal
{
    /**
     * @var int
     */
    private $minNrOfGames;
    /**
     * @var array|GameCounter[]
     */
    private $minGameCounters;
    /**
     * @var int
     */
    private $maxNrOfGames;
    /**
     * @var array|GameCounter[]
     */
    private $maxGameCounters;
    /**
     * @var int
     */
    private $pouleNr = 0;

    /**
     * Unequal constructor.
     * @param int $minNrOfGames
     * @param array|GameCounter[] $minGameCounters
     * @param int $maxNrOfGames
     * @param array|GameCounter[] $maxGameCounters
     */
    public function __construct(
        int $minNrOfGames,
        array $minGameCounters,
        int $maxNrOfGames,
        array $maxGameCounters
    ) {
        $this->minNrOfGames = $minNrOfGames;
        $this->minGameCounters = $minGameCounters;
        $this->maxNrOfGames = $maxNrOfGames;
        $this->maxGameCounters = $maxGameCounters;
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
     * @return array|GameCounter[]
     */
    public function getMinGameCounters(): array
    {
        return $this->minGameCounters;
    }

    /**
     * @return array|GameCounter[]
     */
    public function getMaxGameCounters(): array
    {
        return $this->maxGameCounters;
    }

    public function getPouleNr(): int
    {
        return $this->pouleNr;
    }

    public function setPouleNr(int $pouleNr)
    {
        $this->pouleNr = $pouleNr;
    }
}