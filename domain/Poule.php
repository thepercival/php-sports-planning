<?php

declare(strict_types=1);

namespace SportsPlanning;

use Exception;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\TogetherGame;

class Poule
{
    /**
     * @var list<Place>
     */
    public readonly array $places;

    /**
     * @var list<AgainstGame>
     */
    protected array $againstGames = [];
    /**
     * @psalm-var list<TogetherGame>
     */
    protected array $togetherGames = [];

    public function __construct(public readonly int $pouleNr, int $nrOfPlaces /* Category $category*/)
    {
        $places = [];
        for ($placeNr = 1; $placeNr <= $nrOfPlaces; $placeNr++) {
            $places[] = new Place($placeNr, $pouleNr);
        }
        $this->places = $places;
    }

    /*public function getCategory(): Category
    {
        return $this->category;
    }*/


    public function getPlace(int $placeNr): Place
    {
        foreach ($this->places as $place) {
            if ($place->placeNr === $placeNr) {
                return $place;
            }
        }
        throw new Exception('de plek kan niet gevonden worden', E_ERROR);
    }

    /**
     * @return list<AgainstGame>
     */
    public function getAgainstGames(): array
    {
        return $this->againstGames;
    }

    /**
     * @return list<TogetherGame>
     */
    public function getTogetherGames(): array
    {
        return $this->togetherGames;
    }

    public function addGame(TogetherGame|AgainstGame $game): void
    {
        if( $game instanceof TogetherGame) {
            $this->togetherGames[] = $game;
        } else {
            $this->againstGames[] = $game;
        }
    }

    /**
     * @return list<AgainstGame|TogetherGame>
     */
    public function getGames(): array
    {
        return array_merge($this->againstGames, $this->togetherGames);
    }

    public function removeGames(): void
    {
        $this->againstGames = [];
        $this->togetherGames = [];
    }
}
