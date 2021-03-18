<?php

namespace SportsPlanning\Input;

use SportsHelpers\GameMode;
use SportsHelpers\SportBase;
use SportsHelpers\SportConfig;
use SportsHelpers\SportRange;

class SportsIterator implements \Iterator
{
    protected SportRange $fieldRange;
    protected SportRange $gameAmountRange;
    protected SportRange $gamePlacesRange;

    protected int $gameMode;
    protected int $nrOfFields;
    protected int $nrOfGamePlaces;
    protected int $gameAmount;
    /**
     * @var SportConfig|null
     */
    protected $current;

    public function __construct(
        SportRange $fieldRange,
        SportRange $gameAmountRange
    ) {
        $this->fieldRange = $fieldRange;
        $this->gamePlacesRange = new SportRange(1, 2);
        $this->gameAmountRange = $gameAmountRange;
        $this->rewind();
    }

    protected function rewindGameMode(): void
    {
        $this->gameMode = GameMode::AGAINST;
        $this->rewindNrOfFields();
        return;
    }

    protected function rewindNrOfFields(): void
    {
        $this->nrOfFields = $this->fieldRange->getMin();
        $this->rewindNrOfGamePlaces();
    }

    protected function rewindNrOfGamePlaces(): void
    {
        $this->nrOfGamePlaces = $this->gamePlacesRange->getMin();
        if ($this->gameMode === GameMode::AGAINST && $this->nrOfGamePlaces < 2) {
            $this->nrOfGamePlaces = 2;
        }
        $this->rewindGameAmount();
    }

    protected function rewindGameAmount(): void
    {
        $this->gameAmount = $this->gameAmountRange->getMin();
    }

    public function current() : SportConfig|null
    {
        return $this->current;
    }

    public function key() : string
    {
        return 'gamemode => ' . $this->gameMode . ', ' .
            'nrOfFields => ' . $this->nrOfFields . ', ' .
            'nrOfGamePlaces => ' . $this->nrOfGamePlaces . ', ' .
            'gameAmount => ' . $this->gameAmount;
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
        $this->current = $this->createSportConfig();
    }

    public function rewind()
    {
        $this->rewindGameMode();
        $this->current = $this->createSportConfig();
    }

    public function valid() : bool
    {
        return $this->current !== null;
    }

    protected function createSportConfig(): SportConfig
    {
        return new SportConfig(
            $this->gameMode,
            $this->nrOfGamePlaces,
            $this->nrOfFields,
            $this->gameAmount
        );
    }

    protected function incrementValue(): bool
    {
        return $this->incrementGameAmount();
    }

    protected function incrementGameAmount(): bool
    {
        if ($this->gameAmount === $this->gameAmountRange->getMax()) {
            return $this->incrementNrOfGamePlaces();
        }
        $this->gameAmount++;
        return true;
    }

    protected function incrementNrOfGamePlaces(): bool
    {
        if ($this->nrOfGamePlaces === $this->gamePlacesRange->getMax()) {
            return $this->incrementNrOfFields();
        }
        $this->nrOfGamePlaces++;
        $this->rewindGameAmount();
        return true;
    }

    protected function incrementNrOfFields(): bool
    {
        if ($this->nrOfFields === $this->fieldRange->getMax()) {
            return $this->incrementGameMode();
        }
        $this->nrOfFields++;
        $this->rewindNrOfGamePlaces();
        return true;
    }

    protected function incrementGameMode(): bool
    {
        if ($this->gameMode === GameMode::TOGETHER) {
            return false;
        }
        $this->gameMode = GameMode::TOGETHER;
        $this->rewindNrOfFields();
        return true;
    }
}
