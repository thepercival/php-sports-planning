<?php

namespace SportsPlanning\Resource;

use SportsHelpers\SelfReferee;
use SportsPlanning\Game;
use SportsPlanning\Place;
use SportsPlanning\Planning;
use SportsPlanning\Resource\GameCounter;
use SportsPlanning\Resource\GameCounter\Place as PlaceGameCounter;

class ResourceCounter
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
}