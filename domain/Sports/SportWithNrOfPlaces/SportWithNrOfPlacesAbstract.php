<?php

namespace SportsPlanning\Sports\SportWithNrOfPlaces;

abstract class SportWithNrOfPlacesAbstract
{
    public function __construct(public readonly int $nrOfPlaces)
    {
    }
}