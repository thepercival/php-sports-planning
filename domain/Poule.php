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
    /**
     * @phpstan-var ArrayCollection<int|string, Place>|PersistentCollection<int|string, Place>
     * @psalm-var ArrayCollection<int|string, Place>
     */
    protected ArrayCollection|PersistentCollection $places;
    /**
     * @phpstan-var ArrayCollection<int|string, AgainstGame>|PersistentCollection<int|string, AgainstGame>
     * @psalm-var ArrayCollection<int|string, AgainstGame>
     */
    protected ArrayCollection|PersistentCollection $againstGames;
    /**
     * @phpstan-var ArrayCollection<int|string, TogetherGame>|PersistentCollection<int|string, TogetherGame>
     * @psalm-var ArrayCollection<int|string, TogetherGame>
     */
    protected ArrayCollection|PersistentCollection $togetherGames;

    public function __construct(protected Planning $planning, protected int $number, int $nrOfPlaces)
    {
        $this->places = new ArrayCollection();
        for ($placeNr = 1 ; $placeNr <= $nrOfPlaces ; $placeNr++) {
            $this->places->add(new Place($this, $placeNr));
        }
        $this->againstGames = new ArrayCollection();
        $this->togetherGames = new ArrayCollection();
    }

    public function getPlanning(): Planning
    {
        return $this->planning;
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

    public function getPlace(int $number): Place
    {
        foreach ($this->getPlaces() as $place) {
            if ($place->getNumber() === $number) {
                return $place;
            }
        }
        throw new Exception('de plek kan niet gevonden worden', E_ERROR);
    }

    /**
     * @return list<AgainstGame|TogetherGame>
     */
    public function getGames(): array
    {
        return array_values(array_merge($this->getAgainstGames()->toArray(), $this->getTogetherGames()->toArray()));
    }

    /**
     * @phpstan-return ArrayCollection<int|string, AgainstGame>|PersistentCollection<int|string, AgainstGame>
     * @psalm-return ArrayCollection<int|string, AgainstGame>
     */
    public function getAgainstGames(): ArrayCollection|PersistentCollection
    {
        return $this->againstGames;
    }

    /**
     * @phpstan-return ArrayCollection<int|string, TogetherGame>|PersistentCollection<int|string, TogetherGame>
     * @psalm-return ArrayCollection<int|string, TogetherGame>
     */
    public function getTogetherGames(): ArrayCollection|PersistentCollection
    {
        return $this->togetherGames;
    }
}
