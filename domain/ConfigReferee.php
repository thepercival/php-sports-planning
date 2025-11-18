<?php

namespace SportsPlanning;

use SportsHelpers\Identifiable;

readonly class ConfigReferee
{
    public function __construct(public int $refereeNr, public array $categoryNrs)
    {

    }
}
