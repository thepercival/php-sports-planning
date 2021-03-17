<?php

namespace SportsPlanning\Batch;

use SportsPlanning\Batch;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeOtherPoule;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeSamePoule;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Place;
use SportsPlanning\Poule;
use SportsPlanning\PouleCounter;

abstract class SelfReferee
{
    protected SelfRefereeOtherPoule|SelfRefereeSamePoule|null $previous;
    protected SelfRefereeOtherPoule|SelfRefereeSamePoule|null $next = null;
    /**
     * @var array<string|Place>
     */
    protected array $placesAsRefereeMap = [];
    /**
     * @var array<int,PouleCounter>
     */
    protected array $previousTotalPouleCounterMap = [];
    /**
     * @var array<int|string,int>
     */
    protected array $previousTotalNrOfForcedRefereePlacesMap = [];
    /**
     * @var array<int,PouleCounter>
     */
    protected array $pouleCounterMap = [];

    public function __construct(protected Batch $batch, SelfRefereeOtherPoule|SelfRefereeSamePoule|null $previous = null)
    {
        $this->previous = $previous;

        if ($previous !== null) {
            $previousPreviousTotalPouleCounterMap = [];
            $previousPreviousTotalNrOfForcedRefereePlacesMap = [];
            $previous->getCopyPreviousTotals(
                $previousPreviousTotalPouleCounterMap,
                $previousPreviousTotalNrOfForcedRefereePlacesMap
            );
            $this->setPreviousTotals(
                $previousPreviousTotalPouleCounterMap,
                $previousPreviousTotalNrOfForcedRefereePlacesMap,
                $previous
            );
        }
    }

    public function getBase(): Batch
    {
        return $this->batch;
    }

    public function hasNext(): bool
    {
        return $this->next !== null;
    }

    public function getNext(): SelfRefereeOtherPoule|SelfRefereeSamePoule|null
    {
        return $this->next;
    }

    public function hasPrevious(): bool
    {
        return $this->previous !== null;
    }

    public function getPrevious(): SelfRefereeOtherPoule|SelfRefereeSamePoule|null
    {
        return $this->previous;
    }

    public function addAsReferee(TogetherGame|AgainstGame$game, Place $placeReferee): void
    {
        $game->setRefereePlace($placeReferee);
        $this->placesAsRefereeMap[$placeReferee->getLocation()] = $placeReferee;
    }

    /**
     * @return array<string|Place>
     */
    public function getPlacesAsReferees(): array
    {
        return $this->placesAsRefereeMap;
    }

    public function removeAsReferee(Place|null $place = null, TogetherGame|AgainstGame|null $game = null): void
    {
        if ($place !== null) {
            unset($this->placesAsRefereeMap[$place->getLocation()]);
        }
        if ($game !== null) {
            $game->setRefereePlace(null);
        }
    }

    public function emptyPlacesAsReferees(): void
    {
        $this->placesAsRefereeMap = [];
    }

    public function isParticipatingAsReferee(Place $placeReferee): bool
    {
        return array_key_exists($placeReferee->getLocation(), $this->placesAsRefereeMap);
    }

    /**
     * @param array<int,PouleCounter> $previousTotalPouleCounterMap
     * @param array<int|string,int> $previousPreviousTotalNrOfForcedRefereePlacesMap
     */
    public function getCopyPreviousTotals(array &$previousTotalPouleCounterMap, array &$previousPreviousTotalNrOfForcedRefereePlacesMap): void
    {
        foreach ($this->previousTotalPouleCounterMap as $key => $pouleCounterMap) {
            $copiedPouleCounterMap = new PouleCounter(
                $pouleCounterMap->getPoule(),
                $pouleCounterMap->getNrOfPlacesAssigned()
            );
            $copiedPouleCounterMap->addNrOfGames($pouleCounterMap->getNrOfGames());
            $previousTotalPouleCounterMap[$key] = $copiedPouleCounterMap;
        }
        foreach ($this->previousTotalNrOfForcedRefereePlacesMap as $key => $nrOfForcedRefereePlace) {
            $previousPreviousTotalNrOfForcedRefereePlacesMap[$key] = $nrOfForcedRefereePlace;
        }
    }

    /**
     * @param array<int,PouleCounter> $previousPreviousTotalPouleCounterMap
     * @param array<string|int,int> $previousPreviousTotalNrOfForcedRefereePlacesMap
     * @param self $previousBatch
     * @return void
     */
    protected function setPreviousTotals(
        array $previousPreviousTotalPouleCounterMap,
        array $previousPreviousTotalNrOfForcedRefereePlacesMap,
        self $previousBatch
    ): void {
        $previousBatchPouleCounterMap = $previousBatch->getPouleCounters();
        $this->previousTotalPouleCounterMap = $this->addPouleCounters(
            $previousPreviousTotalPouleCounterMap,
            $previousBatchPouleCounterMap
        );
        $previousBatchForcedRefereePlacesMap = $previousBatch->getForcedRefereePlacesMap();
        $this->previousTotalNrOfForcedRefereePlacesMap = $this->addForcedRefereePlacesMaps(
            $previousPreviousTotalNrOfForcedRefereePlacesMap,
            $previousBatchForcedRefereePlacesMap
        );
    }

