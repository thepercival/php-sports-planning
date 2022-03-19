<?php

declare(strict_types=1);

namespace SportsPlanning\Planning\Validator;

use SportsHelpers\SelfReferee;
use SportsPlanning\Exception\NoBestPlanning as UnequalAssignedFieldsException;
use SportsPlanning\Exception\UnequalAssignedRefereePlaces as UnequalAssignedRefereePlacesException;
use SportsPlanning\Exception\UnequalAssignedReferees as UnequalAssignedRefereesException;
use SportsPlanning\Game;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Resource\GameCounter;
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

    public const FIELDS = 1;
    public const REFEREES = 2;
    public const REFEREEPLACES = 4;

    public function __construct(protected Planning $planning)
    {
        $this->fieldMap = [];
        $this->refereeMap = [];
        $this->refereePlaceMap = [];
        $this->init();
    }

    protected function init(): void
    {
        foreach ($this->planning->getInput()->getFields() as $field) {
            $this->fieldMap[$field->getUniqueIndex()] = new GameCounter($field);
        }

        if ($this->planning->getInput()->selfRefereeEnabled()) {
            foreach ($this->planning->getInput()->getPlaces() as $place) {
                $this->refereePlaceMap[$place->getUniqueIndex()] = new PlaceGameCounter($place);
            }
        } else {
            foreach ($this->planning->getInput()->getReferees() as $referee) {
                $this->refereeMap[$referee->getUniqueIndex()] = new GameCounter($referee);
            }
        }

        $games = $this->planning->getGames(Game::ORDER_BY_BATCH);
        foreach ($games as $game) {
            $this->fieldMap[$game->getField()->getUniqueIndex()]->increment();
            if ($this->planning->getInput()->selfRefereeEnabled()) {
                $refereePlace = $game->getRefereePlace();
                if ($refereePlace !== null) {
                    $this->refereePlaceMap[$refereePlace->getUniqueIndex()]->increment();
                }
            } else {
                $referee = $game->getReferee();
                if ($referee !== null) {
                    $this->refereeMap[$referee->getUniqueIndex()]->increment();
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
        if (!$this->planning->getInput()->hasMultipleSports()) {
            $unequalFields = $this->getMaxUnequal($this->fieldMap);
            if ($unequalFields !== null) {
                throw new UnequalAssignedFieldsException($this->getUnequalDescription($unequalFields, "fields"), E_ERROR);
            }
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
        $nrOfPoules = $this->planning->getInput()->getPoules()->count();
        if ($this->planning->getInput()->getSelfReferee() === SelfReferee::SamePoule) {
            return true;
        }
        if (($this->planning->getInput()->getPlaces()->count() % $nrOfPoules) === 0) {
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
        } elseif ($this->planning->getInput()->createPouleStructure()->isAlmostBalanced()) {
            $unequal = $this->getMaxUnequal($this->refereePlaceMap);
            if ($unequal !== null) {
                $unequals[] = $unequal;
            }
        }
        return $unequals;
    }

    /**
     * @return array<int,array<string|int,PlaceGameCounter>>
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
            $refereePlacesPerPoule[$pouleNr][$gameCounter->getIndex()] = $gameCounter;
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
         * @var int|null $minNrOfGames
         * @var int|null $maxNrOfGames
         * @var array<int|string,GameCounter> $maxGameCounters
         */
        list($minNrOfGames, $maxNrOfGames, $maxGameCounters) = $this->setCounters($gameCounters);
        if ($minNrOfGames === null || $maxNrOfGames === null || $maxNrOfGames - $minNrOfGames <= 1) {
            return null;
        }
        $otherGameCounters = array_filter($gameCounters, function (GameCounter $gameCounterIt) use ($maxNrOfGames): bool {
            return ($gameCounterIt->getNrOfGames() + 1) < $maxNrOfGames;
        });
//        uasort($otherGameCounters, function (GameCounter $a, GameCounter $b): int {
//            return $a->getNrOfGames() < $b->getNrOfGames() ? -1 : 1;
//        });
        return new UnequalGameCounter(
            $minNrOfGames,
            $otherGameCounters,
            $maxNrOfGames,
            $maxGameCounters
        );
    }

    /**
     * @param array<int|string,GameCounter> $gameCounters
     * @return list<int|null|array<int|string,GameCounter>>
     */
    protected function setCounters(array $gameCounters): array
    {
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
                $maxGameCounters[$gameCounter->getIndex()] = $gameCounter;
                $maxNrOfGames = $nrOfGames;
            }
        }
        return array($minNrOfGames,$maxNrOfGames,$maxGameCounters);
    }

    protected function getUnequalDescription(UnequalGameCounter $unequal, string $suffix): string
    {
        $retVal = "too much difference(" . $unequal->getDifference() . ") in number of games for " . $suffix;
        return $retVal . '(' . $unequal . ')';
    }
}
