<?php

namespace SportsPlanning;

use Exception;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Sport\Counter as SportCounter;

class Resources
{
    /**
     * @var array<int,Field>
     */
    private array $fieldMap;
    private int|null $fieldIndex  = null;
    private int $nrOfFieldSwitches;
    /**
     * @var array<string,SportCounter>
     */
    private array $sportCounters;
    /**
     * @var array<int,int>
     */
    private array $sportTimes = [];

    const FIELDS = 1;
    const REFEREES = 2;
    const PLACES = 4;

    /**
     * @param array<int,Field> $fields
     * @param array<string,SportCounter>|null $sportCounters
     * @param array<int,int>|null $sportTimes
     */
    public function __construct(array $fields, array|null $sportCounters = null, array|null $sportTimes = null)
    {
        $this->fieldMap = $fields;
        $this->sportCounters = $sportCounters ?? [];
        if ($sportTimes !== null) {
            $this->sportTimes = $sportTimes;
        } elseif ($sportCounters !== null) {
            foreach ($fields as $field) {
                $this->sportTimes[$field->getSport()->getNumber()] = 0;
            }
        }
        $this->nrOfFieldSwitches = 0;
    }

    /**
     * @return array<int,Field>
     */
    public function getFields(): array
    {
        return $this->fieldMap;
    }

    public function addField(Field $field): void
    {
        $this->fieldMap[] = $field;
    }

    /**
     * @param array<int,Field> $fields
     */
    public function setFields(array $fields): void
    {
        $this->fieldMap = $fields;
    }

    public function unshiftField(Field $field): void
    {
        array_unshift($this->fieldMap, $field);
    }

    public function shiftField(): Field
    {
        return $this->removeField(0);
    }

    public function removeField(int $fieldIndex): Field
    {
        if (isset($this->fieldMap[$fieldIndex]) === false) {
            throw new Exception('veld kan niet vewijderd worden', E_ERROR);
        }
        $removedField = $this->fieldMap[$fieldIndex];
        unset($this->fieldMap[$fieldIndex]);
        return $removedField;
    }

    public function orderFields(): void
    {
        uasort($this->fieldMap, function (Field $fieldA, Field $fieldB): int {
            return $this->sportTimes[$fieldA->getSport()->getNumber() ] > $this->sportTimes[$fieldB->getSport()->getNumber() ] ? -1 : 1;
        });
    }

    public function switchFields(): bool
    {
        return false;
        /*if( ++$this->nrOfFieldSwitches >= count($this->fields) ) {
            return false;
        }
        array_push( $this->fields, array_shift($this->fields) );
        return true;*/
//        $newFields = [];
//        for( $i = 0 ; $i < count($fields) ; $i++ ) {
//            $newFields[] = $fields;
//            array_push( $fields, array_shift($fields) );
//        }
//        return $newFields;
//
//        return [$fields];
//        $fieldCombinations = [];
//        $pc_permute = function($items, $perms = array()) use (&$pc_permute, &$fieldCombinations) {
//            if (empty($items)) {
//                $fieldCombinations[] = $perms;
//            }
//            for ($i = count($items) - 1; $i >= 0; --$i) {
//                $newitems = $items;
//                $newperms = $perms;
//                list($foo) = array_splice($newitems, $i, 1);
//                array_unshift($newperms, $foo);
//                $pc_permute($newitems, $newperms);
//            }
//        };
//        $pc_permute($fields);
//        return $fieldCombinations;
    }

    public function getGameNrOfSportsToGo(AgainstGame|TogetherGame $game): int
    {
        $gameNrToGo = 0;
        foreach ($game->getPlaces() as $gamePlace) {
            $gameNrToGo += $this->getSportCounter($gamePlace->getPlace())->getNrOfSportsToGo();
        }
        return $gameNrToGo;
    }

    public function getFieldIndex(): int|null
    {
        return $this->fieldIndex;
    }

    public function setFieldIndex(int|null $fieldIndex = null): void
    {
        $this->fieldIndex = $fieldIndex;
    }

    public function getNrOfFieldSwitches(): int
    {
        return $this->nrOfFieldSwitches;
    }

    public function setNrOfFieldSwitches(int $nrOfFieldSwitches): void
    {
        $this->nrOfFieldSwitches = $nrOfFieldSwitches;
    }

    /**
     * @return array<string,SportCounter>
     */
    public function getSportCounters(): array
    {
        return $this->sportCounters;
    }

    public function assignSport(TogetherGame|AgainstGame $game): void
    {
        if (count($this->sportCounters) === 0) {
            return;
        }
        $this->sportTimes[$game->getSport()->getNumber()]++;
        foreach ($game->getPlaces() as $gamePlace) {
            $this->getSportCounter($gamePlace->getPlace())->addGame($game->getSport());
        }
    }

    public function isSportAssignable(TogetherGame|AgainstGame $game, Sport $sport): bool
    {
        if (count($this->sportCounters) === 0) {
            return true;
        }
        foreach ($game->getPlaces() as $gamePlace) {
            if (!$this->getSportCounter($gamePlace->getPlace())->isAssignable($sport)) {
                return false;
            };
        }
        return true;
    }

    public function getSportCounter(Place $place): SportCounter
    {
        return $this->sportCounters[$place->getLocation()];
    }

    public function copy(): Resources
    {
        $newSportCounters = [];
        foreach ($this->getSportCounters() as $location => $sportCounter) {
            $newSportCounters[$location] = $sportCounter->copy();
        }
        $resources = new Resources($this->getFields(), $newSportCounters, $this->sportTimes);
        $resources->setFieldIndex($this->getFieldIndex());
        $resources->setNrOfFieldSwitches($this->getNrOfFieldSwitches());
        return $resources;
    }
}
