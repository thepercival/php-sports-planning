<?php

namespace SportsPlanning;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use SportsHelpers\Identifiable;
use SportsHelpers\SportRange;
use SportsHelpers\SportConfig;
use SportsPlanning\Input\Calculator as InputCalculator;
use SportsHelpers\PouleStructure;
use SportsHelpers\SportBase;

class Input extends Identifiable
{
    /**
     * @var list<int>
     */
    protected array $pouleStructureDb;
    /**
     * @var list<array<string,int>>
     */
    protected array $sportConfigDb;
    /**
     * @var list<SportConfig>|null
     */
    protected array|null $sportConfigs = null;
    protected int $nrOfReferees;
    protected DateTimeImmutable $createdAt;
    /**
     * @var ArrayCollection<int|string,Planning>
     */
    protected ArrayCollection $plannings;
    protected int|null $maxNrOfGamesInARow = null;
    protected bool $teamupDep = false; // DEPRECATED
    protected int $nrOfHeadtoheadDep = 1; // DEPRECATED

    /**
     * @param PouleStructure $pouleStructure
     * @param list<SportConfig> $sportConfigs
     * @param int $nrOfReferees
     * @param int $selfReferee
     */
    public function __construct(
        protected PouleStructure $pouleStructure,
        array $sportConfigs,
        int $nrOfReferees,
        protected int $selfReferee
    ) {
        $this->pouleStructureDb = $this->pouleStructure->toArray();

        $this->sportConfigDb = [];
        foreach ($sportConfigs as $sportConfig) {
            $this->sportConfigDb[] = $sportConfig->toArray();
        }
        $this->sportConfigs = $sportConfigs;

        $nrOfReferees = $selfReferee === SelfReferee::DISABLED ? $nrOfReferees : 0;
        $this->nrOfReferees = $nrOfReferees;
        $this->plannings = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
    }

    public function getPouleStructure(): PouleStructure
    {
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
     * @return list<SportConfig>
     */
    public function getSportConfigs(): array
    {
        if ($this->sportConfigs === null) {
            $this->sportConfigs = [];
            foreach ($this->sportConfigDb as $sportConfigDb) {
                $this->sportConfigs[] = new SportConfig(
                    new SportBase($sportConfigDb["gameMode"], $sportConfigDb["nrOfGamePlaces"]),
                    $sportConfigDb["nrOfFields"],
                    $sportConfigDb["gameAmount"]
                );
            }
        }
        return $this->sportConfigs;
    }

    public function hasMultipleSports(): bool
    {
        return count($this->sportConfigDb) > 1;
    }

    public function getNrOfFields(): int
    {
        $nrOfFields = 0;
        foreach ($this->getSportConfigs() as $sportConfig) {
            $nrOfFields += $sportConfig->getNrOfFields();
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
            $this->getSportConfigs(),
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
                $this->getSportConfigs(),
                $this->selfRefereeEnabled()
            );
        }
        return $this->maxNrOfGamesInARow;
    }

    /**
     * @param int|null $state = null
     * @return ArrayCollection<int|string,Planning>
     */
    public function getPlannings(int|null $state = null): ArrayCollection
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
            throw new \Exception('er kan geen planning worden gevonden', E_ERROR);
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
