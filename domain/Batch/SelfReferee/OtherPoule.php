<?php

namespace SportsPlanning\Batch\SelfReferee;

use SportsPlanning\Batch;
use SportsPlanning\Place;
use SportsPlanning\Poule;

class OtherPoule extends Batch\SelfReferee
{
    /**
     * @param list<Poule> $poules
     * @param Batch $batch
     * @param OtherPoule|null $previous
     */
    public function __construct(protected array $poules, Batch $batch, OtherPoule|null $previous = null)
    {
        parent::__construct($batch, $previous);

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
     * @return array<int,list<Place>>
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
