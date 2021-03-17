<?php
declare(strict_types=1);

namespace SportsPlanning\Validator;

use SportsPlanning\Exception\UnequalAssignedFields as UnequalAssignedFieldsException;
use SportsPlanning\Exception\UnequalAssignedReferees as UnequalAssignedRefereesException;
use SportsPlanning\Exception\UnequalAssignedRefereePlaces as UnequalAssignedRefereePlacesException;
use SportsPlanning\Game;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Resource\GameCounter;
use SportsPlanning\SelfReferee;
use SportsPlanning\Resource\GameCounter\Place as PlaceGameCounter;
use SportsPlanning\Resource\GameCounter\Unequal as UnequalGameCounter;

class GameAssignments
{
    /**
     * @var array<string,GameCounter>
     */
    protected array $fieldMap;
    /**
     * @var array<string,GameCounter>
     */
    protected array $refereeMap;
    /**
     * @var array<string,GameCounter>
     */
    protected array $refereePlaceMap;

    const FIELDS = 1;
    const REFEREES = 2;
    const REFEREEPLACES = 4;

    public function __construct(protected Planning $planning)
    {
        $this->fieldMap = [];
        $this->refereeMap = [];
        $this->refereePlaceMap = [];
        $this->init();
    }

    protected function init(): void
    {
        foreach ($this->planning->getFields() as $field) {
            $this->fieldMap[$field->getUniqueIndex()] = new GameCounter($field);
        }

        if ($this->planning->getInput()->selfRefereeEnabled()) {
            foreach ($this->planning->getPlaces() as $place) {
                $this->refereePlaceMap[$place->getUniqueIndex()] = new PlaceGameCounter($place);
            }
        } else {
            foreach ($this->planning->getReferees() as $referee) {
                $this->refereeMap[$referee->getUniqueIndex()] = new GameCounter($referee);
            }
        }

        $games = $this->planning->getGames(Game::ORDER_BY_BATCH);
        foreach ($games as $game) {
            $field = $game->getField();
            if ($field !== null) {
                $this->fieldMap[$field->getUniqueIndex()]->increase();
            }
            if ($this->planning->getInput()->selfRefereeEnabled()) {
                $refereePlace = $game->getRefereePlace();
                if ($refereePlace !== null) {
                    $this->refereePlaceMap[$refereePlace->getUniqueIndex()]->increase();
                }
            } else {
                $referee = $game->getReferee();
                if ($referee !== null) {
                    $this->refereeMap[$referee->getUniqueIndex()]->increase();
                }
            }
        }
    }

    /**
     * @param int|null $totalTypes
     * @return array<int,array<string,GameCounter>>
     */
    public function getCounters(int|null $totalTypes = null): array
    {
        $counters = [];
        if ($totalTypes === null || ($totalTypes & self::FIELDS) === self::FIELDS) {
            $counters[self::FIELDS] = $this->fieldMap;
        }
        if ($totalTypes === null || ($totalTypes & self::REFEREES) === self::REFEREES) {
            $counters[self::REFEREES] = $this->refereeMap;
        }
        if ($totalTypes === null || ($totalTypes & self::REFEREEPLACES) === self::REFEREEPLACES) {
            $counters[self::REFEREEPLACES] = $this->refereePlaceMap;
        }
        return $counters;
    }

    public function validate(): void
    {
        $unequalFields = $this->getMaxUnequal($this->fieldMap);
        if ($unequalFields !== null) {
            throw new UnequalAssignedFieldsException($this->getUnequalDescription($unequalFields, "fields"), E_ERROR);
        }

        $unequalReferees = $this->getMaxUnequal($this->refereeMap);
        if ($unequalReferees !== null) {
            throw new UnequalAssignedRefereesException(
                $this->getUnequalDescription($unequalReferees, "referees"),
                E_ERROR
            );
        }

        $unequalRefereePlaces = $this->getRefereePlaceUnequals();
        if (count($unequalRefereePlaces) > 0) {
            throw new UnequalAssignedRefereePlacesException(
                $this->getUnequalDescription(reset($unequalRefereePlaces), "refereePlaces"),
                E_ERROR
            );
        }
    }

    protected function shouldValidatePerPoule(): bool
    {
        $nrOfPoules = $this->planning->getPoules()->count();
        if ($this->planning->getInput()->getSelfReferee() === SelfReferee::SAMEPOULE) {
            return true;
        }
        if (($this->planning->getPlaces()->count() % $nrOfPoules) === 0) {
            return false;
        }
        if ($nrOfPoules === 2) {
            return true;
        }
        $input = $this->planning->getInput();
        if ($nrOfPoules > 2 && $input->selfRefereeEnabled()) {
            return true;
        }
        return false;
    }

    /**
     * @return list<UnequalGameCounter>
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
        } elseif ($this->planning->getPouleStructure()->isAlmostBalanced()) {
            $unequal = $this->getMaxUnequal($this->refereePlaceMap);
            if ($unequal !== null) {
                $unequals[] = $unequal;
            }
        }
        return $unequals;
    }

    /**
     * @return array<int,array<int,PlaceGameCounter>>
     */
    protected function getRefereePlacesPerPoule(): array
    {
        $refereePlacesPerPoule = [];
        /** @var PlaceGameCounter $gameCounter */
        foreach ($this->refereePlaceMap as $gameCounter) {
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
     * @param array<int|string,GameCounter> $gameCounters
     * @return UnequalGameCounter|null
     */
    protected function getMaxUnequal(array $gameCounters): UnequalGameCounter|null
    {
        /**
         * @return list<int|list<int>>
         */
        $setCounters = function () use ($gameCounters) : array {
            $minNrOfGames = null;
            $maxNrOfGames = null;
            $maxGameCounters = [];
            foreach ($gameCounters as $gameCounter) {
                $nrOfGames = $gameCounter->getNrOfGames();
                if ($minNrOfGames === null || $nrOfGames < $minNrOfGames) {
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
            return array($minNrOfGames,$maxNrOfGames,$maxGameCounters);
        };
        list($minNrOfGames, $maxNrOfGames, $maxGameCounters) = $setCounters();
        if ($minNrOfGames === null || $maxNrOfGames === null || $maxNrOfGames - $minNrOfGames <= 1) {
            return null;
        }
        $otherGameCounters = array_filter($gameCounters, function (GameCounter $gameCounterIt) use ($maxNrOfGames): bool {
            return ($gameCounterIt->getNrOfGames() + 1) < $maxNrOfGames;
        });
        uasort($otherGameCounters, function (GameCounter $a, GameCounter $b): int {
            return $a->getNrOfGames() < $b->getNrOfGames() ? -1 : 1;
        });
        return new UnequalGameCounter(
            $minNrOfGames,
            $otherGameCounters,
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
