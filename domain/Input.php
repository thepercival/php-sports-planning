<?php

namespace SportsPlanning;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Exception;
use SportsHelpers\Identifiable;
use SportsHelpers\SelfReferee;
use SportsHelpers\Sport\GameAmountVariant as SportGameAmountVariant;
use SportsHelpers\SportRange;
use SportsPlanning\Input\Calculator as InputCalculator;
use SportsHelpers\PouleStructure;

class Input extends Identifiable
{
    /**
     * @var list<int>
     */
    protected array $pouleStructureDb;
    protected PouleStructure|null $pouleStructure = null;
    /**
     * @var list<array<string,int>>
     */
    protected array $sportConfigDb;
    /**
     * @var list<SportGameAmountVariant>|null
     */
    protected array|null $sportVariants = null;
    protected int $nrOfReferees;
    protected DateTimeImmutable $createdAt;
    /**
     * @phpstan-var ArrayCollection<int|string, Planning>|PersistentCollection<int|string, Planning>
     * @psalm-var ArrayCollection<int|string, Planning>
     */
    protected ArrayCollection|PersistentCollection $plannings;
    protected int|null $maxNrOfGamesInARow = null;
    protected bool $teamupDep = false; // DEPRECATED
    protected int $nrOfHeadtoheadDep = 1; // DEPRECATED

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportGameAmountVariant> $sportVariants
     * @param int $nrOfReferees
     * @param int $selfReferee
     */
    public function __construct(
        PouleStructure $pouleStructure,
        array $sportVariants,
        int $nrOfReferees,
        protected int $selfReferee
    ) {
        $this->pouleStructure = $pouleStructure;
        $this->pouleStructureDb = $this->pouleStructure->toArray();

        $this->sportConfigDb = [];
        foreach ($sportVariants as $sportVariant) {
            $this->sportConfigDb[] = $sportVariant->toArray();
        }
        $this->sportVariants = $sportVariants;

        $nrOfReferees = $selfReferee === SelfReferee::DISABLED ? $nrOfReferees : 0;
        $this->nrOfReferees = $nrOfReferees;
        $this->plannings = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
    }

    public function getPouleStructure(): PouleStructure
    {
        if( $this->pouleStructure === null) {
            $this->pouleStructure = new PouleStructure(...$this->pouleStructureDb);
        }
        return $this->pouleStructure;
    }

    public function getNrOfPoules(): int
    {
        return $this->getPouleStructure()->getNrOfPoules();
    }

    public function getNrOfPlaces(): int
    {
        return $this->getPouleStructure()->getNrOfPlaces();
    }

    /**
     * @return list<SportGameAmountVariant>
     */
    public function getSportVariants(): array
    {
        if ($this->sportVariants === null) {
            $this->sportVariants = [];
            foreach ($this->sportConfigDb as $sportConfigDb) {
                $this->sportVariants[] = new SportGameAmountVariant(
                    $sportConfigDb["gameMode"],
                    $sportConfigDb["nrOfGamePlaces"],
                    $sportConfigDb["nrOfFields"],
                    $sportConfigDb["gameAmount"]
                );
            }
        }
        return $this->sportVariants;
    }

    public function hasMultipleSports(): bool
    {
        return count($this->sportConfigDb) > 1;
    }

    public function getNrOfFields(): int
    {
        $nrOfFields = 0;
        foreach ($this->getSportVariants() as $sportVariant) {
            $nrOfFields += $sportVariant->getNrOfFields();
        }
        return $nrOfFields;
    }

    public function getNrOfReferees(): int
    {
        return $this->nrOfReferees;
    }

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
        return $inputCalculator->getMaxNrOfGamesPerBatch(
            $this->getPouleStructure(),
            $this->getSportVariants(),
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
            $this->maxNrOfGamesInARow = $calculator->getMaxNrOfGamesInARow(
                $this->getPouleStructure(),
                $this->getSportVariants(),
                $this->selfRefereeEnabled()
            );
        }
        return $this->maxNrOfGamesInARow;
    }

    /**
     * @param int|null $state = null
     * @phpstan-return ArrayCollection<int|string, Planning>|PersistentCollection<int|string, Planning>
     * @psalm-return ArrayCollection<int|string, Planning>
     */
    public function getPlannings(int|null $state = null): ArrayCollection|PersistentCollection
    {
        if ($state === null) {
            return $this->plannings;
        }
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
        $batchGamesPlannings = $this->getPlannings($state)->filter(function (Planning $planning): bool {
            return $planning->isBatchGames();
        });
        return $this->orderBatchGamesPlannings($batchGamesPlannings);
    }

    // from most most efficient to less efficient
    /**
     * @param ArrayCollection<int|string,Planning> $batchGamesPlannings
     * @return list<Planning>
     */
    public function orderBatchGamesPlannings(ArrayCollection $batchGamesPlannings): array
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
        $bestBatchGamesPlanning = $this->getBestBatchGamesPlanning();
        if ($bestBatchGamesPlanning === null) {
            throw new Exception('er kan geen planning worden gevonden', E_ERROR);
        }
        return $bestBatchGamesPlanning->getBestGamesInARowPlanning();
    }

    public function getBestBatchGamesPlanning(): ?Planning
    {
        foreach ($this->getBatchGamesPlannings(Planning::STATE_SUCCEEDED) as $planning) {
            return $planning;
        }
        return null;
    }
}
