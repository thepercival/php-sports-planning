<?php

declare(strict_types=1);

namespace SportsPlanning\Resource;

use Exception;
use SportsPlanning\Field;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Input;
use SportsPlanning\Poule;
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
    /**
     * @var array<string, bool>|null
     */
    private array|null $fieldPouleMap = null;

    public function __construct(Input $input)
    {
        $this->unassignedFields = $input->getFields();
        $this->initFieldPouleMap($input);
    }

    private function initFieldPouleMap(Input $input): void
    {
        if ($input->hasMultipleSports() || !$input->createPouleStructure()->isBalanced()) {
            return;
        }
        $fields = $input->getFields();
        $poules = $input->getPoules();
        $nrOfFields = count($fields);
        $nrOfPoules = count($poules);
        // poules A,B en fields 1,2,3,4,5,6    :   A1, A2, A3, B4, B5, B6
        if ($nrOfFields >= $nrOfPoules && ($nrOfFields % $nrOfPoules) === 0) {
            $this->fieldPouleMap = [];
            $nrOfFieldsPerPoule = (int)($nrOfFields / $nrOfPoules);
            foreach ($fields as $field) {
                $rest = $field->getNumber() % $nrOfFieldsPerPoule;
                $addToCeil = $rest === 0 ? 0 : ($nrOfFieldsPerPoule - $rest);
                $pouleNr = (int) (($field->getNumber() + $addToCeil)  / $nrOfFieldsPerPoule);
                $poule = $input->getPoule($pouleNr);
                $index = $this->getFieldPouleMapIndex($field, $poule);
                $this->fieldPouleMap[$index] = true;
            }
        } elseif ($nrOfFields < $nrOfPoules && ($nrOfPoules % $nrOfFields) === 0) {
            // poules A,B,C,D en fields 1, 2   :   A1, B1, C2, D2
            $this->fieldPouleMap = [];
            $sport = $input->getSport(1);
            $nrOfPoulesPerField = (int)($nrOfPoules / $nrOfFields);
            foreach ($poules as $poule) {
                $rest = $poule->getNumber() % $nrOfPoulesPerField;
                $addToCeil = $rest === 0 ? 0 : ($nrOfPoulesPerField - $rest);
                $fieldNr = (int) (($poule->getNumber() + $addToCeil)  / $nrOfPoulesPerField);
                $field = $sport->getField($fieldNr);
                $index = $this->getFieldPouleMapIndex($field, $poule);
                $this->fieldPouleMap[$index] = true;
            }
        }
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

    public function isSomeFieldAssignable(Sport $sport, Poule $poule): bool
    {
        foreach ($this->unassignedFields as $unassignedField) {
            if ($this->isFieldAssignable($unassignedField, $sport, $poule)) {
                return true;
            }
        }
        return false;
    }

    public function assignToGame(TogetherGame|AgainstGame $game): void
    {
        foreach ($this->unassignedFields as $unassignedField) {
            if (!$this->isFieldAssignable($unassignedField, $game->getSport(), $game->getPoule())) {
                continue;
            }
            $this->assign($unassignedField);
            $game->setField($unassignedField);
            return;
        }
        throw new Exception('no field could be assigned', E_ERROR);
    }

    public function fill(): void
    {
        while ($assignedField = array_shift($this->assignedFields)) {
            array_push($this->unassignedFields, $assignedField);
        }
    }

    protected function isUnassigned(Field $field): bool
    {
        $idx = array_search($field, $this->unassignedFields, true);
        return $idx !== false;
    }

    protected function assign(Field $field): void
    {
        $idx = array_search($field, $this->assignedFields, true);
        if ($idx !== false) {
            throw new Exception('field could be assigned', E_ERROR);
        }
        $idx = array_search($field, $this->unassignedFields, true);
        if ($idx === false) {
            throw new Exception('field is not unassigned', E_ERROR);
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

    protected function isFieldAssignable(Field $field, Sport $sport, Poule $poule): bool
    {
        if ($field->getSport() !== $sport) {
            return false;
        }
        return $this->fieldPouleMap === null|| isset($this->fieldPouleMap[$this->getFieldPouleMapIndex($field, $poule)]);
    }

    protected function getFieldPouleMapIndex(Field $field, Poule $poule): string
    {
        return 'P' . $poule->getNumber() . '-F' . $field->getNumber();
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
