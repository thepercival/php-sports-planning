<?php

declare(strict_types=1);

namespace SportsPlanning\Output;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output as OutputHelper;
use SportsHelpers\Output\Color;
use SportsPlanning\Place as PlaceBase;

class Place extends OutputHelper
{
    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct($logger);
    }

    public function getPlace(
        PlaceBase $place,
        string|null $suffix,
        bool $useColors
    ): string {
        $color = $this->convertNumberToColor($useColors ? $place->getNumber() : -1);
        // $gamesInARowSuffix = $gamesInARow !== null ? '(' . $gamesInARow . ')' : '';
        return Color::getColored($color, $place->getLocation() . ($suffix ?? ''));
    }
}