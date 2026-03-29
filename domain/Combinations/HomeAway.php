<?php

declare(strict_types=1);

namespace SportsPlanning\Combinations;

use SportsHelpers\Against\AgainstSide;

final class HomeAway implements \Stringable
{
    private string|null $index = null;

    public function __construct(
        private PlaceNrCombination $home,
        private PlaceNrCombination $away
    ) {
    }

    public function getIndex(): string
    {
        if( $this->index === null ) {
            $this->index = (string)$this;
        }
        return $this->index;
    }

    public function get(AgainstSide $side): PlaceNrCombination
    {
        return $side === AgainstSide::Home ? $this->home : $this->away;
    }

    public function getHome(): PlaceNrCombination
    {
        return $this->home;
    }

    public function getAway(): PlaceNrCombination
    {
        return $this->away;
    }

    public function hasPlaceNr(int $placeNr): bool
    {
        return $this->home->has($placeNr) || $this->away->has($placeNr);
    }

    public function playAgainst(int $placeNr, int $againstPlaceNr): bool {
        return ($this->getHome()->has($placeNr) && $this->getAway()->has($againstPlaceNr))
            || ($this->getHome()->has($againstPlaceNr) && $this->getAway()->has($placeNr));
    }

    /**
     * @param AgainstSide|null $side
     * @return list<int>
     */
    public function getPlaceNrs(AgainstSide|null $side = null): array
    {
        if( $side === null ) {
            return array_merge($this->home->getPlaceNrs(), $this->away->getPlaceNrs());
        }
        return $this->get($side)->getPlaceNrs();
    }

    /**
     * @return list<PlaceNrCombination>
     */
    public function getAgainstPlaceNrCombinations(): array {

        $againstPlaceNrCombinations = [];
        foreach($this->getPlaceNrs(AgainstSide::Home) as $homePlaceNr) {
            foreach($this->getPlaceNrs(AgainstSide::Away) as $awayPlaceNr) {
                array_push($againstPlaceNrCombinations, new PlaceNrCombination([$homePlaceNr, $awayPlaceNr]));
            }
        }
        return $againstPlaceNrCombinations;
    }

    /**
     * @return list<PlaceNrCombination>
     */
    public function getWithPlaceNrCombinations(): array {

        $withPlaceNrCombinations = [];
        foreach([AgainstSide::Home, AgainstSide::Away] as $side) {
            $placeNrCombination = $this->get($side);
            if(count($placeNrCombination->getPlaceNrs()) > 1) {
                array_push($withPlaceNrCombinations, $placeNrCombination);
            }
        }
        return $withPlaceNrCombinations;
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

    #[\Override]
    public function __toString(): string
    {
        return ((string)$this->get(AgainstSide::Home)) . ' vs ' . ((string)$this->get(AgainstSide::Away));
    }
}
