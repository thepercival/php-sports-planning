<?php
declare(strict_types=1);

namespace SportsPlanning;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use SportsHelpers\Identifiable;

class Poule extends Identifiable
{
    protected int $number;

    /**
     * @var Collection<int|string, Place>
     */
    protected Collection $places;

    public function __construct(protected Input $input)
    {
        $this->number = $input->getPoules()->count() + 1;
        $input->getPoules()->add($this);
        $this->places = new ArrayCollection();
    }

    public function getInput(): Input
    {
        return $this->input;
    }

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
            if ($place->getNumber() === $number) {
                return $place;
            }
        }
        throw new Exception('de plek kan niet gevonden worden', E_ERROR);
    }
}
