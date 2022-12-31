<?php

declare(strict_types=1);

namespace SportsPlanning\SportVariant;

use SportsPlanning\Poule;

trait WithPoule
{
    public function getPoule(): Poule
    {
        return $this->poule;
    }
}
