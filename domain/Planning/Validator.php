<?php

namespace SportsPlanning\Planning;

use \Exception;
use SportsHelpers\GameMode;
use SportsHelpers\SportConfig;
use SportsPlanning\Game;
use SportsPlanning\Poule;
use SportsPlanning\Input;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Place;
use SportsPlanning\Validator\GameAssignments;
use SportsPlanning\Planning;
use SportsPlanning\Exception\UnequalAssignedFields as UnequalAssignedFieldsException;
use SportsPlanning\Exception\UnequalAssignedReferees as UnequalAssignedRefereesException;
use SportsPlanning\Exception\UnequalAssignedRefereePlaces as UnequalAssignedRefereePlacesException;

class Validator
{
    /**
     * @var Planning
     */
    protected $planning;

    public const NOT_VALIDATED = -1;
    public const VALID = 0;
    public const NO_GAMES = 1;
    public const EMPTY_PLACE = 2;
    public const EMPTY_FIELD = 4;
    public const EMPTY_REFEREE = 8;
    public const EMPTY_REFEREEPLACE = 16;
    public const UNEQUAL_GAME_HOME_AWAY = 32;
    public const NOT_EQUALLY_ASSIGNED_PLACES = 64;
    public const TOO_MANY_GAMES_IN_A_ROW = 128;
    public const MULTIPLE_ASSIGNED_FIELDS_IN_BATCH = 256;
    public const MULTIPLE_ASSIGNED_REFEREES_IN_BATCH = 512;
    public const MULTIPLE_ASSIGNED_PLACES_IN_BATCH = 1024;
    public const UNEQUALLY_ASSIGNED_FIELDS = 2048;
    public const UNEQUALLY_ASSIGNED_REFEREES = 4096;
    public const UNEQUALLY_ASSIGNED_REFEREEPLACES = 8192;
    public const INVALID_ASSIGNED_REFEREEPLACE = 16384;

    public const ALL_INVALID = 32767;

    public function __construct()
    {
    }

    public function validate(Planning $planning): int
    {
        $this->planning = $planning;

        $validity = $this->validateGamesAndGamePlaces();
        if (self::VALID !== $validity) {
            return $validity;
        }
        $validity = $this->validateGamesInARow();
        if (self::VALID !== $validity) {
            return $validity;
        }
        $validity = $this->validateResourcesPerBatch();
        if (self::VALID !== $validity) {
            return $validity;
        }
        $validity = $this->validateEquallyAssigned();
        if (self::VALID !== $validity) {
            return $validity;
        }
        return self::VALID;
    }

    /**
     * @param int $validity
     * @return array|string[]
     */
    public function getValidityDescriptions(int $validity): array
    {
        $invalidations = [];
        if (($validity & self::NO_GAMES) === self::NO_GAMES) {
            $invalidations[] = "the planning has not enough games";
        }
        if (($validity & self::UNEQUAL_GAME_HOME_AWAY) === self::UNEQUAL_GAME_HOME_AWAY) {
            $invalidations[] = "the planning has a game with an unequal number of home- & awayplaces";
        }
        if (($validity & self::EMPTY_PLACE) === self::EMPTY_PLACE) {
            $invalidations[] = "the planning has a game with an empty place";
        }
        if (($validity & self::EMPTY_FIELD) === self::EMPTY_FIELD) {
            $invalidations[] = "the planning has a game with no field";
        }
        if (($validity & self::EMPTY_REFEREE) === self::EMPTY_REFEREE) {
            $invalidations[] = "the planning has a game with no referee";
        }
        if (($validity & self::EMPTY_REFEREEPLACE) === self::EMPTY_REFEREEPLACE) {
            $invalidations[] = "the planning has a game with no refereeplace";
        }
        if (($validity & self::NOT_EQUALLY_ASSIGNED_PLACES) === self::NOT_EQUALLY_ASSIGNED_PLACES) {
            $invalidations[] = "not all places within poule have same number of games";
        }
        if (($validity & self::TOO_MANY_GAMES_IN_A_ROW) === self::TOO_MANY_GAMES_IN_A_ROW) {
            $invalidations[] = "more than allowed numberr of games in a row";
        }
        if (($validity & self::MULTIPLE_ASSIGNED_FIELDS_IN_BATCH) === self::MULTIPLE_ASSIGNED_FIELDS_IN_BATCH) {
            $invalidations[] = "multiple assigned fields in batch";
        }
        if (($validity & self::MULTIPLE_ASSIGNED_REFEREES_IN_BATCH) === self::MULTIPLE_ASSIGNED_REFEREES_IN_BATCH) {
            $invalidations[] = "multiple assigned referees in batch";
        }
        if (($validity & self::MULTIPLE_ASSIGNED_PLACES_IN_BATCH) === self::MULTIPLE_ASSIGNED_PLACES_IN_BATCH) {
            $invalidations[] = "multiple assigned places in batch";
        }
        if ((($validity & self::UNEQUALLY_ASSIGNED_FIELDS) === self::UNEQUALLY_ASSIGNED_FIELDS
                || ($validity & self::UNEQUALLY_ASSIGNED_REFEREES) === self::UNEQUALLY_ASSIGNED_REFEREES
                || ($validity & self::UNEQUALLY_ASSIGNED_REFEREEPLACES) === self::UNEQUALLY_ASSIGNED_REFEREEPLACES)
            && $this->planning !== null) {
            $invalidations[] = $this->getUnqualAssignedDescription($this->planning);
        }
        if (($validity & self::INVALID_ASSIGNED_REFEREEPLACE) === self::INVALID_ASSIGNED_REFEREEPLACE) {
            $invalidations[] = "refereeplace should (not) be referee in same poule";
        }

        return $invalidations;
    }

