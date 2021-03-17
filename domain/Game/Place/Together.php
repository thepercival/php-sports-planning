<?php

namespace SportsPlanning\Game\Place;

use SportsPlanning\Place as PoulePlace;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Game\Place as GamePlace;

class Together extends GamePlace
{
    public function __construct(private TogetherGame $game, PoulePlace $place, private int $gameRoundNumber)
    {
        parent::__construct($place);
        if (!$game->getPlaces()->contains($this)) {
            $game->getPlaces()->add($this) ;
        }
    }

    public function getGame(): TogetherGame
    {
        return $this->game;
    }

    public function getGameRoundNumber(): int
    {
        return $this->gameRoundNumber;
    }
}
