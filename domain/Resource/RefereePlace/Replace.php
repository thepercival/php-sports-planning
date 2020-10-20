<?php


namespace SportsPlanning\Resource\RefereePlace;

use SportsPlanning\Batch\SelfReferee as SelfRefereeBatch;
use SportsPlanning\Game;
use SportsPlanning\Place;

class Replace
{
    protected SelfRefereeBatch $batch;
    protected Game $game;
    protected Place $replaced;
    protected ?Place $replacement;

    public function __construct(SelfRefereeBatch $batch, Game $game, Place $replacement)
    {
        $this->batch = $batch;
        $this->game = $game;
        $this->replaced = $game->getRefereePlace();
        $this->replacement = $replacement;
    }

    public function getBatch(): SelfRefereeBatch
    {
        return $this->batch;
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    public function getReplaced(): Place
    {
        return $this->replaced;
    }

    public function getReplacement(): Place
    {
        return $this->replacement;
    }
}