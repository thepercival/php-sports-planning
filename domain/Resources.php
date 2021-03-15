<?php

namespace SportsPlanning;

use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
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
    private int $nrOfFieldSwitches;
    /**
     * @var array<SportCounter>
     */
    private $sportCounters;
    /**
     * @var array<int>
     */
    private array $sportTimes = [];

    const FIELDS = 1;
    const REFEREES = 2;
    const PLACES = 4;

    /**
     * @param array<Field> $fields
     * @param array<SportCounter>|null $sportCounters
     * @param array<int>|null $sportTimes
     */
    public function __construct(array $fields, array|null $sportCounters = null, array|null $sportTimes = null)
    {
        $this->fields = $fields;
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
     * @return array<Field>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @param Field $field
     */
    public function addField(Field $field): void
    {
        $this->fields[] = $field;
    }

    /**
     * @param array<Field> $fields
     */
    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }

    /**
     * @param Field $field
     */
    public function unshiftField(Field $field): void
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

    public function orderFields(): void
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

    public function getGameNrOfSportsToGo(AgainstGame|TogetherGame $game): int
    {
        $gameNrToGo = 0;
        foreach ($game->getPlaces() as $gamePlace) {
            $gameNrToGo += $this->getSportCounter($gamePlace->getPlace())->getNrOfSportsToGo();
        }
        return $gameNrToGo;
    }

    public function getFieldIndex(): ?int
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
     * @return array<SportCounter>
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

    /**
     * @param TogetherGame|AgainstGame $game
     * @param Sport $sport
     * @return bool
     */
    public function isSportAssignable($game, Sport $sport): bool
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
