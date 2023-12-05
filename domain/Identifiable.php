<?php

declare(strict_types=1);

namespace SportsPlanning;
class Identifiable
{
    protected int|string|null $id = null;

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function setId(int|string|null $id): void
    {
        $this->id = $id;
    }
}
