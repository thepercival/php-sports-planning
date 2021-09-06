<?php
declare(strict_types=1);

namespace SportsPlanning\Planning;

use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\Combinations\GamePlaceStrategy;
use SportsPlanning\Game;
use SportsPlanning\Combinations\Validator\Against as AgainstValidator;
use SportsPlanning\Combinations\Validator\With as WithValidator;
use SportsPlanning\Poule;
use SportsHelpers\SelfReferee;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Place;
use SportsPlanning\Sport;
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
    public const EMPTY_REFEREE = 8;
    public const EMPTY_REFEREEPLACE = 16;
    public const UNEQUAL_GAME_HOME_AWAY = 32;
    public const UNEQUAL_GAME_WITH_AGAINST = 64;
    public const NOT_EQUALLY_ASSIGNED_PLACES = 128;
    public const TOO_MANY_GAMES_IN_A_ROW = 256;
    public const MULTIPLE_ASSIGNED_FIELDS_IN_BATCH = 512;
    public const MULTIPLE_ASSIGNED_REFEREES_IN_BATCH = 1024;
    public const MULTIPLE_ASSIGNED_PLACES_IN_BATCH = 2048;
    public const UNEQUALLY_ASSIGNED_FIELDS = 4096;
    public const UNEQUALLY_ASSIGNED_REFEREES = 8192;
    public const UNEQUALLY_ASSIGNED_REFEREEPLACES = 16384;
    public const INVALID_ASSIGNED_REFEREEPLACE = 32768;
    public const UNEQUAL_PLACE_NROFHOMESIDES = 65536;

    public const ALL_INVALID = 131071;

    public function __construct()
    {
    }

    public function validate(Planning $planning, bool $onlyUnassigned = false): int
    {
        $validity = $this->validateGamesAndGamePlaces($planning);
        if (self::VALID !== $validity) {
            return $validity;
        }
        $validity = $this->validateGamesInARow($planning);
        if (self::VALID !== $validity) {
            return $validity;
        }
        if ($onlyUnassigned) {
            return $validity;
        }
        $validity = $this->validateResourcesCorrectlyAssigned($planning);
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
            $invalidations[] = "the planning an unequal number of home- or awayplaces";
        }
        if (($validity & self::UNEQUAL_GAME_WITH_AGAINST) === self::UNEQUAL_GAME_WITH_AGAINST) {
            $invalidations[] = "the planning an unequal number of with- or againstplaces";
        }
        if (($validity & self::UNEQUAL_PLACE_NROFHOMESIDES) === self::UNEQUAL_PLACE_NROFHOMESIDES) {
            $invalidations[] = "the planning has a places with too much difference in nrOfHomeSides";
        }
        if (($validity & self::EMPTY_PLACE) === self::EMPTY_PLACE) {
            $invalidations[] = "the planning has a game with an empty place";
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
            $invalidations[] = "more than allowed number of games in a row";
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
        foreach ($planning->getInput()->getPoules() as $poule) {
            if (count($planning->getGamesForPoule($poule)) === 0) {
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
        foreach ($planning->getInput()->getPoules() as $poule) {
            if (count($planning->getGamesForPoule($poule)) === 0) {
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
        foreach ($planning->getInput()->getSports() as $sport) {
            $invalid = $this->allPlacesInPouleSameNrOfSportGames($planning, $poule, $sport);
            if ($invalid !== self::VALID) {
                return $invalid;
            }
        }
        return self::VALID;
    }

    protected function allPlacesInPouleSameNrOfSportGames(Planning $planning, Poule $poule, Sport $sport): int
    {
        $nrOfGamesPerPlace = [];

        /** @var non-empty-array<int, int> $nrOfHomeSideGames */
        $nrOfHomeSideGames = [];
        $sportVariant = $sport->createVariant();
        if ($sportVariant instanceof AgainstSportVariant) {
            foreach ($poule->getPlaces() as $place) {
                $nrOfHomeSideGames[$place->getUniqueIndex()] = 0;
            }
            if ($sportVariant->withAgainstMustBeEquallyAssigned($poule->getPlaces()->count())) {
                if ($sportVariant->getNrOfGamePlaces() > 2) {
                    $withValidator = new WithValidator($poule, $sport);
                    $withValidator->addGames($planning);
                    if (!$withValidator->balanced()) {
                        return self::UNEQUAL_GAME_WITH_AGAINST;
                    }
                }
                $againstValidator = new AgainstValidator($poule, $sport);
                $againstValidator->addGames($planning);
                if (!$againstValidator->balanced()) {
                    return self::UNEQUAL_GAME_WITH_AGAINST;
                }
            }
        }

        $sportGames = array_filter($planning->getGamesForPoule($poule), function (Game $game) use ($sport): bool {
            return $game->getSport() === $sport;
        });
        foreach ($sportGames as $game) {
            $sportVariant = $game->createVariant();
            if ($sportVariant instanceof AgainstSportVariant) {
                if (!$game instanceof AgainstGame) {
                    return self::UNEQUAL_GAME_HOME_AWAY;
                }
                $homePlaces = $game->getSidePlaces(AgainstSide::HOME);
                $awayPlaces = $game->getSidePlaces(AgainstSide::AWAY);
                $nrOfHomePlaces = count($homePlaces);
                $nrOfAwayPlaces = count($awayPlaces);
                if ($nrOfHomePlaces === 0 || $nrOfAwayPlaces === 0) {
                    return self::EMPTY_PLACE;
                }
                if ($sportVariant->getNrOfHomePlaces() === $sportVariant->getNrOfAwayPlaces()) {
                    if ($sportVariant->getNrOfHomePlaces() !== $nrOfHomePlaces
                    || $sportVariant->getNrOfAwayPlaces() !== $nrOfAwayPlaces) {
                        return self::UNEQUAL_GAME_HOME_AWAY;
                    }
                } else {
                    if (
                    ($sportVariant->getNrOfHomePlaces() !== $nrOfHomePlaces && $sportVariant->getNrOfAwayPlaces() !== $nrOfHomePlaces)
                        ||
                    ($sportVariant->getNrOfHomePlaces() !== $nrOfAwayPlaces && $sportVariant->getNrOfAwayPlaces() !== $nrOfAwayPlaces)) {
                        return self::UNEQUAL_GAME_HOME_AWAY;
                    }
                }

                foreach ($homePlaces as $homePlace) {
                    $nrOfHomeSideGames[$homePlace->getPlace()->getUniqueIndex()]++;
                }
            } elseif ($sportVariant instanceof AllInOneGameSportVariant) {
                if ($poule->getPlaces()->count() !== $game->getPlaces()->count()) {
                    return self::UNEQUAL_GAME_HOME_AWAY;
                }
            }
            if ($game->getPlaces()->count() === 0) {
                return self::EMPTY_PLACE;
            }
            $places = $game->getPoulePlaces();
            foreach ($places as $place) {
                if (array_key_exists($place->getLocation(), $nrOfGamesPerPlace) === false) {
                    $nrOfGamesPerPlace[$place->getLocation()] = 0;
                }
                $nrOfGamesPerPlace[$place->getLocation()]++;
            }
        }
        if ($planning->getInput()->getGamePlaceStrategy() === GamePlaceStrategy::EquallyAssigned
            && $sportVariant->mustBeEquallyAssigned($poule->getPlaces()->count())) {
            $nrOfGamesFirstPlace = reset($nrOfGamesPerPlace);
            foreach ($nrOfGamesPerPlace as $nrOfGamesSomePlace) {
                if ($nrOfGamesFirstPlace !== $nrOfGamesSomePlace) {
                    return self::NOT_EQUALLY_ASSIGNED_PLACES;
                }
            }
        }


        if (!($sportVariant instanceof AgainstSportVariant)) {
            return self::VALID;
        }
        if ($planning->getInput()->getGamePlaceStrategy() === GamePlaceStrategy::RandomlyAssigned) {
            return self::VALID;
        }

        if ($sportVariant->homeAwayMustBeQuallyAssigned()) {
            $minValue = min($nrOfHomeSideGames);
            foreach ($nrOfHomeSideGames as $amount) {
                if ($amount - $minValue > 1) {
                    return self::UNEQUAL_PLACE_NROFHOMESIDES;
                }
            }
        }

        return self::VALID;
    }

    protected function validateResourcesCorrectlyAssigned(Planning $planning): int
    {
        foreach ($planning->getInput()->getPoules() as $poule) {
            $validity = $this->validateResourcesCorrectlyAssignedHelper($planning, $poule);
            if ($validity !== self::VALID) {
                return $validity;
            }
        }
        return self::VALID;
    }

    protected function validateResourcesCorrectlyAssignedHelper(Planning $planning, Poule $poule): int
    {
        foreach ($planning->getGamesForPoule($poule) as $game) {
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
                if ($planning->getInput()->getReferees()->count() > 0) {
                    if ($game->getReferee() === null) {
                        return self::EMPTY_REFEREE;
                    }
                }
            }
        }
        return self::VALID;
    }

    protected function validateGamesInARow(Planning $planning): int
    {
        if ($planning->getMaxNrOfGamesInARow() === 0) {
            return self::VALID;
        }
        foreach ($planning->getInput()->getPoules() as $poule) {
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
        $batchMap = [];
        foreach ($games as $game) {
            if (array_key_exists($game->getBatchNr(), $batchMap) === false) {
                $batchMap[$game->getBatchNr()] = array("fields" => [], "referees" => [], "places" => []);
            }
            $places = $game->getPoulePlaces();
            $refereePlace = $game->getRefereePlace();
            if ($refereePlace !== null) {
                $places[] = $refereePlace;
            }
            foreach ($places as $placeIt) {
                /** @var bool|int|string $search */
                $search = array_search($placeIt, $batchMap[$game->getBatchNr()]["places"], true);
                if ($search !== false) {
                    return self::MULTIPLE_ASSIGNED_FIELDS_IN_BATCH;
                }
                array_push($batchMap[$game->getBatchNr()]["places"], $placeIt);
            }

            $search = array_search($game->getField(), $batchMap[$game->getBatchNr()]["fields"], true);
            /** @var bool|int|string $search */
            if ($search !== false) {
                return self::MULTIPLE_ASSIGNED_FIELDS_IN_BATCH;
            }
            array_push($batchMap[$game->getBatchNr()]["fields"], $game->getField());

            $referee = $game->getReferee();
            if ($referee !== null) {
                /** @var bool|int|string $search */
                $search = array_search($referee, $batchMap[$game->getBatchNr()]["referees"], true);
                if ($search !== false) {
                    return self::MULTIPLE_ASSIGNED_REFEREES_IN_BATCH;
                }
                array_push($batchMap[$game->getBatchNr()]["referees"], $referee);
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
