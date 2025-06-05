<?php

namespace SportsPlanning;

use SportsHelpers\PlaceLocationInterface;

final class Place implements Resource, PlaceLocationInterface
{
    public const string SEPERATOR = '.';

    public function __construct(public readonly int $placeNr, public readonly int $pouleNr)
    {
        if( $placeNr < 1 ) {
            throw new \Exception('placeNr should be at least 1');
        }
    }

    public function getUniqueNumber(): int
    {
        return pow(2, $this->placeNr - 1);
    }

    #[\Override]
    public function getPlaceNr(): int {
        return $this->placeNr;
    }

    #[\Override]
    public function getPouleNr(): int {
        return $this->pouleNr;
    }

    #[\Override]
    public function getUniqueIndex(): string
    {
        return $this->pouleNr . self::SEPERATOR . $this->placeNr;
    }
}
