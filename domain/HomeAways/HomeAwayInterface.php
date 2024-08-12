<?php

declare(strict_types=1);

namespace SportsPlanning\HomeAways;

use SportsHelpers\Against\Side as AgainstSide;
use SportsPlanning\Combinations\DuoPlaceNr;
use SportsPlanning\Place;

interface HomeAwayInterface
{
    public function hasPlaceNr(int $placeNr, AgainstSide $side = null): bool;
    public function playsAgainst(int $placeNr, int $againstPlaceNr): bool;
    public function equals(OneVsOneHomeAway|OneVsTwoHomeAway|TwoVsTwoHomeAway $homeAway): bool;

    /**
     * @return list<int>
     */
    public function convertToPlaceNrs(): array;
}
