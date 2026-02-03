<?php

declare(strict_types=1);

namespace SportsPlanning\Game\Place;

use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Place as GamePlace;
use SportsPlanning\Place as PoulePlace;

final class Against extends GamePlace
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