    /**
     * @return array<string,int>
     */
    abstract protected function getForcedRefereePlacesMap(): array;

    /**
     * @param array<string|int,int> $baseForcedRefereePlacesMap
     * @param array<string|int,int> $forcedRefereePlacesMapToAdd
     * @return array<string|int,int>
     */
    protected function addForcedRefereePlacesMaps(
        array $baseForcedRefereePlacesMap,
        array $forcedRefereePlacesMapToAdd
    ): array {
        foreach ($forcedRefereePlacesMapToAdd as $placeLocation => $amount) {
            if (!array_key_exists(
                $placeLocation,
                $baseForcedRefereePlacesMap
            )) {
                $baseForcedRefereePlacesMap[$placeLocation] = 0;
            }
            $baseForcedRefereePlacesMap[$placeLocation] += $amount;
        }
        return $baseForcedRefereePlacesMap;
    }

    /**
     * @return array<int,PouleCounter>
     */
    public function getTotalPouleCounters(): array
    {
        $previousTotalPouleCounterMap = [];
        foreach ($this->previousTotalPouleCounterMap as $key => $it) {
            $previousTotalPouleCounterMap[$key] = new PouleCounter($it->getPoule(), $it->getNrOfPlacesAssigned());
            $previousTotalPouleCounterMap[$key]->addNrOfGames($it->getNrOfGames());
        }
        $pouleCounterMap = [];
        foreach ($this->pouleCounterMap as $key => $it) {
            $pouleCounterMap[$key] = new PouleCounter($it->getPoule(), $it->getNrOfPlacesAssigned());
            $pouleCounterMap[$key]->addNrOfGames($it->getNrOfGames());
        }
        return $this->addPouleCounters($previousTotalPouleCounterMap, $pouleCounterMap);
    }

    /**
     * @param array<int,PouleCounter> $previousPreviousTotalPouleCounterMap
     * @param array<int,PouleCounter> $previousBatchPouleCounterMap
     * @return array<int,PouleCounter>
     */
    protected function addPouleCounters(
        array $previousPreviousTotalPouleCounterMap,
        array $previousBatchPouleCounterMap
    ): array {
        foreach ($previousBatchPouleCounterMap as $previousBatchPouleCounter) {
            $previousPouleNr = $previousBatchPouleCounter->getPoule()->getNumber();
            if (!array_key_exists($previousPouleNr, $previousPreviousTotalPouleCounterMap)) {
                $previousPreviousTotalPouleCounterMap[$previousPouleNr] = $previousBatchPouleCounter;
            } else {
                $previousPreviousTotalPouleCounterMap[$previousPouleNr]->addNrOfGames(
                    $previousBatchPouleCounter->getNrOfGames()
                );
                $previousPreviousTotalPouleCounterMap[$previousPouleNr]->addNrOfAssignedPlaces(
                    $previousBatchPouleCounter->getNrOfPlacesAssigned()
                );
            }
        }
        return $previousPreviousTotalPouleCounterMap;
    }

    /**
     * @return array<string|int,int>
     */
    public function getTotalNrOfForcedRefereePlaces(): array
    {
        return $this->addForcedRefereePlacesMaps(
            $this->previousTotalNrOfForcedRefereePlacesMap,
            $this->getForcedRefereePlacesMap()
        );
    }

    /**
     * @param Poule $poule
     * @return list<Place>
     */
    public function getPlacesNotParticipating(Poule $poule): array
    {
        return array_values($poule->getPlaces()->filter(
            function (Place $place): bool {
                return !$this->getBase()->isParticipating($place);
            }
        )->toArray());
    }

    /**
     * @return array<int,PouleCounter>
     */
    public function getPouleCounters(): array
    {
        return $this->pouleCounterMap;
    }

    public function add(TogetherGame|AgainstGame $game): void
    {
        $this->batch->add($game);

        $poule = $game->getPoule();
        if (!array_key_exists($poule->getNumber(), $this->pouleCounterMap)) {
            $this->pouleCounterMap[$poule->getNumber()] = new PouleCounter($poule);
        }
        $this->pouleCounterMap[$poule->getNumber()]->add($game->getPlaces()->count());
    }

    public function remove(TogetherGame|AgainstGame $game): void
    {
        $this->batch->remove($game);

        $poule = $game->getPoule();
        $this->pouleCounterMap[$poule->getNumber()]->remove($game->getPlaces()->count());
    }

    /**
     * @param Poule|null $poule
     * @return list<AgainstGame|TogetherGame>
     */
    public function getGames(Poule|null $poule = null): array
    {
        return $this->getBase()->getGames($poule);
    }

    public function isParticipating(Place $place): bool
    {
        return $this->getBase()->isParticipating($place);
    }

    public function getNumber(): int
    {
        return $this->getBase()->getNumber();
    }

    public function getGamesInARow(Place $place): int
    {
        return $this->getBase()->getGamesInARow($place);
    }
}
