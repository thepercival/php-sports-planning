<?php

namespace SportsPlanning\Batches;

use SportsPlanning\Place;
use SportsPlanning\Poule;

class SelfRefereeBatchOtherPoule extends SelfRefereeBatchAbstract
{
    /**
     * @param list<Poule> $poules
     * @param Batch $batch
     * @param SelfRefereeBatchOtherPoule|null $previous
     */
    public function __construct(protected array $poules, Batch $batch, SelfRefereeBatchOtherPoule|null $previous = null)
    {
        parent::__construct($batch, $previous);

        $nextBatch = $this->getBase()->getNext();
        if ($nextBatch !== null) {
            $this->next = new SelfRefereeBatchOtherPoule($poules, $nextBatch, $this);
        }
    }

    public function getFirst(): SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule
    {
        $previous = $this->getPrevious();
        return $previous !== null ? $previous->getFirst() : $this;
    }

    public function getLeaf(): SelfRefereeBatchOtherPoule|SelfRefereeBatchSamePoule
    {
        $next = $this->getNext();
        return $next !== null ? $next->getLeaf() : $this;
    }

    public function createNext(): SelfRefereeBatchOtherPoule
    {
        $this->next = new SelfRefereeBatchOtherPoule($this->poules, $this->getBase()->createNext(), $this);
        return $this->next;
    }

    /**
     * @return array<string,int>
     */
    protected function getForcedRefereePlacesMap(): array
    {
        $forcedRefereePlacesMap = [];
        $otherPlacesMap = $this->getOtherPlacesMap();
        foreach ($this->getPouleCounters() as $pouleCounter) {
            $poule = $pouleCounter->getPoule();
            $nrOfGames = $this->pouleCounterMap[$poule->getNumber()]->getNrOfGames();
            $availableRefereePlaces = $this->getAvailableRefereePlaces(
                $otherPlacesMap[$poule->getNumber()]
            );
            if ($nrOfGames < count($availableRefereePlaces)) {
                continue;
            }
            foreach ($availableRefereePlaces as $availableRefereePlace) {
                $forcedRefereePlacesMap[(string)$availableRefereePlace] = 1;
            }
        }
        return $forcedRefereePlacesMap;
    }

    /**
     * @return array<int, list<Place>>
    */
    protected function getOtherPlacesMap(): array
    {
        $otherPoulePlacesMap = [];
        foreach ($this->poules as $poule) {
            $otherPoulePlacesMap[$poule->getNumber()] = [];
            $otherPoules = $this->poules;
            foreach ($otherPoules as $otherPoule) {
                if ($otherPoule === $poule) {
                    continue;
                }
                $otherPoulePlacesMap[$poule->getNumber()] = array_values(array_merge(
                    $otherPoulePlacesMap[$poule->getNumber()],
                    $otherPoule->getPlaces()->toArray()
                ));
            }
        }
        return $otherPoulePlacesMap;
    }

    /**
     * @param list<Place> $otherPoulePlaces
     * @return list<Place>
     */
    protected function getAvailableRefereePlaces(array $otherPoulePlaces): array
    {
        $baseBatch = $this->getBase();
        $availableRefereePlaces = [];
        foreach ($otherPoulePlaces as $otherPoulePlace) {
            if ($baseBatch->isParticipating($otherPoulePlace)) {
                continue;
            }
            $availableRefereePlaces[] = $otherPoulePlace;
        }
        return $availableRefereePlaces;
    }

    public function getNrOfPlacesParticipating(Poule $poule, int $nrOfRefereePlacePerGame): int
    {
        return $this->getNrOfPlacesParticipatingHelper($poule, $nrOfRefereePlacePerGame);
    }
}
