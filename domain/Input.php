<?php

declare(strict_types=1);

namespace SportsPlanning;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use SportsHelpers\Identifiable;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsHelpers\Sports\AgainstOneVsOne;
use SportsHelpers\Sports\AgainstOneVsTwo;
use SportsHelpers\Sports\AgainstTwoVsTwo;
use SportsHelpers\Sports\TogetherSport;
use SportsPlanning\Exceptions\NoBestPlanningException;
use SportsPlanning\Input\Configuration;
use SportsPlanning\Input\Configuration as InputConfiguration;
use SportsPlanning\Planning\Comparer;
use SportsPlanning\Planning\Filter as PlanningFilter;
use SportsPlanning\Planning\HistoricalBestPlanning;
use SportsPlanning\Planning\State as PlanningState;
use SportsPlanning\Planning\Type as PlanningType;
use SportsPlanning\PlanningPouleStructure as PlanningPouleStructure;
use SportsPlanning\Referee\Info as RefereeInfo;
use SportsPlanning\Sports\Plannable\PlannableSport;
use SportsPlanning\Sports\SportWithNrOfFields;

class Input extends Identifiable
{
    private const int MaxNrOfGamesInARow = 5;


    protected string $name;
    protected DateTimeImmutable $createdAt;
    protected bool|null $hasBalancedStructure = null;
    protected SelfReferee $selfReferee;
    protected int $nrOfSimSelfRefs;
    protected int $seekingPercentage = -1;


    // protected Collection $categories;
    /**
     * @var Collection<int|string, Poule>
     */
    protected Collection $poules;
    /**
     * @var Collection<int|string, PlannableSport>
     */
    protected Collection $sports;
    /**
     * @var Collection<int|string, Referee>
     */
    protected Collection $referees;
    /**
     * @var Collection<int|string, Planning>
     */
    protected Collection $plannings;
    /**
     * @var Collection<int|string, HistoricalBestPlanning>
     */
    protected Collection $historicalBestPlannings;
    protected int|null $maxNrOfGamesInARow = null;
    protected bool $perPoule;

    public function __construct(readonly Configuration $configuration) {
        $this->perPoule = $configuration->perPoule;

        // $this->categories = new ArrayCollection();
        $this->poules = new ArrayCollection();
        $this->sports = new ArrayCollection();
        $this->referees = new ArrayCollection();
        $this->plannings = new ArrayCollection();
        $this->historicalBestPlannings = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();

//        $planningPouleStructure = $configuration->planningPouleStructure;
        $pouleStructure = $configuration->pouleStructure;
        foreach ($pouleStructure->toArray() as $nrOfPoulePlaces) {
            $poule = new Poule($this);
            for ($placeNr = 1; $placeNr <= $nrOfPoulePlaces; $placeNr++) {
                new Place($poule);
            }
        }

        foreach ($configuration->sportsWithNrOfFields as $sportWithNrOfFields) {
            $sport = new PlannableSport($this);
            for ($fieldNr = 1; $fieldNr <= $sportVariantWithNrOfFields->nrOfFields; $fieldNr++) {
                new Field($sport);
            }
        }
        if ($this->hasMultipleSports()) {
            $this->perPoule = false;
        }

        $refereeInfo = $planningPouleStructure->refereeInfo;
        $this->selfReferee = $refereeInfo->selfRefereeInfo->selfReferee;
        $this->nrOfSimSelfRefs = $refereeInfo->selfRefereeInfo->nrIfSimSelfRefs;
        if ($this->selfReferee === SelfReferee::Disabled) {
            for ($refNr = 1; $refNr <= $refereeInfo->nrOfReferees; $refNr++) {
                new Referee($this);
            }
        }

        $this->name = $configuration->getName();
    }

    public function createConfiguration(): Configuration {

        return new InputConfiguration(
            $this->createPouleStructure(),
            $this->createSportVariantsWithFields(),
            $this->getRefereeInfo(),
            $this->getPerPoule()
        );
    }



    /**
     * @return Collection<int|string, Poule>
     */
    public function getPoules(): Collection
    {
        return $this->poules;
    }

    /*public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function getPoulesOrderedBySize(): array
    {
        foreach ($this->getPoules() as $poule) {
            if ($poule->getNumber() === $pouleNr) {
                return $poule;
            }
        }
        throw new Exception('de poule kan niet gevonden worden', E_ERROR);
    }
    */

