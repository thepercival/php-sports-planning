<?php

namespace SportsPlanning\GameGenerator;

use SportsPlanning\Game\AgainstEachOther as AgainstEachOtherGame;

class AgainstEachOtherHomeAway
{
    private PlaceCombination $home;
    private PlaceCombination $away;

    public function __construct(PlaceCombination $home, PlaceCombination $away)
    {
        $this->home = $home;
        $this->away = $away;
    }

    public function get(bool $homeAway): PlaceCombination
    {
        return $homeAway === AgainstEachOtherGame::HOME ? $this->home : $this->away;
    }

    public function getHome(): PlaceCombination
    {
        return $this->home;
    }

    public function getAway(): PlaceCombination
    {
        return $this->away;
    }

    public function equals(AgainstEachOtherHomeAway $game): bool
    {
        return ($game->getAway()->getNumber() === $this->getHome()->getNumber()
                || $game->getHome()->getNumber() === $this->getHome()->getNumber())
            && ($game->getAway()->getNumber() === $this->getAway()->getNumber()
                || $game->getHome()->getNumber() === $this->getAway()->getNumber());
    }

    public function hasOverlap(AgainstEachOtherHomeAway $game): bool
    {
        return $game->getAway()->hasOverlap($this->getHome())
            || $game->getAway()->hasOverlap($this->getAway())
            || $game->getHome()->hasOverlap($this->getHome())
            || $game->getHome()->hasOverlap($this->getAway())
            ;
    }
}
