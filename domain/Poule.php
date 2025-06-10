<?php

declare(strict_types=1);

namespace SportsPlanning;

use Exception;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\AgainstGamePlace;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Game\TogetherGamePlace;

final class Poule
{

    /**
     * @var list<AgainstGame>
     */
    protected array $againstGames = [];
    /**
     * @psalm-var list<TogetherGame>
     */
    protected array $togetherGames = [];

    /**
     * @param int $pouleNr
     * @param list<Place> $places
     * @throws Exception
     */
    private function __construct(public readonly int $pouleNr, public readonly array $places)
    {
    }

    public static function fromNrOfPlaces(int $pouleNr, int $nrOfPlaces): self {
        $places = [];
        for ($placeNr = 1; $placeNr <= $nrOfPlaces; $placeNr++) {
            $places[] = new Place($placeNr, $pouleNr);
        }
        return new self($pouleNr, $places);
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

    /**
     * @return list<Place>
     */
    public function getPlaces(AgainstGame|TogetherGame $game): array
    {
        return array_map(function(AgainstGamePlace|TogetherGamePlace $gamePlace): Place {
            return $this->getPlace($gamePlace->placeNr);
        }, $game->getGamePlaces() );
    }

    public function removeGames(): void
    {
        $this->againstGames = [];
        $this->togetherGames = [];
    }
}
