<?php
declare(strict_types=1);

namespace SportsPlanning\Game\Place;

use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Place as GamePlace;
use SportsPlanning\Place as PoulePlace;

class Against extends GamePlace
{
    private AgainstGame $game;
    private int $side;

    public function __construct(AgainstGame $game, PoulePlace $place, int $side)
    {
        parent::__construct($place);
        $this->setGame($game);
        $this->side = $side;
    }

    public function getGame(): AgainstGame
    {
        return $this->game;
    }

    protected function setGame(AgainstGame $game): void
    {
        if (!$game->getPlaces()->contains($this)) {
            $game->getPlaces()->add($this) ;
        }
        $this->game = $game;
    }

    public function getSide(): int
    {
        return $this->side;
    }
}
