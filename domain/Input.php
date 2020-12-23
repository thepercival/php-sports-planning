<?php

namespace SportsPlanning;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use SportsHelpers\Range;
use SportsHelpers\SportConfig;
use SportsPlanning\Input\Calculator as InputCalculator;
use SportsHelpers\PouleStructure;

class Input
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var array|int[]
     */
    protected $pouleStructureDb;
    /**
     * @var PouleStructure
     */
    protected $pouleStructure;
    /**
     * @var array[]
     */
    protected $sportConfigDb;
    /**
     * @var array|SportConfig[]
     */
    protected $sportConfigs;
    protected int $gameMode;
    /**
     * @var int
     */
    protected $nrOfReferees;
    /**
     * @var int
     */
    protected $nrOfHeadtohead;
    /**
     * @var int
     */
    protected $selfReferee;
    /**
     * @var int|null
     */
    protected $maxNrOfGamesInARow;
    /**
     * @var DateTimeImmutable
     */
    protected $createdAt;
    /**
     * @var Collection| Planning[]
     */
    protected $plannings;

    public const SELFREFEREE_DISABLED = 0;
    public const SELFREFEREE_OTHERPOULES = 1;
    public const SELFREFEREE_SAMEPOULE = 2;

    const AGAINST_MAXNROFGAMEPLACES = 8;

    /**
     * @param PouleStructure $pouleStructure
     * @param array|SportConfig[] $sportConfigs
     * @param int $gameMode
     * @param int $nrOfReferees
     * @param int $selfReferee
     */
    public function __construct(
        PouleStructure $pouleStructure,
        array $sportConfigs,
        int $gameMode,
        int $nrOfReferees,
        int $selfReferee
    ) {
        $this->setPouleStructure($pouleStructure);
        $this->setSportConfigs($sportConfigs);
        $this->gameMode = $gameMode;
        $nrOfReferees = $selfReferee === self::SELFREFEREE_DISABLED ? $nrOfReferees : 0;
        $this->nrOfReferees = $nrOfReferees;
        $this->selfReferee = $selfReferee;
        $this->plannings = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPouleStructure(): PouleStructure
    {
        if( $this->pouleStructure === null && $this->pouleStructureDb !== null ) {
            $this->pouleStructure = new PouleStructure( $this->pouleStructureDb );
        }
        return $this->pouleStructure;

    }

    public function setPouleStructure(PouleStructure $pouleStructure)
    {
        $this->pouleStructure = $pouleStructure;
        $this->pouleStructureDb = $this->pouleStructure->toArray();
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
     * @return array|SportConfig[]
     */
    public function getSportConfigs(): array
    {
        if( $this->sportConfigs === null && $this->sportConfigDb !== null ) {
            $this->sportConfigs = [];
            foreach ($this->sportConfigDb as $sportConfig) {
                $this->sportConfigs[] = new SportConfig(
                    $sportConfig["nrOfFields"],
                    $sportConfig["nrOfGamePlaces"],
                    $sportConfig["gameAmount"]
                );
            }
        }
        return $this->sportConfigs;

    }

    /**
     * @param array| SportConfig[] $sportConfigs
     */
    public function setSportConfigs(array $sportConfigs)
    {
        $this->sportConfigs = $sportConfigs;
        $this->sportConfigDb = [];
        foreach ($this->sportConfigs as $sportConfig) {
            $this->sportConfigDb[] = $sportConfig->toArray();
        }
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

    public function getGameMode(): int
    {
        return $this->gameMode;
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
        return $this->selfReferee !== self::SELFREFEREE_DISABLED;
    }

    public function getMaxNrOfBatchGames(): int {
        $inputCalculator = new InputCalculator();
        return $inputCalculator->getMaxNrOfGamesPerBatch(
            $this->getPouleStructure(),
            $this->getSportConfigs(),
            $this->selfRefereeEnabled() );
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
                $this->gameMode, $this->getPouleStructure(), $this->getSportConfigs(), $this->selfRefereeEnabled()
            );
        }
        return $this->maxNrOfGamesInARow;
    }

    /**
     * @return Collection|Planning[]
     */
    public function getPlannings( int $state = null ): Collection
    {
        if( $state === null ) {
            return $this->plannings;
        }
        return $this->plannings->filter( function( Planning $planning ) use ($state): bool {
            return ($planning->getState() & $state) > 0;
        });
    }

    /**
     * @param int|null $state
     * @return array|Planning[]
     */
    public function getBatchGamesPlannings( int $state = null ): array {
        $batchGamesPlannings = $this->getPlannings( $state )->filter( function( Planning $planning ): bool {
            return $planning->isBatchGames();
        });
        return $this->orderBatchGamesPlannings( $batchGamesPlannings );
    }

    /**
     * from most most efficient to less efficient
     *
     * @return array|Planning[]
     */
    public function orderBatchGamesPlannings( Collection $batchGamesPlannings ): array
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

    public function getPlanning(Range $range, int $maxNrOfGamesInARow): ?Planning
    {
        foreach ($this->getPlannings() as $planning) {
            if ($planning->getMinNrOfBatchGames() === $range->min
                && $planning->getMaxNrOfBatchGames() === $range->max
                && $planning->getMaxNrOfGamesInARow() === $maxNrOfGamesInARow) {
                return $planning;
            }
        }
        return null;
    }

    public function getBestPlanning(): ?Planning
    {
        $bestBatchGamesPlanning = $this->getBestBatchGamesPlanning();
        if( $bestBatchGamesPlanning === null ) {
            return null;
        }
        return $bestBatchGamesPlanning->getBestGamesInARowPlanning();
    }

    public function getBestBatchGamesPlanning(): ?Planning
    {
        foreach ($this->getBatchGamesPlannings( Planning::STATE_SUCCEEDED ) as $planning) {
            return $planning;
        }
        return null;
    }
}
