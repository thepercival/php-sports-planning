<?php

declare(strict_types=1);

namespace SportsPlanning\Planning;

use SportsHelpers\SportRange;

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

    public function __toString()
    {
        return 'batchGamesRange: "' . $this->batchGamesRange->getMin() . ' < x <= ' . $this->batchGamesRange->getMax()
            . '", maxNrOfGamesInARow: ' . $this->maxNrOfGamesInARow;
    }
}
