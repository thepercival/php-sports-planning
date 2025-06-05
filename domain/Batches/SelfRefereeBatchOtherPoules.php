<?php

namespace SportsPlanning\Batches;

use SportsPlanning\Place;
use SportsPlanning\Poule;

final class SelfRefereeBatchOtherPoules extends SelfRefereeBatchAbstract
{
    /**
     * @param list<Poule> $poules
     * @param Batch $batch
     * @param SelfRefereeBatchOtherPoules|null $previous
     */
    public function __construct(protected array $poules, Batch $batch, SelfRefereeBatchOtherPoules|null $previous = null)
    {
        parent::__construct($batch, $previous);

        $nextBatch = $this->getBase()->getNext();
        if ($nextBatch !== null) {
            $this->next = new SelfRefereeBatchOtherPoules($poules, $nextBatch, $this);
        }
    }

    public function getFirst(): SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule
    {
        $previous = $this->getPrevious();
        return $previous !== null ? $previous->getFirst() : $this;
    }

    public function getLeaf(): SelfRefereeBatchOtherPoules|SelfRefereeBatchSamePoule
    {
        $next = $this->getNext();
        return $next !== null ? $next->getLeaf() : $this;
    }

    public function createNext(): SelfRefereeBatchOtherPoules
    {
        $this->next = new SelfRefereeBatchOtherPoules($this->poules, $this->getBase()->createNext(), $this);
        return $this->next;
    }

    /**
     * @return array<string,int>
     */
    #[\Override]
    protected function getForcedRefereePlacesMap(): array
    {
        $forcedRefereePlacesMap = [];
        $otherPlacesMap = $this->getOtherPlacesMap();
        foreach ($this->getPouleCounters() as $pouleCounter) {
            $poule = $pouleCounter->getPoule();
            $nrOfGames = $this->pouleCounterMap[$poule->pouleNr]->getNrOfGames();
            $availableRefereePlaces = $this->getAvailableRefereePlaces(
                $otherPlacesMap[$poule->pouleNr]
            );
            if ($nrOfGames < count($availableRefereePlaces)) {
                continue;
            }
            foreach ($availableRefereePlaces as $availableRefereePlace) {
                $forcedRefereePlacesMap[$availableRefereePlace->getUniqueIndex()] = 1;
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
            $otherPoulePlacesMap[$poule->pouleNr] = [];
            $otherPoules = $this->poules;
            foreach ($otherPoules as $otherPoule) {
                if ($otherPoule === $poule) {
                    continue;
                }
                $otherPoulePlacesMap[$poule->pouleNr] = array_merge(
                    $otherPoulePlacesMap[$poule->pouleNr],
                    $otherPoule->places
                );
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
