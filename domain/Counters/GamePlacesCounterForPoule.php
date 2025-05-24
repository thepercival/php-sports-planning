<?php

declare(strict_types=1);

namespace SportsPlanning\Counters;

use SportsHelpers\Counter;
use SportsPlanning\Poule;

readonly class GamePlacesCounterForPoule
{
    /**
     * @var Counter<Poule>
     */
    protected Counter $assignedGamesCounter;

    public function __construct(
        protected Poule $poule,
        protected int $nrOfAssignedGamePlaces = 0,
        int $count = 0)
    {
        if( $count < 0 ) {
            throw new \Exception('count must be at least 0');
        }
        $this->assignedGamesCounter = new CounterForPoule($poule, $count);
    }

    private function create( int $nrOfGames, int $nrOfPlacesAssigned): self {
        return new self($this->poule, $nrOfPlacesAssigned, $nrOfGames);
    }

    public function getPoule(): Poule
    {
        return $this->poule;
    }

    public function reset(): self
    {
        return $this->create( 0, 0 );
    }

    public function add(int $nrOfPlacesToAssign, int $nrOfGames = 1): self
    {
        return $this->create(
            $this->assignedGamesCounter->count() + $nrOfGames,
            $this->nrOfAssignedGamePlaces + $nrOfPlacesToAssign
        );
    }

    public function remove(int $nrOfGamePlacesToUnassign, int $nrOfGamesToRemove = 1): self
    {
        $newNrOfGames = $this->assignedGamesCounter->count() - $nrOfGamesToRemove;
        $newNrOfGamePlacesAssigned = $this->nrOfAssignedGamePlaces - $nrOfGamePlacesToUnassign;
        return $this->create(
            max($newNrOfGames, 0),
            max($newNrOfGamePlacesAssigned, 0)
        );
    }

    public function calculateNrOfAssignedGamePlaces(int|null $nrOfRefereePlacesPerGame = null): int
    {
        if( $nrOfRefereePlacesPerGame === null || $nrOfRefereePlacesPerGame < 0 ) {
            return $this->nrOfAssignedGamePlaces;
        }
        return $this->nrOfAssignedGamePlaces + ($nrOfRefereePlacesPerGame * $this->assignedGamesCounter->count() );
    }

    public function getNrOfGames(): int
    {
        return $this->assignedGamesCounter->count();
    }
}
