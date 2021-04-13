<?php
declare(strict_types=1);

namespace SportsPlanning;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Exception;
use SportsHelpers\Sport\GameAmountVariant;

class Sport extends GameAmountVariant
{
    /**
     * @phpstan-var ArrayCollection<int|string, Field>|PersistentCollection<int|string, Field>
     * @psalm-var ArrayCollection<int|string, Field>
     */
    protected ArrayCollection|PersistentCollection $fields;

    public function __construct(
        protected Planning $planning,
        protected int $number,
        int $gameMode,
        int $nrOfGamePlaces,
        int $gameAmount
    ) {
        parent::__construct($gameMode, $nrOfGamePlaces, 0, $gameAmount);
        $this->fields = new ArrayCollection();
    }

    public function getPlanning(): Planning
    {
        return $this->planning;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * @phpstan-return ArrayCollection<int|string, Field>|PersistentCollection<int|string, Field>
     * @psalm-return ArrayCollection<int|string, Field>
     */
    public function getFields(): ArrayCollection|PersistentCollection
    {
        return $this->fields;
    }

    public function getField(int $number): Field
    {
        foreach ($this->getFields() as $field) {
            if ($field->getNumber() === $number) {
                return $field;
            }
        }
        throw new Exception('het veld kan niet gevonden worden', E_ERROR);
    }

    public function getNrOfFields(): int
    {
        return $this->getFields()->count();
    }
}
