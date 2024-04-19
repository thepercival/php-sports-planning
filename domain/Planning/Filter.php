<?php

declare(strict_types=1);

namespace SportsPlanning\Planning;

use SportsHelpers\SportRange;
use SportsPlanning\Planning;

class Filter implements \Stringable
{
    public function __construct(
        readonly public Type|null $type,
        readonly public State|null $state,
        readonly public SportRange|null $batchGamesRange,
        readonly public int|null $maxNrOfGamesInARow)
    {
    }

    public function equals(Planning $planning): bool
    {
        return ($this->type === null ||  $this->type === $planning->getType())
            && ($this->state === null || $this->state === $planning->getState())
            && ($this->isEqualBatchGames($planning->getNrOfBatchGames() ))
                && ($this->maxNrOfGamesInARow === null || $this->maxNrOfGamesInARow === $planning->getMaxNrOfGamesInARow());
    }

    public function isEqualBatchGames(SportRange $nrOfBatchGames): bool
    {
        return $this->batchGamesRange === null
                || $this->batchGamesRange->equals($nrOfBatchGames)
                || ($this->batchGamesRange->getMin() === 0 && $this->batchGamesRange->getMax() === 0 &&
                $nrOfBatchGames->getMin() === $nrOfBatchGames->getMax()
            );
    }

    public function __toString(): string
    {
        return 'state: ' . ( $this->state !== null ? '"'.$this->state->value.'"' : 'null' ) . ','
            . 'type: ' . ( $this->type !== null ? '"'.$this->type->value.'"' : 'null' ) . ','
            . 'batchGamesRange: ' . ( $this->batchGamesRange !== null ? '"'.$this->batchGamesRange.'"' : 'null' ) . ','
            . 'maxNrOfGamesInARow: ' . ( $this->maxNrOfGamesInARow !== null ? $this->maxNrOfGamesInARow : 'null' );
    }
}
