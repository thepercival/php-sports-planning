<?php

namespace SportsPlanning;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use SportsHelpers\GameCalculator;
use SportsHelpers\Range;
use SportsHelpers\SportConfig as SportConfigHelper;
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
     * @var array|SportConfigHelper[]
     */
    protected $sportConfigHelpers;
    /**
     * @var int
     */
    protected $nrOfReferees;
    /**
     * @var int
     */
    protected $nrOfHeadtohead;
    /**
     * @var bool
     */
    protected $teamup;
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

    const TEAMUP_MIN = 4;
    const TEAMUP_MAX = 6;

    /**
     * @param PouleStructure $pouleStructure
     * @param array|SportConfigHelper[] $sportConfigHelpers
     * @param int $nrOfReferees
     * @param bool $teamup
     * @param int $selfReferee
     * @param int $nrOfHeadtohead
     */
    public function __construct(
        PouleStructure $pouleStructure,
        array $sportConfigHelpers,
        int $nrOfReferees,
        bool $teamup,
        int $selfReferee,
        int $nrOfHeadtohead
    ) {
        $this->setPouleStructure($pouleStructure);
        // $this->structure = $this->convertToStructure( $structureConfig );
        $this->setSportConfigHelpers($sportConfigHelpers);
        // $this->sports = $this->convertToSports( $sportConfig );
        $nrOfReferees = $selfReferee === self::SELFREFEREE_DISABLED ? $nrOfReferees : 0;
        $this->nrOfReferees = $nrOfReferees;
        $this->teamup = $teamup;
        $this->selfReferee = $selfReferee;
        $this->nrOfHeadtohead = $nrOfHeadtohead;
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
     * @return array|SportConfigHelper[]
     */
    public function getSportConfigHelpers(): array
    {
        if( $this->sportConfigHelpers === null && $this->sportConfigDb !== null ) {
            $this->sportConfigHelpers = [];
            foreach ($this->sportConfigDb as $sportConfig) {
                $this->sportConfigHelpers[] = new SportConfigHelper($sportConfig["nrOfFields"], $sportConfig["nrOfGamePlaces"]);
            }
        }
        return $this->sportConfigHelpers;

    }

    /**
     * @param array| SportConfigHelper[] $sportConfigHelpers
     */
    public function setSportConfigHelpers(array $sportConfigHelpers)
    {
        $this->sportConfigHelpers = $sportConfigHelpers;
        $this->sportConfigDb = [];
        foreach ($this->sportConfigHelpers as $sportConfigHelper) {
            $this->sportConfigDb[] = $sportConfigHelper->toArray();
        }
    }

    public function hasMultipleSports(): bool
    {
        return count($this->sportConfigDb) > 1;
    }

    public function getNrOfFields(): int
    {
        $nrOfFields = 0;
        foreach ($this->getSportConfigHelpers() as $sportConfigHelper) {
            $nrOfFields += $sportConfigHelper->getNrOfFields();
        }
        return $nrOfFields;
    }

    public function getNrOfReferees(): int
    {
        return $this->nrOfReferees;
    }

    public function getNrOfHeadtohead(): int
    {
        return $this->nrOfHeadtohead;
    }

    public function getTeamup(): bool
    {
        return $this->teamup;
    }

    public function getSelfReferee(): int
    {
        return $this->selfReferee;
    }

    public function selfRefereeEnabled(): bool
    {
        return $this->selfReferee !== self::SELFREFEREE_DISABLED;
    }

    public function getMaxNrOfBatchGames(int $resources = null): int
    {
        $maxNrOfBatchGames = null;
        if ((Resources::FIELDS & $resources) === Resources::FIELDS || $resources === null) {
            $maxNrOfBatchGames = $this->getNrOfFields();
        }

        if ((Resources::REFEREES & $resources) === Resources::REFEREES || $resources === null) {
            if (!$this->selfRefereeEnabled() && $this->getNrOfReferees() > 0
                && ($this->getNrOfReferees() < $maxNrOfBatchGames || $maxNrOfBatchGames === null)) {
                $maxNrOfBatchGames = $this->getNrOfReferees();
            }
        }

        if ((Resources::PLACES & $resources) === Resources::PLACES || $resources === null) {
            $nrOfGamesSimultaneously = $this->getNrOfGamesSimultaneously();
            if ($nrOfGamesSimultaneously < $maxNrOfBatchGames || $maxNrOfBatchGames === null) {
                $maxNrOfBatchGames = $nrOfGamesSimultaneously;
            }
        }
        return $maxNrOfBatchGames;
    }

    /**
     * sorteer de sporten van zo laag mogelijk NrOfGamePlaces naar zo hoog mogelijk
     * zo wordt $nrOfGamesSimultaneously zo hoog mogelijk
     *
     * @return int
     */
    protected function getNrOfGamesSimultaneously(): int
    {
        $gameCalculator = new GameCalculator();

        // default sort, sportconfig shoud not be altered
//        uasort( $sports, function ( $sportA, $sportB ) {
//            return ($sportA->getNrOfGamePlaces() < $sportB->getNrOfGamePlaces() ) ? -1 : 1;
//        } );

        // $sportConfig = [ [ "nrOfFields" => 3, "nrOfGamePlaces" => 2 ], ];

        $fieldsNrOfGamePlaces = [];
        foreach ($this->getSportConfigHelpers() as $sportConfigHelper) {
            for ($fieldNr = 1; $fieldNr <= $sportConfigHelper->getNrOfFields(); $fieldNr++) {
                $fieldsNrOfGamePlaces[] = $sportConfigHelper->getNrOfGamePlaces();
            }
        }

        // er zijn meerdere poules, dus hier valt ook nog in te verbeteren
        $nrOfPlaces = $this->getNrOfPlaces();

        $nrOfGamesSimultaneously = 0;
        while ($nrOfPlaces > 0 && count($fieldsNrOfGamePlaces) > 0) {
            $nrOfGamePlaces = array_shift($fieldsNrOfGamePlaces);
            $nrOfPlaces -= $gameCalculator->getNrOfGamePlaces(
                $nrOfGamePlaces,
                $this->teamup,
                $this->selfRefereeEnabled()
            );
            if ($nrOfPlaces >= 0) {
                $nrOfGamesSimultaneously++;
            }
        }
        if ($nrOfGamesSimultaneously === 0) {
            $nrOfGamesSimultaneously = 1;
        }
        return $nrOfGamesSimultaneously;
    }

    public function getMaxNrOfGamesInARow(): int
    {
        if ($this->maxNrOfGamesInARow === null) {
            $nrOfPlaces = $this->pouleStructure->getBiggestPoule();

            $this->maxNrOfGamesInARow = (new GameCalculator())->getNrOfGamesPerPlace(
                $nrOfPlaces,
                $this->getTeamup(),
                $this->getSelfReferee() !== self::SELFREFEREE_DISABLED,
                $this->getNrOfHeadtohead()
            );
            if (!$this->getTeamup() && $this->maxNrOfGamesInARow > ($nrOfPlaces * $this->getNrOfHeadtohead())) {
                $this->maxNrOfGamesInARow = $nrOfPlaces * $this->getNrOfHeadtohead();
            }
        }
        return $this->maxNrOfGamesInARow;
        //         const sportPlanningConfigService = new SportPlanningConfigService();
        //         const defaultNrOfGames = sportPlanningConfigService.getNrOfCombinationsExt(this.roundNumber);
        //         const nrOfHeadtothead = nrOfGames / defaultNrOfGames;
        //            $nrOfHeadtohead = 2;
        //            if( $nrOfHeadtohead > 1 ) {
        //                $maxNrOfGamesInARow *= 2;
        //            }
    }

    // should be known when creating input
//    public function getFieldsUsable( RoundNumber $roundNumber, Input $inputPlanning ): array {
//        $maxNrOfFieldsUsable = $inputPlanning->getMaxNrOfFieldsUsable();
//        $fields = $roundNumber->getCompetition()->getFields()->toArray();
//        if( count($fields) > $maxNrOfFieldsUsable ) {
//            return array_splice( $fields, 0, $maxNrOfFieldsUsable);
//        }
//        return $fields;
//    }


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
