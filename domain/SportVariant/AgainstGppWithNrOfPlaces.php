<?php

declare(strict_types=1);

namespace SportsPlanning\SportVariant;

use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;

/**
 * @api
 */
class AgainstGppWithNrOfPlaces extends AgainstGppWithPoule
{
    use SportVariantWithNrOfPlaces;

    public function __construct(protected int $nrOfPlaces, AgainstGpp $sportVariant) {
        parent::__construct($nrOfPlaces, $sportVariant);
    }
}
