<?php

declare(strict_types=1);

namespace SportsPlanning\SportVariant\WithPoule\Against;

use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\WithPoule\Against\GamesPerPlace as AgainstGppWithPoule;
use SportsPlanning\Poule;
use SportsPlanning\SportVariant\WithPoule as VariantWithPoule;

/**
 * @api
 */
class GamesPerPlace extends AgainstGppWithPoule
{
    use VariantWithPoule;

    public function __construct(protected Poule $poule, AgainstGpp $sportVariant) {
        parent::__construct(count($poule->getPlaces()), $sportVariant);
    }
}
