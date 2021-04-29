<?php
declare(strict_types=1);

namespace SportsPlanning\Input;

use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsHelpers\SportRange;

/**
 * @template TKey
 * @template TValue
 * @implements \Iterator<TKey, TValue>
 */
class AgainstSportsIterator implements \Iterator
{
    protected SportRange $sidePlacesRange;

    protected int $nrOfFields;
    protected int $nrOfHomePlaces;
    protected int $nrOfAwayPlaces;
    protected int $nrOfH2H;
    /**
     * @var SportVariantWithFields|null
     */
    protected $current;

    public function __construct(
        protected SportRange $fieldRange,
        protected SportRange $nrOfH2HRange
    ) {
        $this->sidePlacesRange = new SportRange(1, 2);
        $this->rewind();
    }

    protected function rewindNrOfFields(): void
    {
        $this->nrOfFields = $this->fieldRange->getMin();
        $this->rewindNrOfHomePlaces();
    }

    protected function rewindNrOfHomePlaces(): void
    {
        $this->nrOfHomePlaces = $this->sidePlacesRange->getMin();
        if ($this->nrOfHomePlaces < 1) {
            $this->nrOfHomePlaces = 1;
        }
        $this->rewindNrOfAwayPlaces();
    }

    protected function rewindNrOfAwayPlaces(): void
    {
        $this->nrOfAwayPlaces = $this->sidePlacesRange->getMin();
        if ($this->nrOfAwayPlaces < 1) {
            $this->nrOfAwayPlaces = 1;
        }
        $this->rewindNrOfH2H();
    }

    protected function rewindNrOfH2H(): void
    {
        $this->nrOfH2H = $this->nrOfH2HRange->getMin();
    }

    public function current() : SportVariantWithFields|null
    {
        return $this->current;
    }

    public function key() : string
    {
        return (string)$this->current;
    }

    public function next()
    {
        if ($this->current === null) {
            return;
        }
        if ($this->incrementValue() === false) {
            $this->current = null;
            return;
        }
        $this->current = $this->createAgainstSportVariantWithFields();
    }

    public function rewind()
    {
        $this->rewindNrOfFields();
        $this->current = $this->createAgainstSportVariantWithFields();
    }

    public function valid() : bool
    {
        return $this->current !== null;
    }

    protected function createAgainstSportVariantWithFields(): SportVariantWithFields
    {
        if ($this->nrOfHomePlaces + $this->nrOfAwayPlaces > 2) {
            $againstSportVariant = new AgainstSportVariant($this->nrOfHomePlaces, $this->nrOfAwayPlaces, 0, 1);
        } else {
            $againstSportVariant = new AgainstSportVariant($this->nrOfHomePlaces, $this->nrOfAwayPlaces, $this->nrOfH2H, 0);
        }

        return new SportVariantWithFields($againstSportVariant, $this->nrOfFields);
    }

    protected function incrementValue(): bool
    {
        return $this->incrementNrOfH2H();
    }

    protected function incrementNrOfH2H(): bool
    {
        if ($this->nrOfH2H === $this->nrOfH2HRange->getMax()) {
            return $this->incrementNrOfAwayPlaces();
        }
        $this->nrOfH2H++;
        return true;
    }

    protected function incrementNrOfAwayPlaces(): bool
    {
        if ($this->nrOfAwayPlaces === $this->sidePlacesRange->getMax()) {
            return $this->incrementNrOfHomePlaces();
        }
        $this->nrOfAwayPlaces++;
        $this->rewindNrOfH2H();
        return true;
    }

    protected function incrementNrOfHomePlaces(): bool
    {
        if ($this->nrOfHomePlaces === $this->sidePlacesRange->getMax()) {
            return $this->incrementNrOfFields();
        }
        $this->nrOfHomePlaces++;
        $this->rewindNrOfAwayPlaces();
        return true;
    }

    protected function incrementNrOfFields(): bool
    {
        if ($this->nrOfFields === $this->fieldRange->getMax()) {
            return false;
        }
        $this->nrOfFields++;
        $this->rewindNrOfHomePlaces();
        return true;
    }
}
