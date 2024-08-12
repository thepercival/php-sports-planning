<?php

declare(strict_types=1);

namespace SportsPlanning\HomeAways;

readonly class HomeAwayAbstract implements \Stringable
{
    public function __construct(private string $index)
    {
    }

    public function getIndex(): string
    {
       return $this->index;
    }

    public function __toString(): string
    {
        return $this->index;
    }
}
