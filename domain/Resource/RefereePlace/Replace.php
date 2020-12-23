<?php


namespace SportsPlanning\Resource\RefereePlace;

use SportsPlanning\Batch\SelfReferee as SelfRefereeBatch;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Place;

class Replace
{
    protected SelfRefereeBatch $batch;
    /**
     * @var TogetherGame|AgainstGame
     */
    protected $game;
    protected Place $replaced;
    protected ?Place $replacement;

    /**
     * Replace constructor.
     * @param SelfRefereeBatch $batch
     * @param TogetherGame|AgainstGame $game
     * @param Place $replacement
     */
    public function __construct(SelfRefereeBatch $batch, $game, Place $replacement)
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

    /**
     * @return AgainstGame|TogetherGame
     */
    public function getGame()
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