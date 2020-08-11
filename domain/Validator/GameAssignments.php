<?php


namespace SportsPlanning\Validator;

use SportsPlanning\Exception\UnequalAssignedFields as UnequalAssignedFieldsException;
use SportsPlanning\Exception\UnequalAssignedReferees as UnequalAssignedRefereesException;
use SportsPlanning\Exception\UnequalAssignedRefereePlaces as UnequalAssignedRefereePlacesException;
use \Exception;
use SportsPlanning\Field;
use SportsPlanning\Game;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Referee;
use SportsPlanning\Resource\GameCounter;
use SportsPlanning\Input as PlanningInput;
use SportsPlanning\Resource\GameCounter\Place as PlaceGameCounter;
use SportsPlanning\Resource\GameCounter\Unequal as UnequalGameCounter;

class GameAssignments
{
    /**
     * @var Planning
     */
    protected $planning;
    /**
     * @var array|GameCounter[]
     */
    protected $fields;
    /**
     * @var array|GameCounter[]
     */
    protected $referees;
    /**
     * @var array|GameCounter[]
     */
    protected $refereePlaces;

    const FIELDS = 1;
    const REFEREES = 2;
    const REFEREEPLACES = 4;

    public function __construct(Planning $planning)
    {
        $this->planning = $planning;
        $this->fields = [];
        $this->referees = [];
        $this->refereePlaces = [];
        $this->init();
    }

    protected function init()
    {
        /** @var Field $field */
        foreach ($this->planning->getFields() as $field) {
            $this->fields[(string)$field->getNumber()] = new GameCounter($field);
        }

        if ($this->planning->getInput()->selfRefereeEnabled()) {
            /** @var Place $place */
            foreach ($this->planning->getPlaces() as $place) {
                $gameCounter = new PlaceGameCounter($place);
                $this->refereePlaces[$gameCounter->getIndex()] = $gameCounter;
            }
        } else {
            /** @var Referee $referee */
            foreach ($this->planning->getReferees() as $referee) {
                $this->referees[(string)$referee->getNumber()] = new GameCounter($referee);
            }
        }

        $games = $this->planning->getGames(Game::ORDER_BY_BATCH);
        foreach ($games as $game) {
            if ($game->getField() !== null) {
                $this->fields[(string)$game->getField()->getNumber()]->increase();
            }
            if ($this->planning->getInput()->selfRefereeEnabled()) {
                if ($game->getRefereePlace() !== null) {
                    $this->refereePlaces[$game->getRefereePlace()->getLocation()]->increase();
                }
            } else {
                if ($game->getReferee() !== null) {
                    $this->referees[(string)$game->getReferee()->getNumber()]->increase();
                }
            }
        }
    }

    public function getCounters(int $totalTypes = null): array
    {
        $counters = [];
        if ($totalTypes === null || ($totalTypes & self::FIELDS) === self::FIELDS) {
            $counters[self::FIELDS] = $this->fields;
        }
        if ($totalTypes === null || ($totalTypes & self::REFEREES) === self::REFEREES) {
            $counters[self::REFEREES] = $this->referees;
        }
        if ($totalTypes === null || ($totalTypes & self::REFEREEPLACES) === self::REFEREEPLACES) {
            $counters[self::REFEREEPLACES] = $this->refereePlaces;
        }
        return $counters;
    }

    public function validate()
    {
        $unequalFields = $this->getMaxUnequal($this->fields);
        if ($unequalFields !== null) {
            throw new UnequalAssignedFieldsException($this->getUnequalDescription($unequalFields, "fields"), E_ERROR);
        }

        $unequalReferees = $this->getMaxUnequal($this->referees);
        if ($unequalReferees !== null) {
            throw new UnequalAssignedRefereesException(
                $this->getUnequalDescription($unequalReferees, "referees"),
                E_ERROR
            );
        }

        $unequalRefereePlaces = $this->getRefereePlaceUnequals();
        if (count($unequalRefereePlaces) > 0) {
            throw new UnequalAssignedRefereePlacesException(
                $this->getUnequalDescription(reset($unequalRefereePlaces), "refereePlaces"), E_ERROR
            );
        }
    }

