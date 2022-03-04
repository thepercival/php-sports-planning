<?php

declare(strict_types=1);

namespace SportsPlanning\Resource;

use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Poule;
use SportsPlanning\Poule\GameCounter;

class UniquePlacesCounter
{
    protected GameCounter $gameCounter;
    /**
     * @var array<int, bool> $places
     */
    protected array $places = [];

    public function __construct(Poule $poule)
    {
        $this->gameCounter = new GameCounter($poule);
    }

    public function getPoule(): Poule
    {
        return $this->gameCounter->getPoule();
    }

    public function addGame(AgainstGame|TogetherGame $game): void
    {
        $this->gameCounter->increment();
        foreach ($game->getPlaces() as $gamePlace) {
            if (array_key_exists($gamePlace->getPlace()->getNumber(), $this->places)) {
                continue;
            }
            $this->places[$gamePlace->getPlace()->getNumber()] = true;
        }
    }

    public function getNrOfDistinctPlacesAssigned(): int
    {
        return count($this->places);
    }

    public function getNrOfGames(): int
    {
        return $this->gameCounter->count();
    }
}
