<?php

declare(strict_types=1);

namespace SportsPlanning;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use SportsHelpers\PouleStructures\PouleStructure;
use SportsHelpers\SelfReferee;
use SportsHelpers\SelfRefereeInfo;
use SportsHelpers\Sport\Variant\Against\GamesPerPlace as AgainstGpp;
use SportsHelpers\Sport\Variant\Against\H2h as AgainstH2h;
use SportsHelpers\Sport\Variant\AllInOneGame;
use SportsHelpers\Sport\Variant\Single;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsPlanning\Exceptions\NoBestPlanningException;
use SportsPlanning\Input\Configuration;
use SportsPlanning\Input\Configuration as InputConfiguration;
use SportsPlanning\Planning\Comparer;
use SportsPlanning\Planning\Filter as PlanningFilter;
use SportsPlanning\Planning\HistoricalBestPlanning;
use SportsPlanning\Planning\State as PlanningState;
use SportsPlanning\Planning\Type as PlanningType;

final class Input extends Identifiable
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
     * @var Collection<int|string, Sport>
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

        $pouleStructure = $configuration->pouleStructure;
        foreach ($pouleStructure->toArray() as $nrOfPoulePlaces) {
            $poule = new Poule($this, null);
            for ($placeNr = 1; $placeNr <= $nrOfPoulePlaces; $placeNr++) {
                new Place($poule, null);
            }
        }
        /*
        foreach( $pouleStructures as $pouleStructure) {
            $category = new Category($this);
            foreach ($pouleStructure->toArray() as $nrOfPoulePlaces) {
                $poule = new Poule($category);
                for ($placeNr = 1; $placeNr <= $nrOfPoulePlaces; $placeNr++) {
                    new Place($poule);
                }
            }
        }*/

        $hasAgainstH2h = false;
        foreach ($configuration->sportVariantsWithFields as $sportVariantWithFields) {
            $sportVariant = $sportVariantWithFields->getSportVariant();
            if ($sportVariant instanceof AgainstH2h) {
                $hasAgainstH2h = true;
            }
            if (!($sportVariant instanceof AllInOneGame)
                && $sportVariant->getNrOfGamePlaces() > $pouleStructure->getSmallestPoule()) {
                throw new Exception(
                    'te weinig poule-plekken om wedstrijden te kunnen plannen, maak de poule(s) groter',
                    E_ERROR
                );
            }
            $sport = new Sport($this, $sportVariant->toPersistVariant());
            for ($fieldNr = 1; $fieldNr <= $sportVariantWithFields->getNrOfFields(); $fieldNr++) {
                new Field($sport,null);
            }
        }
        if ($this->hasMultipleSports()) {
            $this->perPoule = false;
            if ($hasAgainstH2h) {
                throw new Exception(
                    'bij meerdere sporten mag h2h niet gebruikt worden(Input), pas de sporten aan',
                    E_ERROR
                );
            }
        }

        $refereeInfo = $configuration->refereeInfo;
        $this->selfReferee = $refereeInfo->selfRefereeInfo?->selfReferee ?? SelfReferee::Disabled;
        $this->nrOfSimSelfRefs = $refereeInfo->selfRefereeInfo?->nrOfSimSelfRefs ?? 1;
        if ($this->selfReferee === SelfReferee::Disabled) {
            for ($refNr = 1; $refNr <= $refereeInfo->nrOfReferees; $refNr++) {
                new Referee($this, null);
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

    /*public function createPouleStructures(): array
    {
        $pouleStructures = [];
        foreach( $this->categories as $category) {
            $poules = [];
            foreach ($category->getPoules() as $poule) {
                $poules[] = $poule->getPlaces()->count();
            }
            $pouleStructures[] = new PlanningPouleStructure(...$poules);
        }
        return $pouleStructures;
    }*/

    /**
     * @return Collection<int|string, Sport>
     */
    public function getSports(): Collection
    {
        return $this->sports;
    }

    public function getSport(int $number): Sport
    {
        foreach ($this->getSports() as $sport) {
            if ($sport->getNumber() === $number) {
                return $sport;
            }
        }
        throw new Exception('sport kan niet gevonden worden', E_ERROR);
    }

    /**
     * @return list<SportVariantWithFields>
     */
    public function createSportVariantsWithFields(): array
    {
        return array_values( array_map(function (Sport $sport): SportVariantWithFields {
            return $sport->createVariantWithFields();
        }, $this->sports->toArray()) );
    }

    /**
     * @return list<Single|AgainstH2h|AgainstGpp|AllInOneGame>
     */
    public function createSportVariants(): array
    {
        return array_values( array_map( function (Sport $sport): Single|AgainstH2h|AgainstGpp|AllInOneGame {
            return $sport->createVariant();
        }, $this->sports->toArray()) );
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

    public function getRefereeInfo(): PlanningRefereeInfo
    {
        if( $this->selfReferee === SelfReferee::Disabled ) {
            $selfRefereeInfoOrNrOfReferees = count($this->getReferees());
        } else {
            $selfRefereeInfoOrNrOfReferees = $this->getSelfRefereeInfo();
        }
        return new PlanningRefereeInfo($selfRefereeInfoOrNrOfReferees);
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
