<?php

declare(strict_types=1);

namespace SportsPlanning\Game;

use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Game\AgainstGame as AgainstGame;
use SportsPlanning\Place as PoulePlace;

class AgainstGamePlace extends GamePlaceAbstract
{
    public function __construct(protected AgainstGame $game, PoulePlace $place, private AgainstSide $side)
    {
        parent::__construct($place);
        if (!$game->getPlaces()->contains($this)) {
            $game->getPlaces()->add($this);
        }
    }

    public function getGame(): AgainstGame
    {
        return $this->game;
    }

    public function getSide(): AgainstSide
    {
        return $this->side;
    }
}
