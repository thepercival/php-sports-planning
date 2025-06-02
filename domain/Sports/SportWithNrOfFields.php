<?php

namespace SportsPlanning\Sports;

use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;

class SportWithNrOfFields
{
    public function __construct(
        public readonly AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport $sport,
        public readonly int $nrOfFields )
    {
    }
}