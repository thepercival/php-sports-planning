<?php
declare(strict_types=1);

namespace SportsPlanning;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Exception;
use SportsHelpers\Sport\PersistVariant;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;

class Sport extends PersistVariant implements \Stringable
{
    /**
     * @phpstan-var ArrayCollection<int|string, Field>|PersistentCollection<int|string, Field>
     * @psalm-var ArrayCollection<int|string, Field>
     */
    protected ArrayCollection|PersistentCollection $fields;
    protected int $number;

    public function __construct(protected Input $input, PersistVariant $sportVariant)
    {
        parent::__construct(
            $sportVariant->getGameMode(),
            $sportVariant->getNrOfHomePlaces(),
            $sportVariant->getNrOfAwayPlaces(),
            $sportVariant->getNrOfH2H(),
            $sportVariant->getNrOfPartials(),
            $sportVariant->getNrOfGamePlaces(),
            $sportVariant->getNrOfGamesPerPlace()
        );
        $this->number = $input->getSports()->count() + 1;
        $this->input->getSports()->add($this);
        $this->fields = new ArrayCollection();
    }

    public function getInput(): Input
    {
        return $this->input;
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

    public function createVariantWithFields(): SportVariantWithFields
    {
        return new SportVariantWithFields($this->createVariant(), $this->getNrOfFields());
    }

    public function __toString(): string
    {
        return $this->createVariant() . ' f(' . $this->getNrOfFields() . ')';
    }
}
