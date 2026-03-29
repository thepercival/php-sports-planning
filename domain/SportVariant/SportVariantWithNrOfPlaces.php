<?php

declare(strict_types=1);

namespace SportsPlanning\SportVariant;


trait SportVariantWithNrOfPlaces
{
    public function getNrOfPlaces(): int
    {
        return $this->nrOfPlaces;
    }
}
