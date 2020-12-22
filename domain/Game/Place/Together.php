<?php

namespace SportsPlanning\Game\Place;

use SportsPlanning\Place as PoulePlace;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Game\Place as GamePlace;

class Together extends GamePlace
{
    private TogetherGame $game;
    private int $gameRoundNumber;

    public function __construct(TogetherGame $game, PoulePlace $place, int $gameRoundNumber )
    {
        parent::__construct($place);
        $this->setGame($game);
        $this->gameRoundNumber = $gameRoundNumber;
    }

    public function getGame(): TogetherGame
    {
        return $this->game;
    }

    protected function setGame(TogetherGame $game)
    {
        if (!$game->getPlaces()->contains($this)) {
            $game->getPlaces()->add($this) ;
        }
        $this->game = $game;
    }

    public function getGameRoundNumber(): int
    {
        return $this->gameRoundNumber;
    }
}
