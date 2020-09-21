<?php

namespace SportsPlanning\Batch;

use SportsPlanning\Batch;
use SportsPlanning\Game;
use SportsPlanning\Place;
use SportsPlanning\Poule;
use SportsPlanning\PouleCounter;

class SelfReferee {

    /**
     * @var Batch
     */
    private $batch;
    /**
     * @var SelfReferee
     */
    protected $previous;
    /**
     * @var SelfReferee
     */
    protected $next;
    /**
     * @var array | Place[]
     */
    private $placesAsReferee = [];
    /**
     * @var array | PouleCounter[]
     */
    private $previousTotalPouleCounterMap;
    /**
     * @var array | int []
     */
    private $previousTotalNrOfForcedRefereePlacesMap;
    /**
     * @var array | PouleCounter []
     */
    private $pouleCounterMap;

    public function __construct(Batch $batch, SelfReferee $previous = null)
    {
        $this->previous = $previous;
        $this->placesAsReferee = [];
        $this->batch = $batch;
        $this->pouleCounterMap = [];

        $this->previousTotalPouleCounterMap = [];
        $this->previousTotalNrOfForcedRefereePlacesMap = [];

        if( $previous !== null ) {
            list ( $previousPreviousTotalPouleCounterMap, $previousPreviousTotalNrOfForcedRefereePlacesMap ) = $previous->getPreviousTotals();
            $this->setPreviousTotals( $previousPreviousTotalPouleCounterMap, $previousPreviousTotalNrOfForcedRefereePlacesMap, $previous );
        }

        if( $this->getBase()->hasNext() ) {
            $this->next = new SelfReferee($this->getBase()->getNext(), $this);
        }
    }

    public function getBase(): ?Batch {
        return $this->batch;
    }

    public function hasNext(): bool
    {
        return $this->next !== null;
    }

    /**
     * @return SelfReferee
     */
    public function getNext(): SelfReferee
    {
        return $this->next;
    }

    public function createNext(): SelfReferee
    {
        $this->next = new SelfReferee($this->getBase()->createNext(), $this);
        return $this->next;
    }

    public function hasPrevious(): bool
    {
        return $this->previous !== null;
    }

    public function getPrevious(): SelfReferee
    {
        return $this->previous;
    }

    public function getFirst(): SelfReferee
    {
        return $this->hasPrevious() ? $this->previous->getFirst() : $this;
    }

    public function getLeaf(): SelfReferee
    {
        return $this->hasNext() ? $this->next->getLeaf() : $this;
    }

    public function addAsReferee(Place $placeReferee)
    {
        $this->placesAsReferee[$placeReferee->getLocation()] = $placeReferee;
    }

    /**
     * @return array|Place[]
     */
    public function getPlacesAsReferees(): array
    {
        return $this->placesAsReferee;
    }

    public function removeAsReferee(Place $placeReferee)
    {
        unset($this->placesAsReferee[$placeReferee->getLocation()]);
    }

    public function emptyPlacesAsReferees()
    {
        $this->placesAsReferee = [];
    }

    public function isParticipatingAsReferee(Place $placeReferee): bool
    {
        return array_key_exists($placeReferee->getLocation(), $this->placesAsReferee);
    }

    /**
     * @return array
     */
    public function getPreviousTotals(): array
    {
        return array( $this->previousTotalPouleCounterMap, $this->previousTotalNrOfForcedRefereePlacesMap );
    }

    /**
     * @param array| PouleCounter[] $previousPreviousTotalPouleCounterMap
     * @param array| int[] $previousPreviousTotalNrOfForcedRefereePlacesMap
     * @param self $previousBatch
     */
    protected function setPreviousTotals(
        array $previousPreviousTotalPouleCounterMap,
        array $previousPreviousTotalNrOfForcedRefereePlacesMap,
        self $previousBatch )
    {
        $previousBatchPouleCounterMap = $previousBatch->getPouleCounters();
        $this->previousTotalPouleCounterMap = $this->addPouleCounters( $previousPreviousTotalPouleCounterMap, $previousBatchPouleCounterMap );

        foreach( $previousBatch->getPouleCounters() as $pouleCounter ) {
            $poule = $pouleCounter->getPoule();

            if( $previousBatchPouleCounterMap[$poule->getNumber()]->getNrOfPlacesAssigned( true ) !== $poule->getPlaces()->count() ) {
                continue;
            }
            $forcedRefereePlaces = $previousBatch->getPlacesNotParticipating( $poule );
            foreach( $forcedRefereePlaces as $forcedRefereePlace ) {
                if( !array_key_exists( $forcedRefereePlace->getLocation(), $previousPreviousTotalNrOfForcedRefereePlacesMap ) ) {
                    $previousPreviousTotalNrOfForcedRefereePlacesMap[$forcedRefereePlace->getLocation()] = 0;
                }
                $previousPreviousTotalNrOfForcedRefereePlacesMap[$forcedRefereePlace->getLocation()]++;
            }
        }
        $this->previousTotalNrOfForcedRefereePlacesMap = $previousPreviousTotalNrOfForcedRefereePlacesMap;
    }

