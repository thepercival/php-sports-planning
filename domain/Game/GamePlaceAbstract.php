<?php
namespace SportsPlanning\Game;

readonly abstract class GamePlaceAbstract
{
    public function __construct(public int $placeNr)
    {
    }
}
