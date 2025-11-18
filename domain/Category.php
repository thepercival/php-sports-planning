<?php

declare(strict_types=1);

namespace SportsPlanning;

final readonly class Category
{
    private function __construct(
        public int $categoryNr,
        public array $poules
    )
    {
    }
}
