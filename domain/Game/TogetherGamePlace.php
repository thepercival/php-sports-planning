<?php

namespace SportsPlanning\Game;

final readonly class TogetherGamePlace extends GamePlaceAbstract
{
    public function __construct(int $placeNr, public int $cycleNr)
    {
        parent::__construct($placeNr);
    }
}
