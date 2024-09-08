<?php

namespace SportsPlanning\Schedule;



use SportsHelpers\SportVariants\AgainstGpp;
use SportsHelpers\SportVariants\AgainstH2h;
use SportsHelpers\SportVariants\AllInOneGame;
use SportsHelpers\SportVariants\Single;

class SportVariantWithNr
{
    public function __construct(
        public readonly int $number,
        public readonly Single|AgainstH2h|AgainstGpp|AllInOneGame $sportVariant){

    }
}