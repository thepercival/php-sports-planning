<?php

namespace SportsPlanning\Game;

use SportsPlanning\Game\GamePlaceAbstract as GamePlace;
use SportsPlanning\Game\TogetherGame as TogetherGame;
use SportsPlanning\Place as PoulePlace;

class TogetherGamePlace extends GamePlaceAbstract
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
