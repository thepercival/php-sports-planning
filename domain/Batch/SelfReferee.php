<?php

namespace SportsPlanning\Batch;

use SportsPlanning\Batch;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;
use SportsPlanning\Place;
use SportsPlanning\Poule;
use SportsPlanning\PouleCounter;

abstract class SelfReferee
{
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
    protected $placesAsReferee = [];
    /**
     * @var array | PouleCounter[]
     */
    protected $previousTotalPouleCounterMap;
    /**
     * @var array | int []
     */
    protected $previousTotalNrOfForcedRefereePlacesMap;
    /**
     * @var array | PouleCounter []
     */
    protected $pouleCounterMap;

    public function __construct(protected Batch $batch, SelfReferee $previous = null)
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

    public function getBase(): ?Batch
    {
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

    /**
     * @param TogetherGame|AgainstGame $game
     * @param Place $placeReferee
     */
    public function addAsReferee($game, Place $placeReferee)
    {
        $game->setRefereePlace($placeReferee);
        $this->placesAsReferee[$placeReferee->getLocation()] = $placeReferee;
    }

    /**
     * @return array|Place[]
     */
    public function getPlacesAsReferees(): array
    {
        return $this->placesAsReferee;
    }

    /**
     * @param Place|null $place
     * @param TogetherGame|AgainstGame|null $game
     */
    public function removeAsReferee(Place $place = null, $game = null)
    {
        if ($place !== null) {
            unset($this->placesAsReferee[$place->getLocation()]);
        }
        if ($game !== null) {
            $game->setRefereePlace(null);
        }
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
     */
    protected function setPreviousTotals(
        array $previousPreviousTotalPouleCounterMap,
        array $previousPreviousTotalNrOfForcedRefereePlacesMap,
        self $previousBatch
    ) {
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
     * @return array|int[]
     */
    abstract protected function getForcedRefereePlacesMap(): array;

    /**
     * @param array|int[] $baseForcedRefereePlacesMap
     * @param array|int[] $forcedRefereePlacesMapToAdd
     * @return array|int[]
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
     * @return array|PouleCounter[]
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
     * @param array|PouleCounter[] $previousPreviousTotalPouleCounterMap
     * @param array|PouleCounter[] $previousBatchPouleCounterMap
     * @return array|PouleCounter[]
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
     * @return array|int[]
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
     * @return array|Place[]
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
     * @return array | PouleCounter[]
     */
    public function getPouleCounters(): array
    {
        return $this->pouleCounterMap;
    }

    /**
     * @param TogetherGame|AgainstGame $game
     */
    public function add($game)
    {
        $this->batch->add($game);

        $poule = $game->getPoule();
        if (!array_key_exists($poule->getNumber(), $this->pouleCounterMap)) {
            $this->pouleCounterMap[$poule->getNumber()] = new PouleCounter($poule);
        }
        $this->pouleCounterMap[$poule->getNumber()]->add($game->getPlaces()->count());
    }

    /**
     * @param TogetherGame|AgainstGame $game
     */
    public function remove($game)
    {
        $this->batch->remove($game);

        $poule = $game->getPoule();
        $this->pouleCounterMap[$poule->getNumber()]->remove($game->getPlaces()->count());
    }

    /**
     * @param Poule|null $poule
     * @return array|AgainstGame[]|TogetherGame[]
     */
    public function getGames(Poule $poule = null): array
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
