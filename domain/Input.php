<?php

declare(strict_types=1);

namespace SportsPlanning;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use SportsHelpers\Identifiable;
use SportsHelpers\PouleStructure;
use SportsHelpers\SelfReferee;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;
use SportsHelpers\SportRange;
use SportsPlanning\Combinations\GamePlaceStrategy;
use SportsPlanning\Input\Calculator as InputCalculator;
use SportsPlanning\Planning\State as PlanningState;

class Input extends Identifiable
{
    protected string $uniqueString;
    protected DateTimeImmutable $createdAt;
    protected bool|null $hasBalancedStructure = null;

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
    protected int|null $maxNrOfGamesInARow = null;

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportVariantWithFields> $sportVariantsWithFields
     * @param GamePlaceStrategy $gamePlaceStrategy
     * @param int $nrOfReferees
     * @param SelfReferee $selfReferee
     */
    public function __construct(
        PouleStructure $pouleStructure,
        array $sportVariantsWithFields,
        protected GamePlaceStrategy $gamePlaceStrategy,
        int $nrOfReferees,
        protected SelfReferee $selfReferee
    ) {
        $this->poules = new ArrayCollection();
        $this->sports = new ArrayCollection();
        $this->referees = new ArrayCollection();
        $this->plannings = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();

        foreach ($pouleStructure->toArray() as $nrOfPoulePlaces) {
            $poule = new Poule($this);
            for ($placeNr = 1 ; $placeNr <= $nrOfPoulePlaces ; $placeNr++) {
                new Place($poule);
            }
        }
        foreach ($sportVariantsWithFields as $sportVariantWithFields) {
            $sportVariant = $sportVariantWithFields->getSportVariant();
            if (($sportVariant instanceof AgainstSportVariant || $sportVariant instanceof SingleSportVariant)
                && $sportVariant->getNrOfGamePlaces() > $pouleStructure->getSmallestPoule()) {
                throw new Exception('te weinig plekken om wedstrijden te kunnen plannen', E_ERROR);
            }
            $sport = new Sport($this, $sportVariant->toPersistVariant());
            for ($fieldNr = 1 ; $fieldNr <= $sportVariantWithFields->getNrOfFields() ; $fieldNr++) {
                new Field($sport);
            }
        }
        if ($selfReferee === SelfReferee::Disabled) {
            for ($refNr = 1; $refNr <= $nrOfReferees; $refNr++) {
                new Referee($this);
            }
        }

        $strat = $this->gamePlaceStrategy === GamePlaceStrategy::RandomlyAssigned ? 'rndm' : 'eql';
        $uniqueStrings = [
            '['. $pouleStructure . ']',
            '[' . join(' & ', $this->sports->toArray()) . ']',
            'gpstrat=>' . $strat,
            'ref=>' . $this->getReferees()->count() . ':' . $this->getSelfRefereeAsString()
        ];
        $this->uniqueString = join(' - ', $uniqueStrings);
    }

    public function getGamePlaceStrategy(): GamePlaceStrategy
    {
        return $this->gamePlaceStrategy;
    }

    public function getUniqueString(): string
    {
        return $this->uniqueString;
    }

    /**
     * @return Collection<int|string, Poule>
     */
    public function getPoules(): Collection
    {
        return $this->poules;
    }

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

//    /**
//     * @param PouleStructure $pouleStructure
//     * @return void
//     */
//    protected function initPoules(PouleStructure $pouleStructure): void
//    {
//        $this->poules = new ArrayCollection();
//        foreach ($pouleStructure->toArray() as $nrOfPlaces) {
//            $this->poules->add(new Poule($this, $this->poules->count() + 1, $nrOfPlaces));
//        }
//    }

    public function createPouleStructure(): PouleStructure
    {
        $poules = [];
        foreach ($this->getPoules() as $poule) {
            $poules[] = $poule->getPlaces()->count();
        }
        return new PouleStructure(...$poules);
    }

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

//    /**
//     * @param list<SportGameAmountVariant> $sportVariants
//     */
//    protected function initSports(array $sportVariants): void
//    {
//        $this->sports = new ArrayCollection();
//        foreach ($sportVariants as $sportVariant) {
//            $sport = new Sport(
//                $this,
//                $this->sports->count() + 1,
//                $sportVariant->getGameMode(),
//                $sportVariant->getNrOfGamePlaces(),
//                $sportVariant->getGameAmount(),
//            );
//            $this->sports->add($sport);
//            for ($fieldNrDelta = 0 ; $fieldNrDelta < $sportVariant->getNrOfFields() ; $fieldNrDelta++) {
//                new Field($sport);
//            }
//        }
//    }