    /**
     * @return array|PouleCounter[]
     */
    public function getTotalPouleCounters(): array {
        $previousTotalPouleCounterMap = [];
        foreach( $this->previousTotalPouleCounterMap as $key => $it ) {
            $previousTotalPouleCounterMap[$key] = new PouleCounter( $it->getPoule(), $it->getNrOfPlacesAssigned() );
            $previousTotalPouleCounterMap[$key]->addNrOfGames( $it->getNrOfGames() );
        }
        $pouleCounterMap = [];
        foreach( $this->pouleCounterMap as $key => $it ) {
            $pouleCounterMap[$key] = new PouleCounter( $it->getPoule(), $it->getNrOfPlacesAssigned() );
            $pouleCounterMap[$key]->addNrOfGames( $it->getNrOfGames() );
        }
        return $this->addPouleCounters( $previousTotalPouleCounterMap, $pouleCounterMap );
    }

    /**
     * @param array|PouleCounter[] $previousPreviousTotalPouleCounterMap
     * @param array|PouleCounter[] $previousBatchPouleCounterMap
     * @return array|PouleCounter[]
     */
    protected function addPouleCounters( array $previousPreviousTotalPouleCounterMap, array $previousBatchPouleCounterMap ): array {
        foreach( $previousBatchPouleCounterMap as $previousBatchPouleCounter ) {
            $previousPouleNr = $previousBatchPouleCounter->getPoule()->getNumber();
            if( !array_key_exists( $previousPouleNr, $previousPreviousTotalPouleCounterMap ) ) {
                $previousPreviousTotalPouleCounterMap[$previousPouleNr] = $previousBatchPouleCounter;
            } else {
                $previousPreviousTotalPouleCounterMap[$previousPouleNr]->addNrOfGames( $previousBatchPouleCounter->getNrOfGames() );
                $previousPreviousTotalPouleCounterMap[$previousPouleNr]->addNrOfAssignedPlaces( $previousBatchPouleCounter->getNrOfPlacesAssigned() );
            }
        }
        return $previousPreviousTotalPouleCounterMap;
    }

    /**
     * @return array|int[]
     */
    public function getTotalNrOfForcedRefereePlaces(): array {
        return $this->previousTotalNrOfForcedRefereePlacesMap;
    }

    /**
     * @param Poule $poule
     * @return array|Place[]
     */
    public function getPlacesNotParticipating( Poule $poule ): array {
        return $poule->getPlaces()->filter( function( Place $place ): bool {
            return !$this->getBase()->isParticipating($place);
        })->toArray();
    }

    /**
     * @return array | PouleCounter[]
     */
    public function getPouleCounters(): array
    {
       return $this->pouleCounterMap;
    }

    public function add(Game $game)
    {
        $this->batch->add($game);

        $poule = $game->getPoule();
        if( !array_key_exists( $poule->getNumber(), $this->pouleCounterMap ) ) {
            $this->pouleCounterMap[$poule->getNumber()] = new PouleCounter( $poule );
        }
        $this->pouleCounterMap[$poule->getNumber()]->add( $game->getPlaces()->count() );
    }

    public function remove(Game $game)
    {
        $this->batch->remove($game);

        $poule = $game->getPoule();
        $this->pouleCounterMap[$poule->getNumber()]->remove( $game->getPlaces()->count() );
    }

    /**
     * @param Poule|null $poule
     * @return array|Game[]
     */
    public function getGames( Poule $poule = null ): array
    {
        return $this->getBase()->getGames( $poule );
    }

    public function isParticipating(Place $place): bool
    {
        return $this->getBase()->isParticipating( $place );
    }

    public function getNumber(): int
    {
        return $this->getBase()->getNumber();
    }

    public function getGamesInARow(Place $place): int {
        return $this->getBase()->getGamesInARow($place);
    }
}
