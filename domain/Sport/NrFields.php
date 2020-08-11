<?php

namespace SportsPlanning\Sport;

class NrFields
{
    /**
     * @var int
     */
    private $sportNr;
    /**
     * @var int
     */
    private $nrOfFields;
    /**
     * @var int
     */
    private $nrOfGamePlaces;

    public function __construct(int $sportNr, int $nrOfFields, int $nrOfGamePlaces)
    {
        $this->sportNr = $sportNr;
        $this->nrOfFields = $nrOfFields;
        $this->nrOfGamePlaces = $nrOfGamePlaces;
    }

    public function getSportNr(): int
    {
        return $this->sportNr;
    }

    public function getNrOfFields(): int
    {
        return $this->nrOfFields;
    }

    public function getNrOfGamePlaces(): int
    {
        return $this->nrOfGamePlaces;
    }
}
