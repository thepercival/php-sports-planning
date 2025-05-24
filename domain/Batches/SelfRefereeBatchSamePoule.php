<?php

namespace SportsPlanning\Batches;

use SportsPlanning\Poule;

class SelfRefereeBatchSamePoule extends SelfRefereeBatchAbstract
{
    public function __construct(Batch $batch, SelfRefereeBatchSamePoule $previous = null)
    {
        parent::__construct($batch, $previous);
        $next = $this->getBase()->getNext();
        if ($next !== null) {
            $this->next = new SelfRefereeBatchSamePoule($next, $this);
        }
    }

    public function getFirst(): SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoule
    {
        $previous = $this->getPrevious();
        return $previous !== null ? $previous->getFirst() : $this;
    }

    public function getLeaf(): SelfRefereeBatchSamePoule|SelfRefereeBatchOtherPoule
    {
        $next = $this->getNext();
        return $next !== null ? $next->getLeaf() : $this;
    }

    public function createNext(): SelfRefereeBatchSamePoule
    {
        $this->next = new SelfRefereeBatchSamePoule($this->getBase()->createNext(), $this);
        return $this->next;
    }

    /**
     * @return array<string,int>
     */
    protected function getForcedRefereePlacesMap(): array
    {
        $forcedRefereePlacesMap = [];
        foreach ($this->getPouleCounters() as $pouleCounter) {
            $poule = $pouleCounter->getPoule();

            if ($this->pouleCounterMap[$poule->getNumber()]->calculateNrOfAssignedGamePlaces() !== $poule->getPlaces()->count()) {
                continue;
            }
            $forcedRefereePlaces = $this->getPlacesNotParticipating($poule);
            foreach ($forcedRefereePlaces as $forcedRefereePlace) {
                $forcedRefereePlacesMap[(string)$forcedRefereePlace] = 1;
            }
        }
        return $forcedRefereePlacesMap;
    }

    public function getNrOfPlacesParticipating(Poule $poule, int $nrOfRefereePlacesPerGame): int
    {
        return $this->getNrOfPlacesParticipatingHelper($poule, $nrOfRefereePlacesPerGame);
    }
}
