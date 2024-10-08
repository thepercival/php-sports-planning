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
    protected Counter $gameCounter;

    public function __construct(protected Poule $poule, protected int $nrOfPlacesAssigned = 0, int $count = 0)
    {
        if( $count < 0 ) {
            throw new \Exception('count must be at least 0');
        }
        $this->gameCounter = new CounterForPoule($poule, $count);
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
            $this->gameCounter->count() + $nrOfGames,
            $this->nrOfPlacesAssigned + $nrOfPlacesToAssign
        );
    }

    public function remove(int $nrOfPlacesToUnassign, int $nrOfGamesToRemove = 1): self
    {
        return $this->create(
            $this->gameCounter->count() - $nrOfGamesToRemove,
            $this->nrOfPlacesAssigned - $nrOfPlacesToUnassign
        );
    }

    public function getNrOfPlacesAssigned(int|null $nrOfRefereePlacePerGame = null): int
    {
        if ($nrOfRefereePlacePerGame !== 0 && $nrOfRefereePlacePerGame > 0) {
            return $this->nrOfPlacesAssigned + ($nrOfRefereePlacePerGame * $this->gameCounter->count() );
        }
        return $this->nrOfPlacesAssigned;
    }

    public function getNrOfGames(): int
    {
        return $this->gameCounter->count();
    }
}
