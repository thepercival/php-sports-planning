<?php
declare(strict_types=1);

namespace SportsPlanning\GameGenerator\GameMode;

use SportsPlanning\Field;
use SportsPlanning\GameGenerator\GameMode as GameModeGameGenerator;
use SportsPlanning\GameGenerator\GameRoundPlace;
use SportsPlanning\GameGenerator\PlaceCombination;
use SportsPlanning\GameGenerator\GameMode\SingleHelper;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsPlanning\Place;
use SportsPlanning\Game\Together as TogetherGame;
use drupol\phpermutations\Generators\Combinations as CombinationsGenerator;
use SportsPlanning\Planning;
use SportsPlanning\Poule;
use SportsPlanning\Sport;

class Single implements GameModeGameGenerator
{
    private SingleHelper $singleHelper;

    public function __construct(protected Planning $planning)
    {
        $this->singleHelper = new SingleHelper($planning);
    }

    /**
     * @param Poule $poule
     * @param list<Sport> $sports
     */
    public function generate(Poule $poule, array $sports): void
    {
        $this->singleHelper->addPlaces($poule);
        foreach ($sports as $sport) {
            $this->singleHelper->setDefaultField($sport->getField(1));
            $sportVariant = $sport->createVariant();
            if (!($sportVariant instanceof SingleSportVariant)) {
                throw new \Exception('only single-sport-variant accepted', E_ERROR);
            }
            $this->generateForSportVariant($poule, $sportVariant);
        }
    }

    /**
     * @param Poule $poule
     * @param SingleSportVariant $singleSportVariant
     */
    protected function generateForSportVariant(Poule $poule, SingleSportVariant $singleSportVariant): void
    {
        $places = array_values($poule->getPlaces()->toArray());

        $gameRoundNumber = 1;
        $gameRoundPlaces = $this->createGameRoundPlaces(1, $places);
        while (count($gameRoundPlaces) > 0 || $gameRoundNumber < $singleSportVariant->getGameAmount()) {
            $nextGameRoundPlaces = null;
            if (count($gameRoundPlaces) < $singleSportVariant->getNrOfGamePlaces()) {
                if (++$gameRoundNumber <= $singleSportVariant->getGameAmount()) {
                    $nextGameRoundPlaces = $this->createGameRoundPlaces($gameRoundNumber, $places);
                }
            }
            $this->selectPlaces($poule, $singleSportVariant, $gameRoundPlaces, $nextGameRoundPlaces);
        }
    }

    /**
     * @param int $gameRoundNumber
     * @param list<Place> $places
     * @return list<GameRoundPlace>
     */
    protected function createGameRoundPlaces(int $gameRoundNumber, array $places): array
    {
        return array_values(array_map(function (Place $place) use ($gameRoundNumber): GameRoundPlace {
            return new GameRoundPlace($gameRoundNumber, $place);
        }, $places));
    }

    /**
     * @param CombinationsGenerator $combinations
     * @return list<PlaceCombination>
     */
    protected function toPlaceCombinations(CombinationsGenerator $combinations): array
    {
        /** @var array<int, list<Place>> $combinationsTmp */
        $combinationsTmp = $combinations->toArray();
        return array_values(array_map(
            function (array $places): PlaceCombination {
                return new PlaceCombination($places);
            },
            $combinationsTmp
        ));
    }

    /**
     * @param Poule $poule
     * @param SingleSportVariant $sportVariant
     * @param list<GameRoundPlace> $gameRoundPlaces
     * @param list<GameRoundPlace>|null $nextGameRoundPlaces
     */
    protected function selectPlaces(Poule $poule, SingleSportVariant $sportVariant, array &$gameRoundPlaces, array|null $nextGameRoundPlaces = null): void
    {
//        $current = array_filter( $gameRoundPlaces, function(GameRoundPlace $gameRoundPlace) use ( $gameRoundNumber ): bool {
//            return $gameRoundPlace->getGameRoundNumber() === $gameRoundNumber;
//        });
        if ($nextGameRoundPlaces !== null) {
            $nextGameRoundPlacesSameLocation = $this->removeSameLocation($nextGameRoundPlaces, $gameRoundPlaces);
            $game = $this->singleHelper->createGame($poule, $sportVariant, $gameRoundPlaces, $nextGameRoundPlaces);
            $gameRoundPlaces = array_merge($gameRoundPlaces, $nextGameRoundPlaces, $nextGameRoundPlacesSameLocation);
        } else {
            $game = $this->singleHelper->createGame($poule, $sportVariant, [], $gameRoundPlaces);
        }
        $gameRoundPlaces = array_values($gameRoundPlaces);

        // remove places from $gameRoundPlaces
        foreach ($game->getPlaces() as $gamePlace) {
            $gameRoundPlacesToDelete = array_filter($gameRoundPlaces, function (GameRoundPlace $gameRoundPlace) use ($gamePlace): bool {
                return $gameRoundPlace->getPlace() === $gamePlace->getPlace();
            });
            $gameRoundPlaceToDelete = reset($gameRoundPlacesToDelete);
            if ($gameRoundPlaceToDelete === false) {
                continue;
            }
            $idx = array_search($gameRoundPlaceToDelete, $gameRoundPlaces, true);
            if ($idx !== false) {
                array_splice($gameRoundPlaces, $idx, 1);
            }
        }
    }

    /**
     * @param list<GameRoundPlace> $nextGameRoundPlaces
     * @param list<GameRoundPlace> $gameRoundPlaces
     * @return list<GameRoundPlace>
     */
    protected function removeSameLocation(array &$nextGameRoundPlaces, array $gameRoundPlaces): array
    {
        $nrOfPlaces = count($nextGameRoundPlaces);
        $removed = [];
        for ($idx = 0 ; $idx < $nrOfPlaces ; $idx++) {
            $nextGameRoundPlace = array_shift($nextGameRoundPlaces);
            if ($nextGameRoundPlace === null) {
                break;
            }
            if ($this->hasSameLocation($nextGameRoundPlace, $gameRoundPlaces)) {
                $removed[] = $nextGameRoundPlace;
            } else {
                array_push($nextGameRoundPlaces, $nextGameRoundPlace);
            }
        }
        return $removed;
    }

    /**
     * @param GameRoundPlace $nextGameRoundPlace
     * @param list<GameRoundPlace> $gameRoundPlaces
     * @return bool
     */
    protected function hasSameLocation(GameRoundPlace $nextGameRoundPlace, array $gameRoundPlaces): bool
    {
        foreach ($gameRoundPlaces as $gameRoundPlace) {
            if ($gameRoundPlace->getPlace()->getLocation() === $nextGameRoundPlace->getPlace()->getLocation()) {
                return true;
            }
        }
        return false;
    }
}
