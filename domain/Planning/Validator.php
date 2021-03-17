<?php

namespace SportsPlanning\Planning;

use \Exception;
use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\Game;
use SportsPlanning\Poule;
use SportsPlanning\SelfReferee;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Place;
use SportsPlanning\Field;
use SportsPlanning\Referee;
use SportsPlanning\Validator\GameAssignments;
use SportsPlanning\Planning;
use SportsPlanning\Exception\UnequalAssignedFields as UnequalAssignedFieldsException;
use SportsPlanning\Exception\UnequalAssignedReferees as UnequalAssignedRefereesException;
use SportsPlanning\Exception\UnequalAssignedRefereePlaces as UnequalAssignedRefereePlacesException;

class Validator
{
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
        $validity = $this->validateGamesAndGamePlaces($planning);
        if (self::VALID !== $validity) {
            return $validity;
        }
        $validity = $this->validateGamesInARow($planning);
        if (self::VALID !== $validity) {
            return $validity;
        }
        $validity = $this->validateResourcesPerBatch($planning);
        if (self::VALID !== $validity) {
            return $validity;
        }
        $validity = $this->validateEquallyAssigned($planning);
        if (self::VALID !== $validity) {
            return $validity;
        }
        return self::VALID;
    }

    /**
     * @param int $validity
     * @param Planning|null $planning
     * @return list<string>
     */
    public function getValidityDescriptions(int $validity, Planning|null $planning = null): array
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
        if (($validity & self::INVALID_ASSIGNED_REFEREEPLACE) === self::INVALID_ASSIGNED_REFEREEPLACE) {
            $invalidations[] = "refereeplace should (not) be referee in same poule";
        }
        if ($planning !== null) {
            if ((($validity & self::UNEQUALLY_ASSIGNED_FIELDS) === self::UNEQUALLY_ASSIGNED_FIELDS
                || ($validity & self::UNEQUALLY_ASSIGNED_REFEREES) === self::UNEQUALLY_ASSIGNED_REFEREES
                || ($validity & self::UNEQUALLY_ASSIGNED_REFEREEPLACES) === self::UNEQUALLY_ASSIGNED_REFEREEPLACES)
            ) {
                $invalidations[] = $this->getUnqualAssignedDescription($planning);
            }
        }

        return $invalidations;
    }

    protected function validateAgainstGamesAndGamePlaces(Planning $planning): int
    {
        foreach ($planning->getPoules() as $poule) {
            if (count($poule->getGames()) === 0) {
                return self::NO_GAMES;
            }
            $validity = $this->allPlacesInPouleSameNrOfGames($planning, $poule);
            if ($validity !== self::VALID) {
                return $validity;
            }
        }
        return self::VALID;
    }

    protected function validateGamesAndGamePlaces(Planning $planning): int
    {
        foreach ($planning->getPoules() as $poule) {
            if (count($poule->getGames()) === 0) {
                return self::NO_GAMES;
            }
            $validity = $this->allPlacesInPouleSameNrOfGames($planning, $poule);
            if ($validity !== self::VALID) {
                return $validity;
            }
        }
        return self::VALID;
    }

    protected function allPlacesInPouleSameNrOfGames(Planning $planning, Poule $poule): int
    {
        $nrOfGames = [];
        foreach ($poule->getGames() as $game) {
            if ($game instanceof AgainstGame) {
                $homePlaces = $game->getSidePlaces(AgainstSide::HOME);
                $awayPlaces = $game->getSidePlaces(AgainstSide::AWAY);
                if (count($homePlaces) === 0 || count($awayPlaces) === 0) {
                    return self::EMPTY_PLACE;
                }
                if (count($homePlaces) !== count($awayPlaces)) {
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
            if ($planning->getInput()->selfRefereeEnabled()) {
                $refereePlace = $game->getRefereePlace();
                if ($refereePlace === null) {
                    return self::EMPTY_REFEREEPLACE;
                }
                if ($planning->getInput()->getSelfReferee() === SelfReferee::SAMEPOULE
                    && $refereePlace->getPoule() !== $game->getPoule()) {
                    return self::INVALID_ASSIGNED_REFEREEPLACE;
                }
                if ($planning->getInput()->getSelfReferee() === SelfReferee::OTHERPOULES
                    && $refereePlace->getPoule() === $game->getPoule()) {
                    return self::INVALID_ASSIGNED_REFEREEPLACE;
                }
            } else {
                if ($planning->getInput()->getNrOfReferees() > 0) {
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

    protected function validateGamesInARow(Planning $planning): int
    {
        if ($planning->getMaxNrOfGamesInARow() === 0) {
            return self::VALID;
        }
        /** @var Poule $poule */
        foreach ($planning->getPoules() as $poule) {
            foreach ($poule->getPlaces() as $place) {
                if ($this->checkGamesInARowForPlace($planning, $place) === false) {
                    return self::TOO_MANY_GAMES_IN_A_ROW;
                }
            }
        }
        return self::VALID;
    }

    protected function checkGamesInARowForPlace(Planning $planning, Place $place): bool
    {
        /**
         * @param Place $place
         * @return array<int,bool>
         */
        $getBatchParticipations = function (Place $place) use ($planning): array {
            $games = $planning->getGames(Game::ORDER_BY_BATCH);
            $batchMap = [];
            foreach ($games as $game) {
                if (array_key_exists($game->getBatchNr(), $batchMap) === false) {
                    $batchMap[$game->getBatchNr()] = false;
                }
                if ($batchMap[$game->getBatchNr()] === true) {
                    continue;
                }
                $batchMap[$game->getBatchNr()] = $game->isParticipating($place);
            }
            return $batchMap;
        };
        /**
         * @param array<int,bool> $batchParticipations
         * @return int
         */
        $getMaxInARow = function (array $batchParticipations): int {
            $maxNrOfGamesInRow = 0;
            $currentMaxNrOfGamesInRow = 0;
            /** @var bool $batchParticipation */
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

        return $getMaxInARow($getBatchParticipations($place)) <= $planning->getMaxNrOfGamesInARow();
    }

//    /**
//     * @param Game $game
//     * @param int|null $side
//     * @return array|Place[]
//     */
//    protected function getPlaces(Game $game, int $side = null): array
//    {
//        return $game->getPlaces($side)->map(
//            function (GamePlace $gamePlace): Place {
//                return $gamePlace->getPlace();
//            }
//        )->toArray();
//    }

    protected function validateResourcesPerBatch(Planning $planning): int
    {
        $games = $planning->getGames(Game::ORDER_BY_BATCH);
        /** @var array<string,list<Place>|list<Field>|list<Referee>> $batchMap */
        $batchMap = [];
        foreach ($games as $game) {
            if (array_key_exists($game->getBatchNr(), $batchMap) === false) {
                $batchMap[$game->getBatchNr()] = array("fields" => [], "referees" => [], "places" => []);
            }
            $batch = &$batchMap[$game->getBatchNr()];
            /** @var list<Place> $batchPlaces */
            $batchPlaces = &$batch['places'];
            $places = $game->getPoulePlaces();
            $refereePlace = $game->getRefereePlace();
            if ($refereePlace !== null) {
                $places[] = $refereePlace;
            }
            foreach ($places as $placeIt) {
                if (array_search($placeIt, $batchPlaces, true) !== false) {
                    return self::MULTIPLE_ASSIGNED_PLACES_IN_BATCH;
                }
                $batchPlaces[] = $placeIt;
            }
            /** @var list<Field> $batchFields */
            $batchFields = &$batch['fields'];

            $search = array_search($game->getField(), $batchFields, true);
            if ($search !== false) {
                return self::MULTIPLE_ASSIGNED_FIELDS_IN_BATCH;
            }
            $field = $game->getField();
            if ($field !== null) {
                $batchFields[] = $field;
            }

            /** @var list<Referee> $batchReferees */
            $batchReferees = &$batch['referees'];
            $referee = $game->getReferee();
            if ($referee !== null) {
                $search = array_search($referee, $batchReferees, true);
                if ($search !== false) {
                    return self::MULTIPLE_ASSIGNED_REFEREES_IN_BATCH;
                }
                $batchReferees[] = $referee;
            }
        }
        return self::VALID;
    }

    protected function validateEquallyAssigned(Planning $planning): int
    {
        try {
            $assignmentValidator = new GameAssignments($planning);
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