    protected function validateAgainstGamesAndGamePlaces(): int
    {
        foreach ($this->planning->getPoules() as $poule) {
            if (count($poule->getGames()) === 0) {
                return self::NO_GAMES;
            }
            $validity = $this->allPlacesInPouleSameNrOfGames($poule);
            if ($validity !== self::VALID) {
                return $validity;
            }
        }
        return self::VALID;
    }

    protected function validateGamesAndGamePlaces(): int
    {
        foreach ($this->planning->getPoules() as $poule) {
            if (count($poule->getGames()) === 0) {
                return self::NO_GAMES;
            }
            $validity = $this->allPlacesInPouleSameNrOfGames($poule);
            if ($validity !== self::VALID) {
                return $validity;
            }
        }
        return self::VALID;
    }





    protected function allPlacesInPouleSameNrOfGames(Poule $poule): int
    {
        $nrOfGames = [];
        foreach ($poule->getGames() as $game) {
            if( $this->planning->getInput()->getGameMode() === GameMode::AGAINST ) {
                /** @var array|Place[] $places */
                $homePlaces = $game->getPlaces(AgainstGame::HOME);
                $awayPlaces = $game->getPlaces(AgainstGame::AWAY);
                if (count($homePlaces) === 0 || count($awayPlaces) === 0) {
                    return self::EMPTY_PLACE;
                }
                if (count($game->getPlaces(AgainstGame::HOME))
                    !== count($game->getPlaces(AgainstGame::AWAY))) {
                    return self::UNEQUAL_GAME_HOME_AWAY;
                }
            }
            if ($game->getPlaces()->count() === 0) {
                return self::EMPTY_PLACE;
            }
            $places = $game->getPoulePlaces();
            /** @var Place $place */
            foreach ($places as $place) {
                if (array_key_exists($place->getLocation(), $nrOfGames) === false) {
                    $nrOfGames[$place->getLocation()] = 0;
                }
                $nrOfGames[$place->getLocation()]++;
            }
            if ($game->getField() === null) {
                return self::EMPTY_FIELD;
            }
            if ($this->planning->getInput()->selfRefereeEnabled()) {
                if ($game->getRefereePlace() === null) {
                    return self::EMPTY_REFEREEPLACE;
                } else {
                    if ($this->planning->getInput()->getSelfReferee() === Input::SELFREFEREE_SAMEPOULE
                        && $game->getRefereePlace()->getPoule() !== $game->getPoule()) {
                        return self::INVALID_ASSIGNED_REFEREEPLACE;
                    }
                    if ($this->planning->getInput()->getSelfReferee() === Input::SELFREFEREE_OTHERPOULES
                        && $game->getRefereePlace()->getPoule() === $game->getPoule()) {
                        return self::INVALID_ASSIGNED_REFEREEPLACE;
                    }
                }
            } else {
                if ($this->planning->getInput()->getNrOfReferees() > 0) {
                    if ($game->getReferee() === null) {
                        return self::EMPTY_REFEREE;
                    }
                }
            }
        }
        $value = reset($nrOfGames);
        foreach ($nrOfGames as $valueIt) {
            if ($value !== $valueIt) {
                return self::NOT_EQUALLY_ASSIGNED_PLACES;
            }
        }

        return self::VALID;
    }

    protected function validateGamesInARow(): int
    {
        if( $this->planning->getMaxNrOfGamesInARow() === 0 ) {
            return self::VALID;
        }
        /** @var Poule $poule */
        foreach ($this->planning->getPoules() as $poule) {
            foreach ($poule->getPlaces() as $place) {
                if ($this->checkGamesInARowForPlace($place) === false) {
                    return self::TOO_MANY_GAMES_IN_A_ROW;
                }
            }
        }
        return self::VALID;
    }

