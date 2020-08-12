<?php

namespace SportsPlanning;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Structure
{
    /**
     * @var Collection | Poule[]
     */
    protected $poules;
    /**
     * @var int
     */
    protected $nrOfPlaces;

    public function __construct(Collection $poules)
    {
        $this->nrOfPlaces = 0;
        $this->poules = new ArrayCollection();
        foreach ($poules as $poule) {
            $this->addPoule($poule);
        }
    }

    protected function addPoule(Poule $poule)
    {
        $this->poules->add($poule);
        $this->nrOfPlaces += $poule->getPlaces()->count();
    }

    /**
     * @return Collection|Poule[]
     */
    public function getPoules(): Collection
    {
        return $this->poules;
    }

    /**
     * @return Poule|null
     */
    public function getPoule(int $number): ?Poule
    {
        foreach ($this->getPoules() as $poule) {
            if ($poule->getNumber() === $number) {
                return $poule;
            }
        }
        return null;
    }

    public function getNrOfPlaces(): int
    {
        return $this->nrOfPlaces;
    }
}
