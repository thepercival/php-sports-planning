<?php

namespace SportsPlanning\Schedule;

use SportsHelpers\SportVariants\AgainstOneVsOne;
use SportsHelpers\SportVariants\AgainstOneVsTwo;
use SportsHelpers\SportVariants\AgainstTwoVsTwo;
use SportsHelpers\SportVariants\AllInOneGame;
use SportsHelpers\SportVariants\Single;

class SportVariantWithNr
{
    public function __construct(
        public readonly int $number,
        public readonly Single|AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|AllInOneGame $sportVariant){

    }
}