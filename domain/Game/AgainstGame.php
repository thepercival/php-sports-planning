<?php

declare(strict_types=1);

namespace SportsPlanning\Game;

use Exception;
use SportsHelpers\Against\AgainstSide;
use SportsPlanning\Field;
use SportsPlanning\Poule;

final class AgainstGame extends GameAbstract
{
    /**
     * @psalm-var list<AgainstGamePlace>
     */
    protected array $gamePlaces = [];

    private function __construct(
        int $pouleNr,
        Field $field,
        public int $cyclePartNr,
        public int $cycleNr
    ) {
        parent::__construct($pouleNr, $field);

    }

    public static function fromPoule(Poule $poule, Field $field, int $cyclePartNr, int $cycleNr ): self {
        $game = new self($poule->pouleNr, $field, $cyclePartNr, $cycleNr);
        $poule->addGame($game);
        return $game;
    }

    /**
     * @return list<AgainstGamePlace>
     */
    public function getGamePlaces(): array
    {
        return $this->gamePlaces;
    }

    /**
     * @param AgainstSide|null $side
     * @return list<AgainstGamePlace>
     */
    public function getSideGamePlaces(AgainstSide $side = null): array
    {
        if ($side === null) {
            return $this->gamePlaces;
        }
        return array_values( array_filter( $this->gamePlaces, function(AgainstGamePlace $gamePlace ) use ($side): bool {
            return $gamePlace->side === $side;
        }));
    }

    public function addGamePlace(AgainstSide $side, int $placeNr): AgainstGamePlace
    {
        $gamePlace = new AgainstGamePlace($side, $placeNr);
        $this->gamePlaces[] = $gamePlace;
        return $gamePlace;
    }

    public function isParticipating(int $placeNr, AgainstSide|null $side = null): bool
    {
        $gamePlaces = array_filter($this->getSideGamePlaces($side),
            function (AgainstGamePlace $gamePlace) use($placeNr): bool {
                return $gamePlace->placeNr === $placeNr;
            }
        );
        return count($gamePlaces) === 1;
    }

    public function getSide(int $placeNr): AgainstSide
    {
        if ($this->isParticipating($placeNr, AgainstSide::Home)) {
            return AgainstSide::Home;
        }
        if ($this->isParticipating($placeNr, AgainstSide::Away)) {
            return AgainstSide::Away;
        }
        throw new Exception('kan kant niet vinden', E_ERROR);
    }

    /**
     * @return list<int>
     */
    public function getPlaceNrs(): array
    {
        return array_map(function(AgainstGamePlace $gamePlace): int {
            return $gamePlace->placeNr;
        }, $this->getGamePlaces() );
    }
}
