<?php

declare(strict_types=1);

namespace SportsPlanning\SportVariant\WithPoule\Against;

use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\GamesPerPlace as AgainstGppWithNrOfPlaces;
use SportsPlanning\Poule;
use SportsPlanning\SportVariant\WithPoule as VariantWithPoule;

class GamesPerPlace extends AgainstGppWithNrOfPlaces
{
    use VariantWithPoule;

    public function __construct(protected Poule $poule, AgainstGpp $sportVariant) {
        parent::__construct(count($poule->getPlaces()), $sportVariant);
    }
}
