<?php

namespace SportsPlanning\Calculators;

use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;

class NrOfGamesPerPlaceCalculator
{
    public function calculate(
        int $nrOfPlaces,
        int $nrOfCycles,
        TogetherSport|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo $sport
    ): int
    {
        throw new \Exception('implmenet');
    }
}