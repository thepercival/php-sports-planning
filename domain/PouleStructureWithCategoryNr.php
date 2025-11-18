<?php

namespace SportsPlanning;

use SportsHelpers\PouleStructures\PouleStructure;

final readonly class PouleStructureWithCategoryNr extends PouleStructure
{
    /**
     * @param int $categoryNr
     * @param list<int> $poules
     * @throws \Exception
     */
    public function __construct(public int $categoryNr,array $poules)
    {
        parent::__construct($poules);
    }
}