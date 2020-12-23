<?php

namespace SportsPlanning\Game\Place;

use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Place as GamePlace;
use SportsPlanning\Place as PoulePlace;

class Against extends GamePlace
{
    private AgainstGame $game;
    private bool $homeaway;

    public function __construct(AgainstGame $game, PoulePlace $place, bool $homeaway )
    {
        parent::__construct($place);
        $this->setGame($game);
        $this->homeaway = $homeaway;
    }

    public function getGame(): AgainstGame
    {
        return $this->game;
    }

    protected function setGame(AgainstGame $game)
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
