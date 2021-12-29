<?php

declare(strict_types=1);

namespace SportsPlanning\Place;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output as OutputHelper;
use SportsPlanning\Place;

class Output extends OutputHelper
{
    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }

    public function getPlace(
        Place $place,
        string|null $suffix,
        bool $useColors
    ): string {
        $colorNumber = $useColors ? $place->getNumber() : -1;
        // $gamesInARowSuffix = $gamesInARow !== null ? '(' . $gamesInARow . ')' : '';
        $content = $place->getLocation() . ($suffix ?? '');
        return $this->getColored($colorNumber, $content);
    }
}
