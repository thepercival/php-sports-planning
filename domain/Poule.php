<?php

namespace SportsPlanning;

use \Doctrine\Common\Collections\ArrayCollection;
use Exception;
use SportsHelpers\Identifiable;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Game\Against as AgainstGame;

class Poule extends Identifiable
{
    /**
     * @var ArrayCollection<int|string,Place>
     */
    protected ArrayCollection $places;
    /**
     * @var ArrayCollection<int|string,AgainstGame>
     */
    protected ArrayCollection $againstGames;
    /**
     * @var ArrayCollection<int|string,TogetherGame>
     */
    protected ArrayCollection $togetherGames;

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
     * @return ArrayCollection<int|string,Place>
     */
    public function getPlaces(): ArrayCollection
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
     * @return ArrayCollection<int|string,AgainstGame>
     */
    public function getAgainstGames(): ArrayCollection
    {
        return $this->againstGames;
    }

    /**
     * @return ArrayCollection<int|string,TogetherGame>
     */
    public function getTogetherGames(): ArrayCollection
    {
        return $this->togetherGames;
    }
}
