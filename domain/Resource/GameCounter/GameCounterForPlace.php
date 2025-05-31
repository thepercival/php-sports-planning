<?php

declare(strict_types=1);

namespace SportsPlanning\Resource\GameCounter;

use SportsPlanning\Place;
use SportsPlanning\Resource\GameCounter;

readonly class GameCounterForPlace extends GameCounter
{
    public function __construct(public Place $place, int $nrOfGames = 0)
    {
        parent::__construct($place, $nrOfGames);
    }

    public function getIndex(): string
    {
        return $this->place->getUniqueIndex();
    }

    public function increment(): self
    {
        return new self($this->place, $this->count + 1 );
    }
}
