<?php

declare(strict_types=1);

namespace SportsPlanning;

final class Field implements Resource
{
    public const string SEPERATOR = '.';

    public function __construct(public readonly int $fieldNr, public readonly int $sportNr)
    {
    }

    #[\Override]
    public function getUniqueIndex(): string
    {
        return $this->sportNr . self::SEPERATOR . $this->fieldNr;
    }
}
