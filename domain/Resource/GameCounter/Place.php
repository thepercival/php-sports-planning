<?php

declare(strict_types=1);

namespace SportsPlanning\Resource\GameCounter;

use SportsPlanning\Place as PlaceBase;
use SportsPlanning\Resource\GameCounter;

readonly class Place extends GameCounter
{
    public function __construct(protected PlaceBase $place, int $nrOfGames = 0)
    {
        parent::__construct($place, $nrOfGames);
    }

    public function getIndex(): string
    {
        return (string)$this->place;
    }

    public function getPlace(): PlaceBase
    {
        return $this->place;
    }
}
