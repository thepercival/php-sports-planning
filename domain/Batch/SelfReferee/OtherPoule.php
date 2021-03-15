<?php

namespace SportsPlanning\Batch\SelfReferee;

use SportsPlanning\Batch;
use SportsPlanning\Place;
use SportsPlanning\Poule;

class OtherPoule extends Batch\SelfReferee
{
    /**
     * @var array | Poule[]
     */
    protected array $poules;

    public function __construct(array $poules, Batch $batch, OtherPoule $previous = null)
    {
        parent::__construct($batch, $previous);
        $this->poules = $poules;

        $nextBatch = $this->getBase()->getNext();
        if ($nextBatch !== null) {
            $this->next = new OtherPoule($poules, $nextBatch, $this);
        }
    }

    public function getFirst(): OtherPoule|SamePoule
    {
        $previous = $this->getPrevious();
        return $previous !== null ? $previous->getFirst() : $this;
    }

    public function getLeaf(): OtherPoule|SamePoule
    {
        $next = $this->getNext();
        return $next !== null ? $next->getLeaf() : $this;
    }

    public function createNext(): OtherPoule
    {
        $this->next = new OtherPoule($this->poules, $this->getBase()->createNext(), $this);
        return $this->next;
    }

    /**
     * @return array<int>
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
                $forcedRefereePlacesMap[$availableRefereePlace->getLocation()] = 1;
            }
        }
        return $forcedRefereePlacesMap;
    }

    /**
     * @return array<array<Place>>
     */
    protected function getOtherPlacesMap(): array
    {
        $otherPoulePlacesMap = [];
        foreach ($this->poules as $poule) {
            $otherPoulePlacesMap[$poule->getNumber()] = [];
            $otherPoules = array_slice($this->poules, 0);
            foreach ($otherPoules as $otherPoule) {
                if ($otherPoule === $poule) {
                    continue;
                }
                $otherPoulePlacesMap[$poule->getNumber()] = array_merge(
                    $otherPoulePlacesMap[$poule->getNumber()],
                    $otherPoule->getPlaces()->toArray()
                );
            }
        }
        return $otherPoulePlacesMap;
    }

    /**
     * @param array<Place> $otherPoulePlaces
     * @return array<Place>
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
}
