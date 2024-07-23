<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations;

use SportsHelpers\Against\Side;
use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\Place;

class HomeAway implements \Stringable
{
    private string|null $index = null;

    public function __construct(
        private PlaceCombination $home,
        private PlaceCombination $away
    ) {
    }

    public function getIndex(): string
    {
        if( $this->index === null ) {
            $this->index = (string)$this;
        }
        return $this->index;
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

    public function hasPlace(Place $place, Side $side = null): bool
    {
        $inHome = $this->home->has($place);
        if( ($side === Side::Home || $side === null) && $inHome ) {
            return true;
        }
        $inAway = $this->away->has($place);
        return ($side === Side::Away || $side === null) && $inAway;
    }

    public function playAgainst(Place $place, Place $againstPlace): bool {
        return ($this->getHome()->has($place) && $this->getAway()->has($againstPlace))
            || ($this->getHome()->has($againstPlace) && $this->getAway()->has($place));
    }

    /**
     * @param AgainstSide|null $side
     * @return list<Place>
     */
    public function getPlaces(AgainstSide|null $side = null): array
    {
        if( $side === null ) {
            return array_merge($this->home->getPlaces(), $this->away->getPlaces());
        }
        return $this->get($side)->getPlaces();
    }

    public function getOtherSidePlace(Place $place): Place
    {
        foreach([AgainstSide::Home, AgainstSide::Away] as $side) {
            if( $this->get($side)->has($place)) {
                foreach( $this->get($side)->getPlaces() as $placeIt) {
                    if( $placeIt !== $place) {
                        return $placeIt;
                    }
                }
            }
        }
        throw new \Exception('place should be found');
    }

    /**
     * @return list<PlaceCombination>
     */
    public function getAgainstPlaceCombinations(): array {

        $againstPlaceCombinations = [];
        foreach($this->getPlaces(AgainstSide::Home) as $homePlace) {
            foreach($this->getPlaces(AgainstSide::Away) as $awayPlace) {
                $againstPlaceCombinations[] = new PlaceCombination([$homePlace, $awayPlace]);
            }
        }
        return $againstPlaceCombinations;
    }

    /**
     * @return list<PlaceCombination>
     */
    public function getWithPlaceCombinations(): array {

        $withPlaceCombinations = [];
        foreach([AgainstSide::Home, AgainstSide::Away] as $side) {
            $placeCombination = $this->get($side);
            if(count($placeCombination->getPlaces()) > 1) {
                $withPlaceCombinations[] = $placeCombination;
            }
        }
        return $withPlaceCombinations;
    }

    public function equals(HomeAway $game): bool
    {
        return ($game->getAway()->getIndex() === $this->getHome()->getIndex()
                || $game->getHome()->getIndex() === $this->getHome()->getIndex())
            && ($game->getAway()->getIndex() === $this->getAway()->getIndex()
                || $game->getHome()->getIndex() === $this->getAway()->getIndex());
    }

    public function hasOverlap(HomeAway $game): bool
    {
        return $game->getAway()->hasOverlap($this->getHome())
            || $game->getAway()->hasOverlap($this->getAway())
            || $game->getHome()->hasOverlap($this->getHome())
            || $game->getHome()->hasOverlap($this->getAway());
    }

    public function swap(): self
    {
        return new HomeAway($this->getAway(), $this->getHome());
    }

    public function __toString(): string
    {
        return $this->get(AgainstSide::Home) . ' vs ' . $this->get(AgainstSide::Away);
    }
}
