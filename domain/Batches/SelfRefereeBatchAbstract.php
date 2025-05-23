<?php

namespace SportsPlanning\Batches;

use SportsPlanning\Batches\SelfRefereeBatchOtherPoule as SelfRefereeOtherPoule;
use SportsPlanning\Batches\SelfRefereeBatchSamePoule as SelfRefereeSamePoule;
use SportsPlanning\Counters\GamePlacesCounterForPoule;
use SportsPlanning\Game\AgainstGame as AgainstGame;
use SportsPlanning\Game\TogetherGame as TogetherGame;
use SportsPlanning\Place;
use SportsPlanning\Poule;

abstract class SelfRefereeBatchAbstract
{
    protected SelfRefereeOtherPoule|SelfRefereeSamePoule|null $previous;
    protected SelfRefereeOtherPoule|SelfRefereeSamePoule|null $next = null;
    /**
     * @var array<string|Place>
     */
    protected array $placesAsRefereeMap = [];
    /**
     * @var array<int,GamePlacesCounterForPoule>
     */
    protected array $previousTotalPouleCounterMap = [];
    /**
     * @var array<int|string,int>
     */
    protected array $previousTotalNrOfForcedRefereePlacesMap = [];
    /**
     * @var array<int,GamePlacesCounterForPoule>
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

    public function addReferee(Place $placeReferee): void
    {
        $this->placesAsRefereeMap[(string)$placeReferee] = $placeReferee;
    }

    /**
     * @return array<string|Place>
     */
    public function getPlacesAsReferees(): array
    {
        return $this->placesAsRefereeMap;
    }

    public function removeReferee(Place $place): void
    {
        unset($this->placesAsRefereeMap[(string)$place]);
    }

    public function emptyPlacesAsReferees(): void
    {
        $this->placesAsRefereeMap = [];
    }

    public function isParticipatingAsReferee(Place $placeReferee): bool
    {
        return array_key_exists((string)$placeReferee, $this->placesAsRefereeMap);
    }

    /**
     * @param array<int,GamePlacesCounterForPoule> $previousTotalPouleCounterMap
     * @param array<int|string,int> $previousPreviousTotalNrOfForcedRefereePlacesMap
     */
    public function getCopyPreviousTotals(array &$previousTotalPouleCounterMap, array &$previousPreviousTotalNrOfForcedRefereePlacesMap): void
    {
        foreach ($this->previousTotalPouleCounterMap as $key => $gamePlacesCounterForPoule) {
            $copiedGamePlacesCounterForPoule = new GamePlacesCounterForPoule(
                $gamePlacesCounterForPoule->getPoule(),
                $gamePlacesCounterForPoule->calculateNrOfAssignedGamePlaces(),
                $gamePlacesCounterForPoule->getNrOfGames()
            );
            $previousTotalPouleCounterMap[$key] = $gamePlacesCounterForPoule;
        }
        foreach ($this->previousTotalNrOfForcedRefereePlacesMap as $key => $nrOfForcedRefereePlace) {
            $previousPreviousTotalNrOfForcedRefereePlacesMap[$key] = $nrOfForcedRefereePlace;
        }
    }

    /**
     * @param array<int,GamePlacesCounterForPoule> $previousPreviousTotalPouleCounterMap
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
     * @return array<int,GamePlacesCounterForPoule>
     */
    public function getTotalPouleCounters(): array
    {
        $previousTotalPouleCounterMap = [];
        foreach ($this->previousTotalPouleCounterMap as $key => $it) {
            $previousTotalPouleCounterMap[$key] = new GamePlacesCounterForPoule(
                $it->getPoule(),
                $it->calculateNrOfAssignedGamePlaces(),
                $it->getNrOfGames()
            );
        }
        $pouleCounterMap = [];
        foreach ($this->pouleCounterMap as $key => $it) {
            $pouleCounterMap[$key] = new GamePlacesCounterForPoule(
                $it->getPoule(),
                $it->calculateNrOfAssignedGamePlaces(),
                $it->getNrOfGames()
            );
        }
        return $this->addPouleCounters($previousTotalPouleCounterMap, $pouleCounterMap);
    }

    /**
     * @param array<int,GamePlacesCounterForPoule> $previousPreviousTotalPouleCounterMap
     * @param array<int,GamePlacesCounterForPoule> $previousBatchPouleCounterMap
     * @return array<int,GamePlacesCounterForPoule>
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
                $previousPreviousTotalPouleCounterMap[$previousPouleNr] =
                    $previousPreviousTotalPouleCounterMap[$previousPouleNr]->add(
                        $previousBatchPouleCounter->calculateNrOfAssignedGamePlaces(),
                        $previousBatchPouleCounter->getNrOfGames()
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
     * @return array<int,GamePlacesCounterForPoule>
     */
    public function getPouleCounters(): array
    {
        return $this->pouleCounterMap;
    }

    protected function getNrOfPlacesParticipatingHelper(Poule $poule, int $nrOfRefereePlacesPerGame): int
    {
        if (!isset($this->pouleCounterMap[$poule->getNumber()])) {
            return 0;
        }
        return $this->pouleCounterMap[$poule->getNumber()]->calculateNrOfAssignedGamePlaces($nrOfRefereePlacesPerGame);
    }

    public function add(TogetherGame|AgainstGame $game): void
    {
        $this->batch->add($game);
        $refereePlace = $game->getRefereePlace();
        if ($refereePlace !== null) {
            $this->addReferee($refereePlace);
        }

        $poule = $game->getPoule();
        if (!array_key_exists($poule->getNumber(), $this->pouleCounterMap)) {
            $this->pouleCounterMap[$poule->getNumber()] = new GamePlacesCounterForPoule($poule);
        }
        $this->pouleCounterMap[$poule->getNumber()] = $this->pouleCounterMap[$poule->getNumber()]->add(
            $game->getPlaces()->count()
        );
    }

    public function remove(TogetherGame|AgainstGame $game): void
    {
        $this->batch->remove($game);
        $refereePlace = $game->getRefereePlace();
        if ($refereePlace !== null) {
            $this->removeReferee($refereePlace);
        }

        $poule = $game->getPoule();
        $this->pouleCounterMap[$poule->getNumber()] = $this->pouleCounterMap[$poule->getNumber()]->remove(
            $game->getPlaces()->count()
        );
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

    /**
     * @param bool $includeRefereePlaces
     * @return list<Place>
     */
    public function getUnassignedPlaces(bool $includeRefereePlaces = false): array
    {
        $unassignedPlaces = $this->getBase()->getUnassignedPlaces();
        if ($includeRefereePlaces) {
            return array_values(
                array_filter($unassignedPlaces, function (Place $place): bool {
                    return !isset($this->placesAsRefereeMap[(string)$place]);
                })
            );
        }
        return $unassignedPlaces;
    }
}