    public function getPoule(int $pouleNr): Poule
    {
        foreach ($this->getPoules() as $poule) {
            if ($poule->getNumber() === $pouleNr) {
                return $poule;
            }
        }
        throw new Exception('de poule kan niet gevonden worden', E_ERROR);
    }

    public function getFirstPoule(): Poule
    {
        return $this->getPoule(1);
    }

    public function getLastPoule(): Poule
    {
        return $this->getPoule($this->getPoules()->count());
    }

    /**
     * @return ArrayCollection<int|string, Place>
     */
    public function getPlaces(): ArrayCollection
    {
        /** @var ArrayCollection<int|string, Place> $places */
        $places = new ArrayCollection();
        foreach ($this->getPoules() as $poule) {
            foreach ($poule->getPlaces() as $place) {
                $places->add($place);
            }
        }
        return $places;
    }

    public function getPlace(string $location): Place
    {
        $pos = strpos($location, ".");
        if ($pos === false) {
            throw new Exception('geen punt gevonden in locatie', E_ERROR);
        }
        $pouleNr = (int)substr($location, 0, $pos);
        $placeNr = (int)substr($location, $pos + 1);
        return $this->getPoule($pouleNr)->getPlace($placeNr);
    }

    public function getNrOfPlaces(): int
    {
        $nrOfPlaces = 0;
        foreach ($this->getPoules() as $poule) {
            $nrOfPlaces += $poule->getPlaces()->count();
        }
        return $nrOfPlaces;
    }

    public function createPouleStructure(): PouleStructure
    {
        $poules = [];
        foreach ($this->getPoules() as $poule) {
            $poules[] = $poule->getPlaces()->count();
        }
        return new PouleStructure(...$poules);
    }

    public function createPlanningPouleStructure(): PlanningPouleStructure
    {
        return new PlanningPouleStructure(
            $this->createPouleStructure(),
            $this->sports->toArray(),
            $this->getRefereeInfo()
        );
    }

    /*public function createPouleStructures(): array
    {
        $pouleStructures = [];
        foreach( $this->categories as $category) {
            $poules = [];
            foreach ($category->getPoules() as $poule) {
                $poules[] = $poule->getPlaces()->count();
            }
            $pouleStructures[] = new PouleStructure(...$poules);
        }
        return $pouleStructures;
    }*/

    /**
     * @return Collection<int|string, PlannableSport>
     */
    public function getSports(): Collection
    {
        return $this->sports;
    }

    public function getSport(int $number): PlannableSport
    {
        foreach ($this->getSports() as $sport) {
            if ($sport->getNumber() === $number) {
                return $sport;
            }
        }
        throw new Exception('sport kan niet gevonden worden', E_ERROR);
    }

    /**
     * @return list<SportWithNrOfFields>
     */
    public function createSportsWithNrOfFields(): array
    {
        return array_values( array_map(function (PlannableSport $sport): SportWithNrOfFields {
            return $sport->createSportWithNrOfFields();
        }, $this->sports->toArray()) );
    }

    /**
     * @return list<AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport>
     */
    public function createSports(): array
    {
        return array_map( function (PlannableSport $sport): AgainstOneVsOne|AgainstOneVsTwo|AgainstTwoVsTwo|TogetherSport {
            return $sport->sport;
        }, $this->sports->toArray());
    }

    /**
     * @return list<Field>
     */
    public function getFields(): array
    {
        $fields = [];
        foreach ($this->getSports() as $sport) {
            foreach ($sport->getFields() as $field) {
                array_push($fields, $field);
            }
        }
        return $fields;
    }

    /**
     * @return Collection<int|string, Referee>
     */
    public function getReferees(): Collection
    {
        return $this->referees;
    }

    public function getReferee(int $refereeNr): Referee
    {
        foreach ($this->getReferees() as $referee) {
            if ($referee->getNumber() === $refereeNr) {
                return $referee;
            }
        }
        throw new Exception('scheidsrechter kan niet gevonden worden', E_ERROR);
    }

    public function hasMultipleSports(): bool
    {
        return $this->sports->count() > 1;
    }

    public function getSelfReferee(): SelfReferee
    {
        return $this->selfReferee;
    }

    public function selfRefereeEnabled(): bool
    {
        return $this->selfReferee !== SelfReferee::Disabled;
    }

    public function getNrOfSimSelfRefs(): int
    {
        return $this->nrOfSimSelfRefs;
    }


    public function getPerPoule(): bool
    {
        return $this->perPoule;
    }

