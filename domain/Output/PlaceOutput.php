<?php

declare(strict_types=1);

namespace SportsPlanning\Output;

use Psr\Log\LoggerInterface;
use SportsHelpers\Output\OutputAbstract;
use SportsPlanning\Place as PlaceBase;

final class PlaceOutput extends OutputAbstract
{
    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    public function getPlace(
        PlaceBase $place,
        string|null $suffix
    ): string {
        $color = $this->convertNumberToColor($place->getPlaceNr());
        // $gamesInARowSuffix = $gamesInARow !== null ? '(' . $gamesInARow . ')' : '';
        return $this->getColoredString($color, ((string)$place) . ($suffix ?? ''));
    }
}
