<?php

namespace SportsPlanning\Game\Place;

use SportsPlanning\Game\AgainstEachOther as AgainstEachOtherGame;
use SportsPlanning\Game\Place as GamePlace;
use SportsPlanning\Place as PoulePlace;

class AgainstEachOther extends GamePlace
{
    private AgainstEachOtherGame $game;
    private bool $homeaway;

    public function __construct(AgainstEachOtherGame $game, PoulePlace $place, bool $homeaway )
    {
        parent::__construct($place);
        $this->setGame($game);
        $this->homeaway = $homeaway;
    }

    public function getGame(): AgainstEachOtherGame
    {
        return $this->game;
    }

    protected function setGame(AgainstEachOtherGame $game)
    {
        if (!$game->getPlaces()->contains($this)) {
            $game->getPlaces()->add($this) ;
        }
        $this->game = $game;
    }

    public function getHomeaway(): bool
    {
        return $this->homeaway;
    }
}