    public function getMaxNrOfBatchGames(): int
    {
        return (new PlanningPouleStructure(
            $this->createPouleStructure(),
            $this->createSportVariantsWithFields(),
            $this->getRefereeInfo()
        ))->getMaxNrOfGamesPerBatch();
    }

    public function getMaxNrOfGamesInARow(): int
    {
        if ($this->maxNrOfGamesInARow === null) {
            $this->maxNrOfGamesInARow = (new PlanningPouleStructure(
                $this->createPouleStructure(),
                $this->createSportVariantsWithFields(),
                $this->getRefereeInfo()
            ))->getMaxNrOfGamesInARow();
            if ($this->maxNrOfGamesInARow > self::MaxNrOfGamesInARow) {
                $this->maxNrOfGamesInARow = self::MaxNrOfGamesInARow;
            }
        }
        return $this->maxNrOfGamesInARow;
    }

    /**
     * @return Collection<int|string, Planning>
     */
    public function getPlannings(): Collection
    {
        return $this->plannings;
    }

    /**
     * @param PlanningFilter|null $filter
     * @return list<Planning>
     */
    public function getFilteredPlannings(PlanningFilter|null $filter = null): array
    {
        if( $filter === null ) {
            return array_values( $this->plannings->toArray() );
        }
        $filtered = $this->plannings->filter( function(Planning $planning) use($filter):  bool {
            return $filter->equals($planning);
        })->toArray();
        uasort($filtered, function (Planning $first, Planning $second) {
            if ($first->getMaxNrOfBatchGames() === $second->getMaxNrOfBatchGames()) {
                return $second->getMinNrOfBatchGames() - $first->getMinNrOfBatchGames();
            }
            return $second->getMaxNrOfBatchGames() - $first->getMaxNrOfBatchGames();
        });

        return array_values($filtered);
    }

    public function getPlanning(PlanningFilter $filter): Planning|null
    {
        $plannings = $this->getFilteredPlannings($filter);
        $planning = reset($plannings);
        return $planning === false ? null : $planning;
    }

    public function getBestPlanning(PlanningType|null $type): Planning
    {
        $filter = new PlanningFilter(
            $type, PlanningState::Succeeded, null, null
        );
        $succeededPlannings = $this->getFilteredPlannings($filter);

        uasort($succeededPlannings, function (Planning|HistoricalBestPlanning $first, Planning|HistoricalBestPlanning $second): int {
            return (new Comparer())->compare($first, $second);
        });
        $bestPlanning = array_shift($succeededPlannings);
        if ($bestPlanning === null) {
            throw new NoBestPlanningException($this, $type);
        }
        return $bestPlanning;
    }

    /**
     * @return Collection<int|string, HistoricalBestPlanning>
     */
    public function getHistoricalBestPlannings(): Collection
    {
        return $this->historicalBestPlannings;
    }

    public function getHistoricalVeryBestPlanning(): HistoricalBestPlanning|null
    {
        $historicalBestPlannings = $this->getHistoricalBestPlannings()->toArray();
        uasort($historicalBestPlannings, function (Planning|HistoricalBestPlanning $first, Planning|HistoricalBestPlanning $second): int {
            return (new Comparer())->compare($first, $second);
        });
        return array_shift($historicalBestPlannings);
    }

    public function getSelfRefereeInfo(): SelfRefereeInfo
    {
        return new SelfRefereeInfo($this->selfReferee, $this->nrOfSimSelfRefs);
    }

    public function getRefereeInfo(): RefereeInfo
    {
        if( $this->selfReferee === SelfReferee::Disabled ) {
            $selfRefereeInfoOrNrOfReferees = count($this->getReferees());
        } else {
            $selfRefereeInfoOrNrOfReferees = $this->getSelfRefereeInfo();
        }
        return new RefereeInfo($selfRefereeInfoOrNrOfReferees);
    }

    public function hasBalancedStructure(): bool
    {
        if ($this->hasBalancedStructure === null) {
            $highestNrPlaces = $this->getFirstPoule()->getPlaces()->count();
            $lowestNrPlaces = $this->getLastPoule()->getPlaces()->count();
            $this->hasBalancedStructure = $highestNrPlaces == $lowestNrPlaces;
        }
        return $this->hasBalancedStructure;
    }

    public function getSeekingPercentage(): int|null
    {
        return $this->seekingPercentage;
    }

    public function setSeekingPercentage(int $seekingPercentage): void
    {
        $this->seekingPercentage = $seekingPercentage;
    }
}
