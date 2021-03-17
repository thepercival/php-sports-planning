<?php
declare(strict_types=1);

namespace SportsPlanning\Game\Place;

use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Place as GamePlace;
use SportsPlanning\Place as PoulePlace;

class Against extends GamePlace
{
    private int $side;

    public function __construct(protected AgainstGame $game, PoulePlace $place, int $side)
    {
        parent::__construct($place);
        if (!$game->getPlaces()->contains($this)) {
            $game->getPlaces()->add($this) ;
        }
        $this->side = $side;
    }

    public function getGame(): AgainstGame
    {
        return $this->game;
    }

    public function getSide(): int
    {
        return $this->side;
    }
}
