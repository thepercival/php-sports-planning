<?php
declare(strict_types=1);

namespace SportsPlanning;

use \Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Exception;
use SportsHelpers\Identifiable;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Game\Against as AgainstGame;

class Poule extends Identifiable
{
    protected int $number;

    /**
     * @phpstan-var ArrayCollection<int|string, Place>|PersistentCollection<int|string, Place>
     * @psalm-var ArrayCollection<int|string, Place>
     */
    protected ArrayCollection|PersistentCollection $places;

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
     * @phpstan-return ArrayCollection<int|string, Place>|PersistentCollection<int|string, Place>
     * @psalm-return ArrayCollection<int|string, Place>
     */
    public function getPlaces(): ArrayCollection|PersistentCollection
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
