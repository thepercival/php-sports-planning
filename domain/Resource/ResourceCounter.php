<?php

namespace SportsPlanning\Resource;

use SportsHelpers\SelfReferee;
use SportsPlanning\Game\GameAbstract;
use SportsPlanning\Planning;
use SportsPlanning\Resource\GameCounter\GameCounterForPlace;

final class ResourceCounter
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
        $configuration = $this->planning->getConfiguration();
        $selfRefereeEnabled = $configuration->refereeInfo->selfRefereeInfo->selfReferee !== SelfReferee::Disabled;

        foreach ($this->planning->sports as $sport) {
            foreach ($sport->fields as $field) {
                $this->fieldMap[$field->getUniqueIndex()] = new GameCounter($field);
            }
        }

        if ($selfRefereeEnabled) {
            foreach ($this->planning->poules as $poule) {
                foreach ($poule->places as $place) {
                    $this->refereePlaceMap[$place->getUniqueIndex()] = new GameCounterForPlace($place);
                }
            }
        } else {
            foreach ($this->planning->referees as $referee) {
                $this->refereeMap[$referee->getUniqueIndex()] = new GameCounter($referee);
            }
        }

        $games = $this->planning->getGames(Planning::ORDER_GAMES_BY_BATCH);
        foreach ($games as $game) {
            $fieldGameCounter = $this->fieldMap[$game->getField()->getUniqueIndex()];
            $this->fieldMap[$game->getField()->getUniqueIndex()] = $fieldGameCounter->increment();
            if ($selfRefereeEnabled) {
                $refereePlaceUniqueIndex = $game->getRefereePlaceUniqueIndex();
                if ($refereePlaceUniqueIndex !== null) {
                    $refereePlaceGameCounter = $this->refereePlaceMap[$refereePlaceUniqueIndex];
                    $this->refereePlaceMap[$refereePlaceUniqueIndex] = $refereePlaceGameCounter->increment();
                }
            } else {
                $refereeNr = $game->getRefereeNr();
                if ($refereeNr !== null) {
                    $refereeNr = (string)$refereeNr;
                    $refereeGameCounter = $this->refereeMap[$refereeNr];
                    $this->refereeMap[$refereeNr] = $refereeGameCounter->increment();
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
        } // else if( $resourceType === ResourceType::RefereePlaces ) {
            return $this->refereePlaceMap;
        // }
        // throw new \Exception('unknown resourcetype', E_ERROR);
    }
}