<?php

namespace SportsPlanning;

use SportsPlanning\Sport\Counter as SportCounter;

class Resources
{
    /**
     * @var array|Field[]
     */
    private $fields;
    /**
     * @var int|null
     */
    private $fieldIndex;
    /**
     * @var int
     */
    private $nrOfFieldSwitches;
    /**
     * @var array|SportCounter[]
     */
    private $sportCounters;
    /**
     * @var array|int[]
     */
    private $sportTimes;

    const FIELDS = 1;
    const REFEREES = 2;
    const PLACES = 4;

    /**
     * Resources constructor.
     * @param array|Field[] $fields
     * @param array|SportCounter[]|null $sportCounters
     * @param array|int[]|null $sportTimes
     */
    public function __construct(array $fields, array $sportCounters = null, array $sportTimes = null)
    {
        $this->fields = $fields;
        $this->sportCounters = $sportCounters;
        if ($sportTimes === null && $sportCounters !== null) {
            /** @var Field $field */
            foreach ($fields as $field) {
                $sportTimes[$field->getSport()->getNumber()] = 0;
            }
        }
        $this->sportTimes = $sportTimes;
        $this->nrOfFieldSwitches = 0;
    }

    /**
     * @return array|Field[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @param Field $field
     */
    public function addField(Field $field)
    {
        $this->fields[] = $field;
    }

    /**
     * @param array|Field[] $fields
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * @param Field $field
     */
    public function unshiftField(Field $field)
    {
        array_unshift($this->fields, $field);
    }

    /**
     * @return Field
     */
    public function shiftField(): Field
    {
        return $this->removeField(0);
    }

    /**
     * @return Field
     */
    public function removeField(int $fieldIndex): Field
    {
        $removedFields = array_splice($this->fields, $fieldIndex, 1);
        return reset($removedFields);
    }

    public function orderFields()
    {
        uasort($this->fields, function (Field $fieldA, Field $fieldB): int {
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

    public function getGameNrOfSportsToGo(Game $game): int
    {
        $gameNrToGo = 0;
        foreach ($this->getPlaces($game) as $place) {
            $gameNrToGo += $this->getSportCounter($place)->getNrOfSportsToGo();
        }
        return $gameNrToGo;
    }

    /**
     * @return int
     */
    public function getFieldIndex(): ?int
    {
        return $this->fieldIndex;
    }

    /**
     * @param int $fieldIndex
     */
    public function setFieldIndex(int $fieldIndex = null)
    {
        $this->fieldIndex = $fieldIndex;
    }

    /**
     * @return int
     */
    public function getNrOfFieldSwitches(): ?int
    {
        return $this->nrOfFieldSwitches;
    }

    /**
     * @param int $nrOfFieldSwitches
     */
    public function setNrOfFieldSwitches(int $nrOfFieldSwitches)
    {
        $this->nrOfFieldSwitches = $nrOfFieldSwitches;
    }

    /**
     * @return array|SportCounter[]|null
     */
    public function getSportCounters(): ?array
    {
        return $this->sportCounters;
    }

    public function assignSport(Game $game, Sport $sport)
    {
        if ($this->sportCounters === null) {
            return;
        }
        $this->sportTimes[$sport->getNumber()]++;
        foreach ($this->getPlaces($game) as $placeIt) {
            $this->getSportCounter($placeIt)->addGame($sport);
        }
    }

    public function isSportAssignable(Game $game, Sport $sport): bool
    {
        if ($this->sportCounters === null) {
            return true;
        }
        foreach ($this->getPlaces($game) as $placeIt) {
            if (!$this->getSportCounter($placeIt)->isAssignable($sport)) {
                return false;
            };
        }
        return true;
    }

    public function getSportCounter(Place $place): SportCounter
    {
        return $this->sportCounters[$place->getLocation()];
    }

    /**
     * @param Game $game
     * @return array|Place[]
     */
    protected function getPlaces(Game $game): array
    {
        return array_map(function ($gamePlace) {
            return $gamePlace->getPlace();
        }, $game->getPlaces()->toArray());
    }

    public function copy(): Resources
    {
        $newSportCounters = null;
        if ($this->getSportCounters() !== null) {
            $newSportCounters = [];
            foreach ($this->getSportCounters() as $location => $sportCounter) {
                $newSportCounters[$location] = $sportCounter->copy();
            }
        }
        $resources = new Resources($this->getFields(), $newSportCounters, $this->sportTimes);
        $resources->setFieldIndex($this->getFieldIndex());
        $resources->setNrOfFieldSwitches($this->getNrOfFieldSwitches());
        return $resources;
    }
}
