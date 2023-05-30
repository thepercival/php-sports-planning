<?php

use Doctrine\Common\Collections\Collection;
use SportsHelpers\Identifiable;
use SportsPlanning\Input;
use SportsPlanning\Poule;

/*class Category extends Identifiable
{
    protected int $number;
    //
     // @var Collection<int|string, Poule>
     //
    protected Collection $poules;

    public function __construct(protected Input $input, int|null $number = null)
    {
        $this->number = $number ?? count($input->getCategories()) + 1;
        if (!$input->getCategories()->contains($this)) {
            $input->getCategories()->add($this);
        }
    }

    public function getInput(): Input
    {
        return $this->input;
    }

    public function getNumber(): int
    {
        return $this->number;
    }
}*/