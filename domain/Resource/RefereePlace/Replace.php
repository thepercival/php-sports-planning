<?php


namespace SportsPlanning\Resource\RefereePlace;

use SportsPlanning\Batch\SelfReferee as SelfRefereeBatch;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Place;

class Replace
{
    protected Place|null $replaced;

    public function __construct(
        protected SelfRefereeBatch $batch,
        protected TogetherGame|AgainstGame $game,
        protected Place $replacement
    )
    {
        $this->replaced = $game->getRefereePlace();
    }

    public function getBatch(): SelfRefereeBatch
    {
        return $this->batch;
    }

    public function getGame(): AgainstGame|TogetherGame
    {
        return $this->game;
    }

    public function getReplaced(): Place
    {
        return $this->replaced;
    }

    public function getReplacement(): ?Place
    {
        return $this->replacement;
    }
}