    /**
     * @return Collection<int|string, SportVariantWithFields>
     */
    public function createSportVariantsWithFields(): Collection
    {
        return $this->sports->map(function (Sport $sport): SportVariantWithFields {
            return $sport->createVariantWithFields();
        });
    }

    /**
     * @return Collection<int|string, SingleSportVariant|AgainstSportVariant|AllInOneGameSportVariant>
     */
    public function createSportVariants(): Collection
    {
        return $this->sports->map(function (Sport $sport): SingleSportVariant|AgainstSportVariant|AllInOneGameSportVariant {
            return $sport->createVariant();
        });
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

//    protected function initReferees(int $nrOfReferees): void
//    {
//        $this->referees = new ArrayCollection();
//        for ($refereeNr = 1 ; $refereeNr <= $nrOfReferees ; $refereeNr++) {
//            $this->referees->add(new Referee($this, $refereeNr));
//        }
//    }

    public function getReferee(int $refereeNr): Referee
    {
        foreach ($this->getReferees() as $referee) {
            if ($referee->getNumber() === $refereeNr) {
                return $referee;
            }
        }
        throw new Exception('scheidsrechter kan niet gevonden worden', E_ERROR);
    }

//    /**
//     * @return list<SingleSportVariant|AgainstSportVariant|AllInOneGameSportVariant>
//     */
//    public function getSportVariants(): array
//    {
//        if ($this->sportVariants === null) {
//            $this->sportVariants = [];
//            foreach ($this->sportConfigDb as $sportConfigDb) {
//                // bepaal obv ggamemode welke instanceof er moet komen => switch$sportConfigDb["gameMode"]
//                $this->sportVariants[] = new SportGameAmountVariant(
//                    $sportConfigDb["gameMode"],
//                    $sportConfigDb["nrOfGamePlaces"],
//                    $sportConfigDb["nrOfFields"],
//                    $sportConfigDb["gameAmount"]
//                );
//            }
//        }
//        return $this->sportVariants;
//    }

    public function hasMultipleSports(): bool
    {
        return $this->sports->count() > 1;
    }

//    public function getNrOfFields(): int
//    {
//        $nrOfFields = 0;
//        foreach ($this->getSportVariants() as $sportVariant) {
//            $nrOfFields += $sportVariant->getNrOfFields();
//        }
//        return $nrOfFields;
//    }

    public function getSelfReferee(): SelfReferee
    {
        return $this->selfReferee;
    }

    public function selfRefereeEnabled(): bool
    {
        return $this->selfReferee !== SelfReferee::Disabled;
    }

    public function getMaxNrOfBatchGames(): int
    {
        $inputCalculator = new InputCalculator();
        $sportVariantsWithFields = array_values($this->createSportVariantsWithFields()->toArray());
        return $inputCalculator->getMaxNrOfGamesPerBatch(
            $this->createPouleStructure(),
            $sportVariantsWithFields,
            $this->getReferees()->count(),
            $this->selfRefereeEnabled()
        );
    }

//    public function getMaxNrOfBatchGames(int $resources = null): int
//    {
//        $maxNrOfBatchGames = null;
//        if ((Resources::FIELDS & $resources) === Resources::FIELDS || $resources === null) {
//            // kijk als alle velden
//            $maxNrOfBatchGames = $this->getNrOfFields();
//        }
//
//        if ((Resources::REFEREES & $resources) === Resources::REFEREES || $resources === null) {
//            if (!$this->selfRefereeEnabled() && $this->getNrOfReferees() > 0
//                && ($this->getNrOfReferees() < $maxNrOfBatchGames || $maxNrOfBatchGames === null)) {
//                $maxNrOfBatchGames = $this->getNrOfReferees();
//            }
//        }
//
//        if ((Resources::PLACES & $resources) === Resources::PLACES || $resources === null) {
//            $calculator = new InputCalculator();
//            $maxNrOfGamesSim = $calculator->getMaxNrOfGamesPerBatch( $this->getPouleStructure(), $this->getSportConfigs(), $this->selfRefereeEnabled() );
//            if ($maxNrOfGamesSim < $maxNrOfBatchGames || $maxNrOfBatchGames === null) {
//                $maxNrOfBatchGames = $maxNrOfGamesSim;
//            }
//        }
//        return $maxNrOfBatchGames;
//    }

    public function getMaxNrOfGamesInARow(): int
    {
        if ($this->maxNrOfGamesInARow === null) {
            $calculator = new InputCalculator();
            $this->maxNrOfGamesInARow = $calculator->getMaxNrOfGamesInARow($this, $this->selfRefereeEnabled());
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
     * @param int $stateValue
     * @return Collection<int|string, Planning>
     */
    public function getPlanningsWithState(int $stateValue): Collection
    {
        return $this->plannings->filter(function (Planning $planning) use ($stateValue): bool {
            return ($planning->getState()->value & $stateValue) > 0;
        });
    }

    /**
     * @param int|null $stateValue
     * @return list<Planning>
     */
    public function getBatchGamesPlannings(int|null $stateValue = null): array
    {
        if ($stateValue === null) {
            $batchGamesPlannings = $this->getPlannings();
        } else {
            $batchGamesPlannings = $this->getPlanningsWithState($stateValue)->filter(
                function (Planning $planning): bool {
                    return $planning->isBatchGames();
                }
            );
        }
        return $this->orderBatchGamesPlannings($batchGamesPlannings);
    }

    // from most most efficient to less efficient
    /**
     * @param Collection<int|string,Planning> $batchGamesPlannings
     * @return list<Planning>
     */
    public function orderBatchGamesPlannings(Collection $batchGamesPlannings): array
    {
        $plannings = $batchGamesPlannings->toArray();
        uasort($plannings, function (Planning $first, Planning $second) {
            if ($first->getMaxNrOfBatchGames() === $second->getMaxNrOfBatchGames()) {
                if ($first->getMinNrOfBatchGames() === $second->getMinNrOfBatchGames()) {
                    $firstMaxBatchNr = $first->createFirstBatch()->getLeaf()->getNumber();
                    $secondMaxBatchNr = $second->createFirstBatch()->getLeaf()->getNumber();
                    return $firstMaxBatchNr < $secondMaxBatchNr ? -1 : 1;
                }
                return $first->getMinNrOfBatchGames() < $second->getMinNrOfBatchGames() ? -1 : 1;
            }
            return $first->getMaxNrOfBatchGames() < $second->getMaxNrOfBatchGames() ? -1 : 1;
        });
        return array_values($plannings);
    }

    public function getPlanning(SportRange $range, int $maxNrOfGamesInARow): ?Planning
    {
        foreach ($this->getPlannings() as $planning) {
            if ($planning->getMinNrOfBatchGames() === $range->getMin()
                && $planning->getMaxNrOfBatchGames() === $range->getMax()
                && $planning->getMaxNrOfGamesInARow() === $maxNrOfGamesInARow) {
                return $planning;
            }
        }
        return null;
    }

    public function getBestPlanning(): Planning
    {
        $succeededPlannings = $this->getPlanningsWithState(PlanningState::Succeeded->value)->toArray();
        uasort($succeededPlannings, function (Planning $first, Planning $second): int {
            return $second->getMaxNrOfGamesInARow() - $first->getMaxNrOfGamesInARow();
        });
        $bestPlanning = reset($succeededPlannings);
        if (!($bestPlanning instanceof Planning)) {
            throw new Exception('er kan geen planning worden gevonden', E_ERROR);
        }
        foreach ($succeededPlannings as $succeededPlanning) {
            if ($succeededPlanning->getNrOfBatches() < $bestPlanning->getNrOfBatches()) {
                $bestPlanning = $succeededPlanning;
            }
        }
        return $bestPlanning;
    }

    protected function getSelfRefereeAsString(): string
    {
        if ($this->selfReferee === SelfReferee::Disabled) {
            return '';
        } elseif ($this->selfReferee === SelfReferee::OtherPoules) {
            return 'OP';
        } elseif ($this->selfReferee === SelfReferee::SamePoule) {
            return 'SP';
        }
        return '?';
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
}
