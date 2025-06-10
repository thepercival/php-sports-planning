<?php

namespace SportsPlanning\Batches;

use SportsPlanning\Batches\SelfRefereeBatchOtherPoules;
use SportsPlanning\Batches\SelfRefereeBatchSamePoule;
use SportsPlanning\Counters\GamePlacesCounterForPoule;
use SportsPlanning\Game\AgainstGame;
use SportsPlanning\Game\TogetherGame;
use SportsPlanning\Place;
use SportsPlanning\Poule;

abstract class SelfRefereeBatchAbstract
{
    protected SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule|null $previous;
    protected SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule|null $next = null;
    /**
     * @var array<string,string>
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

    public function __construct(protected Batch $batch, SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule|null $previous = null)
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

    public function getNext(): SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule|null
    {
        return $this->next;
    }

    public function hasPrevious(): bool
    {
        return $this->previous !== null;
    }

    public function getPrevious(): SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule|null
    {
        return $this->previous;
    }

    public function addRefereeUniqueIndex(string $refereePlaceUniqueIndex): void
    {
        $this->placesAsRefereeMap[$refereePlaceUniqueIndex] = $refereePlaceUniqueIndex;
    }

    /**
     * @return array<string|Place>
     */
    public function getPlacesAsReferees(): array
    {
        return $this->placesAsRefereeMap;
    }

    public function removeReferee(string $refereePlaceUniqueIndex): void
    {
        unset($this->placesAsRefereeMap[$refereePlaceUniqueIndex]);
    }

    public function emptyPlacesAsReferees(): void
    {
        $this->placesAsRefereeMap = [];
    }

    public function isParticipatingAsReferee(Place $placeReferee): bool
    {
        return array_key_exists($placeReferee->getUniqueIndex(), $this->placesAsRefereeMap);
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
            $previousPouleNr = $previousBatchPouleCounter->getPoule()->pouleNr;
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
        return array_values( array_filter($poule->places,
            function (Place $place): bool {
                return !$this->getBase()->isParticipating($place);
            }
        ));
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
        if (!isset($this->pouleCounterMap[$poule->pouleNr])) {
            return 0;
        }
        return $this->pouleCounterMap[$poule->pouleNr]->calculateNrOfAssignedGamePlaces($nrOfRefereePlacesPerGame);
    }

    public function add(TogetherGame|AgainstGame $game): void
    {
        $this->batch->add($game);
        $refereePlaceUniqueIndex = $game->getRefereePlaceUniqueIndex();
        if ($refereePlaceUniqueIndex !== null) {
            $this->addRefereeUniqueIndex($refereePlaceUniqueIndex);
        }

        $poule = $this->batch->getPoule($game->pouleNr);
        $pouleNr = $poule->pouleNr;
        if (!array_key_exists($pouleNr, $this->pouleCounterMap)) {
            $this->pouleCounterMap[$pouleNr] = new GamePlacesCounterForPoule($poule);
        }
        $this->pouleCounterMap[$pouleNr] = $this->pouleCounterMap[$pouleNr]->add(count($game->getGamePlaces()));
    }

    public function remove(TogetherGame|AgainstGame $game): void
    {
        $this->batch->remove($game);
        $refereePlaceUniqueIndex = $game->getRefereePlaceUniqueIndex();
        if ($refereePlaceUniqueIndex !== null) {
            $this->removeReferee($refereePlaceUniqueIndex);
        }

        $poule = $this->batch->getPoule($game->pouleNr);
        $this->pouleCounterMap[$poule->pouleNr] = $this->pouleCounterMap[$poule->pouleNr]->remove(
            count($game->getGamePlaces())
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

    public function getPoule(int $pouleNr): Poule
    {
        return $this->getBase()->getPoule($pouleNr);
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
                    return !isset($this->placesAsRefereeMap[$place->getUniqueIndex()]);
                })
            );
        }
        return $unassignedPlaces;
    }
}
