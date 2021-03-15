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
     * @var array<Place>
     */
    protected array $placesAsReferee = [];
    /**
     * @var array<int|string,PouleCounter>
     */
    protected array $previousTotalPouleCounterMap;
    /**
     * @var array<int|string,int>
     */
    protected array $previousTotalNrOfForcedRefereePlacesMap;
    /**
     * @var array<int|string,PouleCounter>
     */
    protected $pouleCounterMap;

    public function __construct(protected Batch $batch, SelfRefereeOtherPoule|SelfRefereeSamePoule|null $previous = null)
    {
        $this->previous = $previous;
        $this->placesAsReferee = [];
        $this->pouleCounterMap = [];

        $this->previousTotalPouleCounterMap = [];
        $this->previousTotalNrOfForcedRefereePlacesMap = [];

        if ($previous !== null) {
            list($previousPreviousTotalPouleCounterMap, $previousPreviousTotalNrOfForcedRefereePlacesMap) = $previous->getCopyPreviousTotals(
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
        $this->placesAsReferee[$placeReferee->getLocation()] = $placeReferee;
    }

    /**
     * @return array<Place>
     */
    public function getPlacesAsReferees(): array
    {
        return $this->placesAsReferee;
    }

    public function removeAsReferee(Place|null $place = null, TogetherGame|AgainstGame|null $game = null): void
    {
        if ($place !== null) {
            unset($this->placesAsReferee[$place->getLocation()]);
        }
        if ($game !== null) {
            $game->setRefereePlace(null);
        }
    }

    public function emptyPlacesAsReferees(): void
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
    public function getCopyPreviousTotals(): array
    {
        $previousTotalPouleCounterMap = [];
        foreach ($this->previousTotalPouleCounterMap as $key => $pouleCounterMap) {
            $copiedPouleCounterMap = new PouleCounter(
                $pouleCounterMap->getPoule(),
                $pouleCounterMap->getNrOfPlacesAssigned()
            );
            $copiedPouleCounterMap->addNrOfGames($pouleCounterMap->getNrOfGames());
            $previousTotalPouleCounterMap[$key] = $copiedPouleCounterMap;
        }
        return array($previousTotalPouleCounterMap, $this->previousTotalNrOfForcedRefereePlacesMap);
    }

    /**
     * @param array| PouleCounter[] $previousPreviousTotalPouleCounterMap
     * @param array| int[] $previousPreviousTotalNrOfForcedRefereePlacesMap
     * @param self $previousBatch
     *
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
     * @return array<int>
     */
    abstract protected function getForcedRefereePlacesMap(): array;

    /**
     * @param array<int> $baseForcedRefereePlacesMap
     * @param array<int> $forcedRefereePlacesMapToAdd
     * @return array<int>
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
     * @return array<PouleCounter>
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
     * @param array<PouleCounter> $previousPreviousTotalPouleCounterMap
     * @param array<PouleCounter> $previousBatchPouleCounterMap
     * @return array<PouleCounter>
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
     * @return array<int>
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
     * @return array<Place>
     */
    public function getPlacesNotParticipating(Poule $poule): array
    {
        return $poule->getPlaces()->filter(
            function (Place $place): bool {
                return !$this->getBase()->isParticipating($place);
            }
        )->toArray();
    }

    /**
     * @return array<PouleCounter>
     */
    public function getPouleCounters(): array
    {
        return $this->pouleCounterMap;
    }

    /**
     * @param TogetherGame|AgainstGame $game
     * @return void
     */
    public function add($game): void
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
     * @return array<AgainstGame|TogetherGame>
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
