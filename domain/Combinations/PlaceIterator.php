<?php
declare(strict_types=1);

namespace SportsPlanning\Combinations;

use SportsPlanning\Place;
use SportsPlanning\Poule;

/**
 * @implements \Iterator<int, Place>
 */
class PlaceIterator implements \Iterator
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

    public function key()
    {
        return $this->current;
    }

    public function valid()
    {
        return true;
    }

    public function rewind()
    {
        $this->current = 1;
    }
}