    protected function shouldValidatePerPoule(): bool
    {
        $nrOfPoules = $this->planning->getPoules()->count();
        if ($this->planning->getInput()->getSelfReferee() === PlanningInput::SELFREFEREE_SAMEPOULE) {
            return true;
        }
        if (($this->planning->getPlaces()->count() % $nrOfPoules) === 0) {
            return false;
        }
        if ($nrOfPoules === 2) {
            return true;
        }
        $input = $this->planning->getInput();
        if ($nrOfPoules > 2 && $input->getTeamup() && $input->selfRefereeEnabled()) {
            return true;
        }
        return false;
    }

    /**
     * @return array|UnequalGameCounter[]
     */
    public function getRefereePlaceUnequals(): array
    {
        $unequals = [];
        if ($this->shouldValidatePerPoule()) {
            $refereePlacesPerPoule = $this->getRefereePlacesPerPoule();
            foreach ($refereePlacesPerPoule as $pouleNr => $refereePlaces) {
                $unequal = $this->getMaxUnequal($refereePlaces);
                if ($unequal !== null) {
                    $unequal->setPouleNr($pouleNr);
                    $unequals[] = $unequal;
                }
            }
        } else {
            $unequal = $this->getMaxUnequal($this->refereePlaces);
            if ($unequal !== null) {
                $unequals[] = $unequal;
            }
        }
        return $unequals;
    }

    protected function getRefereePlacesPerPoule(): array
    {
        $refereePlacesPerPoule = [];
        /** @var PlaceGameCounter $gameCounter */
        foreach ($this->refereePlaces as $gameCounter) {
            /** @var Place $place */
            $place = $gameCounter->getResource();
            $pouleNr = $place->getPoule()->getNumber();
            if (!array_key_exists($pouleNr, $refereePlacesPerPoule)) {
                $refereePlacesPerPoule[$pouleNr] = [];
            }
            $refereePlacesPerPoule[$pouleNr][] = $gameCounter;
        }
        return $refereePlacesPerPoule;
    }

    /**
     * @param array|GameCounter[] $gameCounters
     * @return UnequalGameCounter
     */
    protected function getMaxUnequal(array $gameCounters): ?UnequalGameCounter
    {
        $minNrOfGames = null;
        $minGameCounters = [];
        $maxNrOfGames = null;
        $maxGameCounters = [];
        foreach ($gameCounters as $gameCounter) {
            $nrOfGames = $gameCounter->getNrOfGames();
            if ($minNrOfGames === null || $nrOfGames <= $minNrOfGames) {
                if ($nrOfGames < $minNrOfGames) {
                    $minGameCounters = [];
                }
                $minGameCounters[] = $gameCounter;
                $minNrOfGames = $nrOfGames;
            }
            if ($maxNrOfGames === null || $nrOfGames >= $maxNrOfGames) {
                if ($nrOfGames > $maxNrOfGames) {
                    $maxGameCounters = [];
                }
                $maxGameCounters[] = $gameCounter;
                $maxNrOfGames = $nrOfGames;
            }
        }
        if ($maxNrOfGames - $minNrOfGames <= 1) {
            return null;
        }
        return new UnequalGameCounter(
            $minNrOfGames,
            $minGameCounters,
            $maxNrOfGames,
            $maxGameCounters
        );
    }

    protected function getUnequalDescription(UnequalGameCounter $unequal, string $suffix): string
    {
        $retVal = "too much difference(" . $unequal->getDifference() . ") in number of games for " . $suffix;

        $minGameCounters = array_map(
            function (GameCounter $gameCounter): string {
                return $gameCounter->getIndex();
            },
            $unequal->getMinGameCounters()
        );
        $maxGameCounters = array_map(
            function (GameCounter $gameCounter): string {
                return $gameCounter->getIndex();
            },
            $unequal->getMaxGameCounters()
        );
        $retVal .= "(" . $unequal->getMinNrOfGames() . ": " . join("&", $minGameCounters) . ", ";
        $retVal .= $unequal->getMaxNrOfGames() . ": " . join("&", $maxGameCounters) . ")";
        return $retVal;
    }
}