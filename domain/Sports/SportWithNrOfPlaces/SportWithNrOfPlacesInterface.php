<?php

namespace SportsPlanning\Sports\SportWithNrOfPlaces;


interface SportWithNrOfPlacesInterface
{
    public function calculateNrOfGames(int $nrOfCycles): int;
    public function calculateNrOfGamesPerCycle(): int;
    public function calculateNrOfGamesPerPlace(int $nrOfCycles): int;
    public function calculateNrOfGamesPerPlacePerCycle(): int;
}