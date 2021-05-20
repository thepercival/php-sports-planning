<?php
declare(strict_types=1);

namespace SportsPlanning\Combinations;

use Iterator;
use SportsHelpers\Sport\Variant\Against as AgainstSportVaritant;
use SportsHelpers\SportMath;
use SportsPlanning\Place;
use SportsPlanning\Poule;

/**
 * @implements Iterator<string|int, PlaceCombination>
 */
class PlaceCombinationIterator implements Iterator
{
    /**
     * @var list<PlaceIterator>
     */
    protected array $placeIterators;
    protected int $nrOfIncrements = 1;

    /**
     * @param Poule $poule
     * @param list<Place> $startPlaces
     * @param int $maxNrOfIncrements
     */
    public function __construct(Poule $poule, array $startPlaces, protected int $maxNrOfIncrements)
    {
        $this->placeIterators = array_map(function (Place $place) use ($poule): PlaceIterator {
            return new PlaceIterator($poule, $place->getNumber());
        }, $startPlaces);
    }

    public function current(): PlaceCombination
    {
        $places = array_map(function (PlaceIterator $placeIterator): Place {
            return $placeIterator->current();
        }, $this->placeIterators);
        return new PlaceCombination($places);
    }

    public function next(): void
    {
        $this->nrOfIncrements++;
        foreach ($this->placeIterators as $placeIterator) {
            //   for( $i = 0 ; $i < $this->delta ;$i++) {
            $placeIterator->next();
            //     }
        }
    }

    public function key()
    {
        return '' . $this->current();
    }

    public function valid()
    {
        return $this->nrOfIncrements <= $this->maxNrOfIncrements;
    }

    public function rewind()
    {
    }
}
