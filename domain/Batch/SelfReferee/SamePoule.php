<?php

namespace SportsPlanning\Batch\SelfReferee;

use SportsPlanning\Batch;
use SportsPlanning\Batch\SelfReferee;

class SamePoule extends Batch\SelfReferee
{
    public function __construct(Batch $batch, SelfReferee $previous = null)
    {
        parent::__construct($batch, $previous);

        if ($this->getBase()->hasNext()) {
            $this->next = new SamePoule($this->getBase()->getNext(), $this);
        }
    }

    public function createNext(): SamePoule
    {
        $this->next = new SamePoule($this->getBase()->createNext(), $this);
        return $this->next;
    }

    /**
     * @return array|int[]
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
