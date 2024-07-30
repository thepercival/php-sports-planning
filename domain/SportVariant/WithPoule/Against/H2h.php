<?php

declare(strict_types=1);

namespace SportsPlanning\SportVariant\WithPoule\Against;

use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\WithNrOfPlaces\Against\H2h as AgainstH2hWithNrOfPlaces;
use SportsPlanning\Poule;
use SportsPlanning\SportVariant\WithPoule as VariantWithPoule;

class H2h extends AgainstH2hWithNrOfPlaces
{
    use VariantWithPoule;

    public function __construct(protected Poule $poule, AgainstH2h $sportVariant) {
        parent::__construct(count($poule->getPlaces()), $sportVariant);
    }
}
