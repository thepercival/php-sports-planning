<?php

declare(strict_types=1);

namespace SportsPlanning\SportVariant;

use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\Single;
use SportsHelpers\Sport\VariantWithPoule;
use SportsPlanning\Poule;

class WithPoule extends VariantWithPoule
{
    public function __construct(
        AllInOneGame|Single|AgainstH2h|AgainstGpp $sportVariant,
        protected Poule $poule
    ) {
        parent::__construct($sportVariant, count($poule->getPlaces()));
    }

    public function getPoule(): Poule
    {
        return $this->poule;
    }
}