    protected function checkGamesInARowForPlace(Place $place): bool
    {
        /**
         * @param Place $place
         * @return array|bool[]
         */
        $getBatchParticipations = function (Place $place): array {
            $games = $this->planning->getGames(Game::ORDER_BY_BATCH);
            $batches = [];
            foreach ($games as $game) {
                if (array_key_exists($game->getBatchNr(), $batches) === false) {
                    $batches[$game->getBatchNr()] = false;
                }
                if ($batches[$game->getBatchNr()] === true) {
                    continue;
                }
                $batches[$game->getBatchNr()] = $game->isParticipating($place);
            }
            return $batches;
        };
        /**
         * @param array|bool[] $batchParticipations
         * @return int
         */
        $getMaxInARow = function (array $batchParticipations): int {
            $maxNrOfGamesInRow = 0;
            $currentMaxNrOfGamesInRow = 0;
            foreach ($batchParticipations as $batchParticipation) {
                if ($batchParticipation) {
                    $currentMaxNrOfGamesInRow++;
                    if ($currentMaxNrOfGamesInRow > $maxNrOfGamesInRow) {
                        $maxNrOfGamesInRow = $currentMaxNrOfGamesInRow;
                    }
                } else {
                    $currentMaxNrOfGamesInRow = 0;
                }
            }
            return $maxNrOfGamesInRow;
        };

        return $getMaxInARow($getBatchParticipations($place)) <= $this->planning->getMaxNrOfGamesInARow();
    }

//    /**
//     * @param Game $game
//     * @param bool $homeAway
//     * @return array|Place[]
//     */
//    protected function getPlaces(Game $game, bool $homeAway = null): array
//    {
//        return $game->getPlaces($homeAway)->map(
//            function (GamePlace $gamePlace): Place {
//                return $gamePlace->getPlace();
//            }
//        )->toArray();
//    }

    protected function validateResourcesPerBatch(): int
    {
        $games = $this->planning->getGames(Game::ORDER_BY_BATCH);
        $batchesResources = [];
        foreach ($games as $game) {
            if (array_key_exists($game->getBatchNr(), $batchesResources) === false) {
                $batchesResources[$game->getBatchNr()] = array("fields" => [], "referees" => [], "places" => []);
            }
            $batchResources = &$batchesResources[$game->getBatchNr()];
            /** @var array|Place[] $places */
            $places = $game->getPoulePlaces();
            if ($this->planning->getInput()->selfRefereeEnabled()) {
                $places = $game->getRefereePlace();
            }
            foreach ($places as $placeIt) {
                if (array_search($placeIt, $batchResources["places"], true) !== false) {
                    return self::MULTIPLE_ASSIGNED_PLACES_IN_BATCH;
                }
                $batchResources["places"][] = $placeIt;
            }

            /** @var bool|int|string $search */
            $search = array_search($game->getField(), $batchResources["fields"], true);
            if ( $search !== false ) {
                return self::MULTIPLE_ASSIGNED_FIELDS_IN_BATCH;
            }
            $batchResources["fields"][] = $game->getField();
            if ($this->planning->getInput()->getNrOfReferees() > 0) {
                /** @var bool|int|string $search */
                $search = array_search($game->getReferee(), $batchResources["referees"], true);
                if ($search !== false) {
                    return self::MULTIPLE_ASSIGNED_REFEREES_IN_BATCH;
                }
                $batchResources["referees"][] = $game->getReferee();
            }
        }
        return self::VALID;
    }

    protected function validateEquallyAssigned()
    {
        try {
            $assignmentValidator = new GameAssignments($this->planning);
            $assignmentValidator->validate();
        } catch (UnequalAssignedFieldsException $e) {
            return self::UNEQUALLY_ASSIGNED_FIELDS;
        } catch (UnequalAssignedRefereesException $e) {
            return self::UNEQUALLY_ASSIGNED_REFEREES;
        } catch (UnequalAssignedRefereePlacesException $e) {
            return self::UNEQUALLY_ASSIGNED_REFEREEPLACES;
        }
        return self::VALID;
    }

    protected function getUnqualAssignedDescription(Planning $planning): string
    {
        try {
            $assignmentValidator = new GameAssignments($planning);
            $assignmentValidator->validate();
        } catch (UnequalAssignedFieldsException | UnequalAssignedRefereesException | UnequalAssignedRefereePlacesException $e) {
            return $e->getMessage();
        }/* catch( Exception $e ) {
            return 'unknown exception: ' . $e->getMessage();
        }*/
        return 'no exception';
    }
}
