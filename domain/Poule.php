<?php

declare(strict_types=1);

namespace SportsPlanning;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;

class Poule extends Identifiable
{
    protected int $number;
    /**
     * @var Collection<int|string, Place>
     */
    protected Collection $places;

    public function __construct(protected Input $input/* Category $category*/)
    {
        $this->number = /*$category*/$input->getPoules()->count() + 1;
        /*$category*/$input->getPoules()->add($this);
        $this->places = new ArrayCollection();
    }

    public function getInput(): Input
    {
        return $this->input;
        // return $this->getCategory()->getInput();
    }

    /*public function getCategory(): Category
    {
        return $this->category;
    }*/

    public function getNumber(): int
    {
        return $this->number;
    }

    /**
     * @return Collection<int|string, Place>
     */
    public function getPlaces(): Collection
    {
        return $this->places;
    }

    /**
     * @return list<Place>
     */
    public function getPlaceList(): array
    {
        return array_values($this->places->toArray());
    }

    public function getPlace(int $number): Place
    {
        foreach ($this->getPlaces() as $place) {
            if ($place->getPlaceNr() === $number) {
                return $place;
            }
        }
        throw new Exception('de plek kan niet gevonden worden', E_ERROR);
    }
}
