<?php

declare(strict_types=1);

namespace SportsPlanning\Place;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output as OutputHelper;
use SportsHelpers\Output\Color;
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
        $color = $this->convertNumberToColor($useColors ? $place->getNumber() : -1);
        // $gamesInARowSuffix = $gamesInARow !== null ? '(' . $gamesInARow . ')' : '';
        return Color::getColored($color, $place->getLocation() . ($suffix ?? ''));
    }
}
