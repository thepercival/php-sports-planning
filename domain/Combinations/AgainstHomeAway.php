<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations;

use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\Place;

class AgainstHomeAway implements \Stringable
{
    public function __construct(
        private PlaceCombination $home,
        private PlaceCombination $away
    ) {
    }

    public function get(AgainstSide $side): PlaceCombination
    {
        return $side === AgainstSide::Home ? $this->home : $this->away;
    }

    public function getHome(): PlaceCombination
    {
        return $this->home;
    }

    public function getAway(): PlaceCombination
    {
        return $this->away;
    }

    public function hasPlace(Place $place): bool
    {
        return $this->home->has($place) || $this->away->has($place);
    }

    public function playAgainst(Place $place, Place $againstPlace): bool {
        return ($this->getHome()->has($place) && $this->getAway()->has($againstPlace))
            || ($this->getHome()->has($againstPlace) && $this->getAway()->has($place));
    }

    /**
     * @return list<Place>
     */
    public function getPlaces(): array
    {
        return array_merge($this->home->getPlaces(), $this->away->getPlaces());
    }

    public function equals(AgainstHomeAway $game): bool
    {
        return ($game->getAway()->getNumber() === $this->getHome()->getNumber()
                || $game->getHome()->getNumber() === $this->getHome()->getNumber())
            && ($game->getAway()->getNumber() === $this->getAway()->getNumber()
                || $game->getHome()->getNumber() === $this->getAway()->getNumber());
    }

    public function hasOverlap(AgainstHomeAway $game): bool
    {
        return $game->getAway()->hasOverlap($this->getHome())
            || $game->getAway()->hasOverlap($this->getAway())
            || $game->getHome()->hasOverlap($this->getHome())
            || $game->getHome()->hasOverlap($this->getAway());
    }

    public function swap(): self
    {
        return new AgainstHomeAway($this->getAway(), $this->getHome());
    }

    public function __toString(): string
    {
        return $this->get(AgainstSide::Home) . ' vs ' . $this->get(AgainstSide::Away);
    }
}
