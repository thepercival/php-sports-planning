<?php

declare(strict_types=1);

namespace SportsPlanning\Game;

use SportsHelpers\Against\AgainstSide;

readonly class AgainstGamePlace extends GamePlaceAbstract
{
    public function __construct(public AgainstSide $side, int $placeNr )
    {
        parent::__construct($placeNr);
    }
}
