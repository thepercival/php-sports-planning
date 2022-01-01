<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations;

use Iterator;
use SportsPlanning\Place;
use SportsPlanning\Poule;

/**
 * @implements Iterator<int, Place>
 */
class PlaceIterator implements Iterator
{
    private int $current;

    public function __construct(protected Poule $poule, int $startNr)
    {
        $this->current = $startNr;
    }

    public function current(): Place
    {
        return $this->poule->getPlace($this->current);
    }

    public function next(): void
    {
        if ($this->current === $this->poule->getPlaces()->count()) {
            $this->current = 1;
        } else {
            $this->current++;
        }
    }

    public function key(): int
    {
        return $this->current;
    }

    public function valid(): bool
    {
        return true;
    }

    public function rewind(): void
    {
        $this->current = 1;
    }
}
