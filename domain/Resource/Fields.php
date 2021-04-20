<?php
declare(strict_types=1);

namespace SportsPlanning\Resource;

use SportsPlanning\Field;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Planning;
use SportsPlanning\Sport;

class Fields
{
    /**
     * @var list<Field>
     */
    private array $unassignedFields;
    /**
     * @var list<Field>
     */
    private array $assignedFields = [];

    public function __construct(Planning $planning)
    {
        $this->unassignedFields = $planning->getInput()->getFields();
    }

    protected function isUnassigned(Field $field): bool
    {
        $idx = array_search($field, $this->unassignedFields, true);
        return $idx !== false;
    }

    public function fill(): void
    {
        while ($assignedField = array_shift($this->assignedFields)) {
            array_push($this->unassignedFields, $assignedField);
        }
    }

    public function assign(Field $field): void
    {
        $idx = array_search($field, $this->assignedFields, true);
        if ($idx !== false) {
            throw new \Exception('field could be assigned', E_ERROR);
        }
        $idx = array_search($field, $this->unassignedFields, true);
        if ($idx === false) {
            throw new \Exception('field is not unassigned', E_ERROR);
        }
        array_splice($this->unassignedFields, $idx, 1);
        array_push($this->assignedFields, $field);
    }

    /*public function unassign(Field $field): void
    {
        $idx = array_search($field, $this->unassignedFields, true);
        if ($idx !== false) {
            throw new \Exception('field is already unassigned', E_ERROR);
        }
        $idx = array_search($field, $this->assignedFields, true);
        if ($idx === false) {
            throw new \Exception('field is not yet assigned', E_ERROR);
        }
        array_splice($this->assignedFields, $idx, 1);
        array_push($this->unassignedFields, $field);
    }*/

    public function assignToGame(TogetherGame|AgainstGame $game): void
    {
        $gameSport = $game->getSport();
        foreach ($this->unassignedFields as $unassignedField) {
            if ($unassignedField->getSport() !== $gameSport) {
                continue;
            }
            $this->assign($unassignedField);
            $game->setField($unassignedField);
            return;
        }
        throw new \Exception('no field could be assigned', E_ERROR);
    }

    /**
     * @param Sport $sport
     * @return list<Field>
     */
    public function getAssignableFields(Sport $sport): array
    {
        return array_values($sport->getFields()->filter(function (Field $field): bool {
            return $this->isUnassigned($field);
        })->toArray());
    }

    public function isSomeFieldAssignable(Sport $sport): bool {
        foreach ($this->unassignedFields as $unassignedField) {
            if ($unassignedField->getSport() === $sport) {
                return true;
            }
        }
        return false;
    }

//    public function copy(Planning $planning): Fields
//    {
//        $fields = new Fields($planning);
//        foreach (array_reverse($this->assignedFields) as $field) {
//            $fields->assign($field);
//        }
//        return $fields;
//    }
}
