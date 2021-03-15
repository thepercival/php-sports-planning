<?php

namespace SportsPlanning\GameGenerator;

use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\Sport;

class AgainstHomeAway
{
    public function __construct(
        private Sport $sport,
        private PlaceCombination $home,
        private PlaceCombination $away
    ) {
    }

    public function getSport(): Sport
    {
        return $this->sport;
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
}
