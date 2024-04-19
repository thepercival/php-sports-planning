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
        readonly public SportRange|BatchGamesType|null $batchGamesRange,
        readonly public int|null $maxNrOfGamesInARow)
    {
    }

    public function equals(Planning $planning): bool
    {
        return ($this->type === null ||  $this->type === $planning->getType())
            && ($this->state === null || $this->state === $planning->getState())
            && ($this->batchGamesRange === null ||
                ($this->batchGamesRange instanceof BatchGamesType && $this->batchGamesRange === $planning->getBatchGamesType()) ||
                ($this->batchGamesRange instanceof SportRange && $this->batchGamesRange->equals($planning->getNrOfBatchGames())))
            && ($this->maxNrOfGamesInARow === null || $this->maxNrOfGamesInARow === $planning->getMaxNrOfGamesInARow());
    }

    public function __toString(): string
    {
        if( $this->batchGamesRange === null ){
            $batchGameRangeAsString = 'null';
        }
        else if($this->batchGamesRange instanceof BatchGamesType ){
            $batchGameRangeAsString = $this->batchGamesRange->value;
        } else {
            $batchGameRangeAsString = (string) $this->batchGamesRange;
        }
        return 'state: ' . ( $this->state !== null ? '"'.$this->state->value.'"' : 'null' ) . ','
            . 'type: ' . ( $this->type !== null ? '"'.$this->type->value.'"' : 'null' ) . ','
            . 'batchGamesRange: ' . $batchGameRangeAsString . ','
            . 'maxNrOfGamesInARow: ' . ( $this->maxNrOfGamesInARow !== null ? $this->maxNrOfGamesInARow : 'null' );
    }
}
