<?php

namespace SportsPlanning\Input;

use Exception;
use SportsHelpers\GameMode;
use SportsHelpers\SportBase;
use SportsHelpers\SportConfig;
use SportsHelpers\Range;

class SportsIterator implements \Iterator
{
    protected Range $fieldRange;
    protected Range $gameAmountRange;
    protected Range $gamePlacesRange;

    protected int $gameMode;
    protected int $nrOfFields;
    protected int $nrOfGamePlaces;
    protected int $gameAmount;
    /**
     * @var SportConfig|null
     */
    protected $current;

    public function __construct(
        Range $fieldRange,
        Range $gameAmountRange
    ) {
        $this->fieldRange = $fieldRange;
        $this->gamePlacesRange = new Range(1, 2);
        $this->gameAmountRange = $gameAmountRange;
        $this->rewind();
    }

    protected function rewindGameMode()
    {
        $this->gameMode = GameMode::AGAINST;
        $this->rewindNrOfFields();
    }

    protected function rewindNrOfFields()
    {
        $this->nrOfFields = $this->fieldRange->min;
        $this->rewindNrOfGamePlaces();
    }

    protected function rewindNrOfGamePlaces()
    {
        $this->nrOfGamePlaces = $this->gamePlacesRange->min;
        if ($this->gameMode === GameMode::AGAINST && $this->nrOfGamePlaces < 2) {
            $this->nrOfGamePlaces = 2;
        }
        $this->rewindGameAmount();
    }

    protected function rewindGameAmount()
    {
        $this->gameAmount = $this->gameAmountRange->min;
    }

    public function current() : ?SportConfig
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
            new SportBase($this->gameMode, $this->nrOfGamePlaces),
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
        if ($this->gameAmount === $this->gameAmountRange->max) {
            return $this->incrementNrOfGamePlaces();
        }
        $this->gameAmount++;
        return true;
    }

    protected function incrementNrOfGamePlaces(): bool
    {
        if ($this->nrOfGamePlaces === $this->gamePlacesRange->max) {
            return $this->incrementNrOfFields();
        }
        $this->nrOfGamePlaces++;
        $this->rewindGameAmount();
        return true;
    }

    protected function incrementNrOfFields(): bool
    {
        if ($this->nrOfFields === $this->fieldRange->max) {
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
