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

    public function get(int $side): PlaceCombination
    {
        return $side === AgainstSide::HOME ? $this->home : $this->away;
    }

    public function getHome(): PlaceCombination
    {
        return $this->home;
    }

    public function getAway(): PlaceCombination
    {
        return $this->away;
    }

    /**
     * @return list<Place>
     */
    public function getPlaces(): array {
        $places = array_merge($this->home->getPlaces(), $this->away->getPlaces());
        return array_values($places);
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
            || $game->getHome()->hasOverlap($this->getAway())
            ;
    }

    public function swap(): self {
        return new AgainstHomeAway($this->getAway(), $this->getHome());
    }

    public function __toString(): string {
        return $this->get(AgainstSide::HOME) . ' vs ' . $this->get(AgainstSide::AWAY);
    }
}
