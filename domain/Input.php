<?php

namespace SportsPlanning;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use SportsHelpers\Range;
use SportsHelpers\SportConfig as SportConfigHelper;

class Input
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var array|int[]
     */
    protected $structureConfig;
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
     * @var int
     */
    protected $state;
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

    public const STATE_CREATED = 1;
    public const STATE_TRYING_PLANNINGS = 2;
    public const STATE_UPDATING_BESTPLANNING_SELFREFEE = 4;
    public const STATE_ALL_PLANNINGS_TRIED = 8;

    public const SELFREFEREE_DISABLED = 0;
    public const SELFREFEREE_OTHERPOULES = 1;
    public const SELFREFEREE_SAMEPOULE = 2;

    const TEAMUP_MIN = 4;
    const TEAMUP_MAX = 6;

    /**
     * Input constructor.
     * @param array|int[] $structureConfig
     * @param array|SportConfigHelper[] $sportConfigHelpers
     * @param int $nrOfReferees
     * @param bool $teamup
     * @param int $selfReferee
     * @param int $nrOfHeadtohead
     */
    public function __construct(
        array $structureConfig,
        array $sportConfigHelpers,
        int $nrOfReferees,
        bool $teamup,
        int $selfReferee,
        int $nrOfHeadtohead
    ) {
        $this->structureConfig = $structureConfig;
        // $this->structure = $this->convertToStructure( $structureConfig );
        $this->sportConfigHelpers = $this->setSportConfigHelpers($sportConfigHelpers);
        // $this->sports = $this->convertToSports( $sportConfig );
        $this->nrOfReferees = $nrOfReferees;
        $this->teamup = $teamup;
        $this->selfReferee = $selfReferee;
        $this->nrOfHeadtohead = $nrOfHeadtohead;
        $this->state = Input::STATE_CREATED;
        $this->plannings = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * $structure = [ 6, 6, 5 ];
     *
     * @return array|int[]
     */
    public function getStructureConfig(): array
    {
        return $this->structureConfig;
    }

    public function getNrOfPoules(): int
    {
        return count($this->getStructureConfig());
    }

    public function getNrOfPlaces(): int
    {
        $nrOfPlaces = 0;
        foreach ($this->getStructureConfig() as $nrOfPlacesIt) {
            $nrOfPlaces += $nrOfPlacesIt;
        }
        return $nrOfPlaces;
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

    public function getState(): int
    {
        return $this->state;
    }

    public function setState(int $state)
    {
        $this->state = $state;
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
        $helper = new \SportsPlanning\HelperTmp();

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
            $nrOfPlaces -= $helper->getNrOfGamePlaces(
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
            $structureConfig = $this->getStructureConfig();
            $nrOfPlaces = reset($structureConfig);

            $this->maxNrOfGamesInARow = (new HelperTmp())->getNrOfGamesPerPlace(
                $nrOfPlaces,
                $this->getTeamup(),
                $this->getSelfReferee(),
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
    public function getPlannings(): Collection
    {
        return $this->plannings;
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

    public function addPlanning(Planning $planning)
    {
        $this->getPlannings()->add($planning);
    }
}
