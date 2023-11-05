<?php

declare(strict_types=1);

namespace SportsPlanning\Planning;

use SportsHelpers\SportRange;
use SportsPlanning\Planning;

class Filter implements \Stringable
{
    public function __construct(protected SportRange $batchGamesRange, protected int $maxNrOfGamesInARow)
    {
    }

    public function getBatchGamesRange(): SportRange
    {
        return $this->batchGamesRange;
    }

    public function getMinNrOfBatchGames(): int
    {
        return $this->batchGamesRange->getMin();
    }

    public function getMaxNrOfBatchGames(): int
    {
        return $this->batchGamesRange->getMax();
    }

    public function getMaxNrOfGamesInARow(): int
    {
        return $this->maxNrOfGamesInARow;
    }

    public function equals(Planning $planning): bool
    {
        return $this->getMinNrOfBatchGames() === $planning->getMinNrOfBatchGames()
            && $this->getMaxNrOfBatchGames() === $planning->getMaxNrOfBatchGames()
            && $this->getMaxNrOfGamesInARow() === $planning->getMaxNrOfGamesInARow();
    }

    public function __toString(): string
    {
        return 'batchGamesRange: "' . $this->batchGamesRange->getMin() . ' < x <= ' . $this->batchGamesRange->getMax()
            . '", maxNrOfGamesInARow: ' . $this->maxNrOfGamesInARow;
    }
}
