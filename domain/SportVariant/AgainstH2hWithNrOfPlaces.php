<?php

declare(strict_types=1);

namespace SportsPlanning\SportVariant;

use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\WithPoule\Against\H2h as AgainstH2hWithPoule;

final class AgainstH2hWithNrOfPlaces extends AgainstH2hWithPoule
{
    use SportVariantWithNrOfPlaces;

    public function __construct(protected int $nrOfPlaces, AgainstH2h $sportVariant) {
        parent::__construct($nrOfPlaces, $sportVariant);
    }
}
