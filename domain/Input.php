<?php
declare(strict_types=1);

namespace SportsPlanning;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Exception;
use SportsHelpers\Identifiable;
use SportsHelpers\SelfReferee;
use SportsHelpers\Sport\Variant\Single as SingleSportVariant;
use SportsHelpers\Sport\Variant\Against as AgainstSportVariant;
use SportsHelpers\Sport\Variant\AllInOneGame as AllInOneGameSportVariant;
use SportsHelpers\SportRange;
use SportsPlanning\Input\Calculator as InputCalculator;
use SportsHelpers\PouleStructure;
use SportsHelpers\Sport\VariantWithFields as SportVariantWithFields;

class Input extends Identifiable
{
    protected string $uniqueString;
    protected DateTimeImmutable $createdAt;
    /**
     * @phpstan-var ArrayCollection<int|string, Poule>|PersistentCollection<int|string, Poule>
     * @psalm-var ArrayCollection<int|string, Poule>
     */
    protected ArrayCollection|PersistentCollection $poules;
    /**
     * @phpstan-var ArrayCollection<int|string, Sport>|PersistentCollection<int|string, Sport>
     * @psalm-var ArrayCollection<int|string, Sport>
     */
    protected ArrayCollection|PersistentCollection $sports;
    /**
     * @phpstan-var ArrayCollection<int|string, Referee>|PersistentCollection<int|string, Referee>
     * @psalm-var ArrayCollection<int|string, Referee>
     */
    protected ArrayCollection|PersistentCollection $referees;
    /**
     * @phpstan-var ArrayCollection<int|string, Planning>|PersistentCollection<int|string, Planning>
     * @psalm-var ArrayCollection<int|string, Planning>
     */
    protected ArrayCollection|PersistentCollection $plannings;
    protected int|null $maxNrOfGamesInARow = null;
    protected bool $teamupDep = false; // DEPRECATED
    protected int $nrOfHeadtoheadDep = 1; // DEPRECATED
    protected string|null $pouleStructureDep = null; // DEPRECATED
    protected string|null $sportConfigDep = null; // DEPRECATED

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportVariantWithFields> $sportVariantsWithFields
     * @param int $nrOfReferees,
     * @param int $selfReferee
     */
    public function __construct(
        PouleStructure $pouleStructure,
        array $sportVariantsWithFields,
        int $nrOfReferees,
        protected int $selfReferee
    ) {
        $this->poules = new ArrayCollection();
        $this->sports = new ArrayCollection();
        $this->referees = new ArrayCollection();
        $this->plannings = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();

        $this->uniqueString = '['. $pouleStructure . ']-';
        foreach ($pouleStructure->toArray() as $nrOfPoulePlaces) {
            $poule = new Poule($this);
            for ($placeNr = 1 ; $placeNr <= $nrOfPoulePlaces ; $placeNr++) {
                new Place($poule);
            }
        }
        foreach ($sportVariantsWithFields as $sportVariantWithFields) {
            $sport = new Sport($this, $sportVariantWithFields->getSportVariant()->createPersistVariant());
            for ($fieldNr = 1 ; $fieldNr <= $sportVariantWithFields->getNrOfFields() ; $fieldNr++) {
                new Field($sport);
            }
        }
        $this->uniqueString .= join(' & ', $this->sports->toArray());
        for ($refNr = 1 ; $refNr <= $nrOfReferees ; $refNr++) {
            new Referee($this);
        }
        $this->uniqueString .= '-REF(' . $nrOfReferees;
        $this->uniqueString .= ':' . $this->getSelfRefereeAsString() . ')';
    }

    public function getUniqueString(): string
    {
        return $this->uniqueString;
    }

    /**
     * @phpstan-return ArrayCollection<int|string, Poule>|PersistentCollection<int|string, Poule>
     * @psalm-return ArrayCollection<int|string, Poule>
     */
    public function getPoules(): ArrayCollection|PersistentCollection
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
     * @phpstan-return ArrayCollection<int|string, Sport>|PersistentCollection<int|string, Sport>
     * @psalm-return ArrayCollection<int|string, Sport>
     */
    public function getSports(): ArrayCollection|PersistentCollection
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
     * @phpstan-return ArrayCollection<int|string, Referee>|PersistentCollection<int|string, Referee>
     * @psalm-return ArrayCollection<int|string, Referee>
     */
    public function getReferees(): ArrayCollection|PersistentCollection
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

    public function getSelfReferee(): int
    {
        return $this->selfReferee;
    }

    public function selfRefereeEnabled(): bool
    {
        return $this->selfReferee !== SelfReferee::DISABLED;
    }

    public function getMaxNrOfBatchGames(): int
    {
        $inputCalculator = new InputCalculator();
        $sportVariantsWithFields = array_values($this->createSportVariantsWithFields()->toArray());
        return $inputCalculator->getMaxNrOfGamesPerBatch(
            $this->createPouleStructure(),
            $sportVariantsWithFields,
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
     * @phpstan-return ArrayCollection<int|string, Planning>|PersistentCollection<int|string, Planning>
     * @psalm-return ArrayCollection<int|string, Planning>
     */
    public function getPlannings(): ArrayCollection|PersistentCollection
    {
        return $this->plannings;
    }

    /**
     * @param int $state
     * @return Collection<int|string, Planning>
     */
    public function getPlanningsWithState(int $state): Collection
    {
        return $this->plannings->filter(function (Planning $planning) use ($state): bool {
            return ($planning->getState() & $state) > 0;
        });
    }

    /**
     * @param int|null $state
     * @return list<Planning>
     */
    public function getBatchGamesPlannings(int|null $state = null): array
    {
        if ($state === null) {
            $batchGamesPlannings = $this->getPlannings();
        } else {
            $batchGamesPlannings = $this->getPlanningsWithState($state)->filter(function (Planning $planning): bool {
                return $planning->isBatchGames();
            });
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
        $succeededPlannings = $this->getPlanningsWithState(Planning::STATE_SUCCEEDED);
        $bestPlanning = $succeededPlannings->first();
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
        if ($this->selfReferee === SelfReferee::DISABLED) {
            return '';
        } elseif ($this->selfReferee === SelfReferee::OTHERPOULES) {
            return 'O';
        } elseif ($this->selfReferee === SelfReferee::SAMEPOULE) {
            return 'S';
        }
        return '?';
    }
}
