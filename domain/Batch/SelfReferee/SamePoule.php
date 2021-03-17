<?php

namespace SportsPlanning\Batch\SelfReferee;

use SportsPlanning\Batch;

class SamePoule extends Batch\SelfReferee
{
    public function __construct(Batch $batch, SamePoule $previous = null)
    {
        parent::__construct($batch, $previous);
        $next = $this->getBase()->getNext();
        if ($next !== null) {
            $this->next = new SamePoule($next, $this);
        }
    }

    public function getFirst(): SamePoule|OtherPoule
    {
        $previous = $this->getPrevious();
        return $previous !== null ? $previous->getFirst() : $this;
    }

    public function getLeaf(): SamePoule|OtherPoule
    {
        $next = $this->getNext();
        return $next !== null ? $next->getLeaf() : $this;
    }

    public function createNext(): SamePoule
    {
        $this->next = new SamePoule($this->getBase()->createNext(), $this);
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

            if ($this->pouleCounterMap[$poule->getNumber()]->getNrOfPlacesAssigned(true) !== $poule->getPlaces(
                )->count()) {
                continue;
            }
            $forcedRefereePlaces = $this->getPlacesNotParticipating($poule);
            foreach ($forcedRefereePlaces as $forcedRefereePlace) {
                $forcedRefereePlacesMap[$forcedRefereePlace->getLocation()] = 1;
            }
        }
        return $forcedRefereePlacesMap;
    }
}
