<?php
declare(strict_types=1);

namespace SportsPlanning\Combinations;

use SportsHelpers\Against\Side as AgainstSide;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Place\Against as AgainstGamePlace;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Poule;
use SportsPlanning\Sport;

/**
 * @template T
 */
abstract class Validator
{
    protected AgainstSportVariant $sportVariant;
    /**
     * @var array<int, T>
     */
    protected array $counters = [];

    public function __construct(protected Poule $poule, protected Sport $sport)
    {
        $sportVariant = $this->sport->createVariant();
        if (!($sportVariant instanceof AgainstSportVariant)) {
            throw new \Exception('only against-sports', E_ERROR);
        }
        $this->sportVariant = $sportVariant;
    }

    public function getPlaceCombination(AgainstGame $game, AgainstSide $side): PlaceCombination
    {
        $poulePlaces = $game->getSidePlaces($side)->map(function (AgainstGamePlace $gamePlace): Place {
            return $gamePlace->getPlace();
        });
        return new PlaceCombination(array_values($poulePlaces->toArray()));
    }

    public function addGames(Planning $planning): void
    {
        foreach ($planning->getAgainstGamesForPoule($this->poule) as $game) {
            $this->addGame($game);
        }
    }

    abstract public function addGame(AgainstGame $game): void;
}
