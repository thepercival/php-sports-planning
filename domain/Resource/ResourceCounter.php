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
        if ($totalTypes === null || ($totalTypes & ResourceType::Fields->value) === ResourceType::Fields->value) {
            $counters[ResourceType::Fields->value] = $this->fieldMap;
        }
        if ($totalTypes === null || ($totalTypes & ResourceType::Referees->value) === ResourceType::Referees->value) {
            $counters[ResourceType::Referees->value] = $this->refereeMap;
        }
        if ($totalTypes === null || ($totalTypes & ResourceType::RefereePlaces->value) === ResourceType::RefereePlaces->value) {
            $counters[ResourceType::RefereePlaces->value] = $this->refereePlaceMap;
        }
        return $counters;
    }

    /**
     * @return array<string,GameCounter>
     */
    public function getCounter(ResourceType $resourceType): array {
        if( $resourceType === ResourceType::Fields ) {
            return $this->fieldMap;
        } else if( $resourceType === ResourceType::Referees ) {
            return $this->refereeMap;
        } else if( $resourceType === ResourceType::RefereePlaces ) {
            return $this->refereePlaceMap;
        }
        throw new \Exception('unknown resourcetype', E_ERROR);
    }
}