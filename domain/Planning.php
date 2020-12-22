<?php

declare(strict_types=1);

namespace SportsPlanning;

use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use SportsHelpers\Range;
use SportsHelpers\SportConfig;
use SportsHelpers\PouleStructure;

use SportsPlanning\Batch\SelfReferee as SelfRefereeBatch;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeOtherPouleBatch;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeSamePouleBatch;
use SportsPlanning\Input as PlanningInput;
use SportsPlanning\Game\AgainstEachOther as AgainstEachOtherGame;
use SportsPlanning\Game\Together as TogetherGame;

class Planning
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var int
     */
    protected $minNrOfBatchGames;
    /**
     * @var int
     */
    protected $maxNrOfBatchGames;
    /**
     * @var int
     */
    protected $maxNrOfGamesInARow;
    /**
     * @var DateTimeImmutable
     */
    protected $createdDateTime;
    /**
     * @var int
     */
    protected $timeoutSeconds;
    /**
     * @var int
     */
    protected $state;
    /**
     * @var int
     */
    protected $validity = -1;
    /**
     * @var Input
     */
    protected $input;
    /**
     * @var Poule[] | Collection
     */
    protected $poules;
    /**
     * @var Sport[] | Collection
     */
    protected $sports;
    /**
     * @var Referee[] | Collection
     */
    protected $referees;

    const STATE_TOBEPROCESSED = 1;
    const STATE_SUCCEEDED = 2;
    const STATE_LESSER_NROFBATCHES_SUCCEEDED = 4;
    const STATE_LESSER_NROFGAMESINROW_SUCCEEDED = 8;
    const STATE_FAILED = 16;
    const STATE_GREATER_NROFBATCHES_FAILED = 32;
    const STATE_GREATER_NROFGAMESINROW_FAILED = 64;
    const STATE_TIMEDOUT = 128;
    const STATE_GREATER_NROFBATCHES_TIMEDOUT = 256;
    const STATE_GREATER_GAMESINAROW_TIMEDOUT = 512;

    const TIMEOUT_MULTIPLIER = 6;
    const DEFAULT_TIMEOUTSECONDS = 5;

    public function __construct(Input $input, Range $nrOfBatchGames, int $maxNrOfGamesInARow)
    {
        $this->input = $input;
        $this->minNrOfBatchGames = $nrOfBatchGames->min;
        $this->maxNrOfBatchGames = $nrOfBatchGames->max;
        $this->maxNrOfGamesInARow = $maxNrOfGamesInARow;
        $this->input->getPlannings()->add($this);
        $this->initPoules($this->getInput()->getPouleStructure());
        $this->initSports($this->getInput()->getSportConfigs());
        $this->initReferees($this->getInput()->getNrOfReferees());

        $this->createdDateTime = new DateTimeImmutable();
        $this->timeoutSeconds = $this->getDefaultTimeoutSeconds();
        $this->state = self::STATE_TOBEPROCESSED;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function minIsMaxNrOfBatchGames(): bool
    {
        return $this->getMinNrOfBatchGames() === $this->getMaxNrOfBatchGames();
    }

    public function getMinNrOfBatchGames(): int
    {
        return $this->minNrOfBatchGames;
    }

//    public function setMinNrOfBatchGames( int $minNrOfBatchGames ) {
//        $this->minNrOfBatchGames = $minNrOfBatchGames;
//    }

    public function getMaxNrOfBatchGames(): int
    {
        return $this->maxNrOfBatchGames;
    }

//    public function setMaxNrOfBatchGames( int $maxNrOfBatchGames ) {
//        $this->maxNrOfBatchGames = $maxNrOfBatchGames;
//    }

    public function getNrOfBatchGames(): Range
    {
        return new Range($this->getMinNrOfBatchGames(), $this->getMaxNrOfBatchGames());
    }

    public function getMaxNrOfGamesInARow(): int
    {
        return $this->maxNrOfGamesInARow;
    }

    public function setMaxNrOfGamesInARow(int $maxNrOfGamesInARow)
    {
        $this->maxNrOfGamesInARow = $maxNrOfGamesInARow;
    }

    public function isBatchGames(): bool {
        return $this->maxNrOfGamesInARow === 0;
    }


    public function getCreatedDateTime(): DateTimeImmutable
    {
        return $this->createdDateTime;
    }

    public function setCreatedDateTime(DateTimeImmutable $createdDateTime)
    {
        $this->createdDateTime = $createdDateTime;
    }

    public function getTimeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }

    public function setTimeoutSeconds(int $timeoutSeconds)
    {
        $this->timeoutSeconds = $timeoutSeconds;
    }

    public function getDefaultTimeoutSeconds(): int {
        $defaultTimeoutSecondds = Planning::DEFAULT_TIMEOUTSECONDS;
        $gameMode = $this->getInput()->getGameMode();
        if( $this->input->getPouleStructure()->getNrOfGames( $gameMode, $this->getInput()->getSportConfigs() ) > 50 ) {
            $defaultTimeoutSecondds *= 2;
        }
        return $defaultTimeoutSecondds;
    }

    public function getState(): int
    {
        return $this->state;
    }

    public function setState(int $state)
    {
        $this->state = $state;
    }

    public function getValidity(): int
    {
        return $this->validity;
    }

    public function setValidity(int $validity)
    {
        $this->validity = $validity;
    }

    public function getInput(): Input
    {
        return $this->input;
    }

    /**
     * @return Collection|Poule[]
     */
    public function getPoules(): Collection
    {
        return $this->poules;
    }

    public function getPoule(int $pouleNr): ?Poule
    {
        foreach ($this->getPoules() as $poule) {
            if ($poule->getNumber() === $pouleNr) {
                return $poule;
            }
        }
        return null;
    }

    /**
     * @param PouleStructure $pouleStructure
     */
    protected function initPoules(PouleStructure $pouleStructure)
    {
        $this->poules = new ArrayCollection();
        foreach ($pouleStructure->toArray() as $nrOfPlaces) {
            $this->poules->add(new Poule($this, $this->poules->count() + 1, $nrOfPlaces));
        }
    }

    public function getPouleStructure(): PouleStructure
    {
        $poules = [];
        foreach ($this->getPoules() as $poule ) {
            $poules[] = $poule->getPlaces()->count();
        }
        return new PouleStructure($poules);
    }

    /**
     * @return Collection|Sport[]
     */
    public function getSports(): Collection
    {
        return $this->sports;
    }

    /**
     * @param array|SportConfig[] $sportConfigs
     */
    protected function initSports(array $sportConfigs)
    {
        $fieldNr = 1;
        $this->sports = new ArrayCollection();
        foreach ($sportConfigs as $sportConfig) {
            $sport = new Sport(
                $this,
                $this->sports->count() + 1,
                $sportConfig->getNrOfGamePlaces() );
            $this->sports->add($sport);
            for ($fieldNrDelta = 0 ; $fieldNrDelta < $sportConfig->getNrOfFields() ; $fieldNrDelta++) {
                new Field($fieldNr + $fieldNrDelta, $sport);
            }
            $fieldNr += $sport->getFields()->count();
        }
    }

    public function getFields(): ArrayCollection
    {
        $fields = new ArrayCollection();
        foreach ($this->getSports() as $sport) {
            foreach ($sport->getFields() as $field) {
                $fields->add($field);
            }
        }
        return $fields;
    }
        
    public function getField(int $fieldNr): ?Field
    {
        foreach ($this->getFields() as $field) {
            if ($field->getNumber() === $fieldNr) {
                return $field;
            }
        }
        return null;
    }

    /**
     * @return Referee[] | Collection
     */
    public function getReferees(): Collection
    {
        return $this->referees;
    }

    protected function initReferees(int $nrOfReferees)
    {
        $this->referees = new ArrayCollection();
        for ($refereeNr = 1 ; $refereeNr <= $nrOfReferees ; $refereeNr++) {
            $this->referees->add(new Referee($this, $refereeNr));
        }
    }

    public function getReferee(int $refereeNr): ?Referee
    {
        foreach ($this->getReferees() as $referee) {
            if ($referee->getNumber() === $refereeNr) {
                return $referee;
            }
        }
        return null;
    }

    /**
     * @return Batch|SelfRefereeBatch
     */
    public function createFirstBatch()
    {
        $games = $this->getGames(Game::ORDER_BY_BATCH);
        $batch = new Batch();
        if( $this->getInput()->selfRefereeEnabled() ) {
            if( $this->getInput()->getSelfReferee() === PlanningInput::SELFREFEREE_SAMEPOULE) {
                $batch = new SelfRefereeSamePouleBatch( $batch );
            } else {
                $batch = new SelfRefereeOtherPouleBatch( $this->getPoules()->toArray(), $batch );
            }
        }
        foreach ($games as $game) {
            if ($game->getBatchNr() === ($batch->getNumber() + 1)) {
                $batch = $batch->createNext();
            }
            $batch->add($game);
        }
        return $batch->getFirst();
    }

    /**
     * @param int|null $order
     * @return array|AgainstEachOtherGame[]|TogetherGame[]
     */
    public function getGames(int $order = null): array
    {
        $games = [];
        foreach ($this->getPoules() as $poule) {
            $games = array_merge($games, $poule->getGames()->toArray());
        }
        if ($order === Game::ORDER_BY_BATCH) {
            uasort($games, function (Game $g1, Game  $g2): int {
                if ($g1->getBatchNr() === $g2->getBatchNr()) {
                    return $g1->getField()->getNumber() - $g2->getField()->getNumber();
                }
                return $g1->getBatchNr() - $g2->getBatchNr();
            });
        } /*elseif ($order === Game::ORDER_BY_GAMENUMBER) {
            uasort($games, function (Game $g1, Game $g2): int {
                if ($g1->getRoundNr() !== $g2->getRoundNr()) {
                    return $g1->getRoundNr() - $g2->getRoundNr();
                }
                if ($g1->getSubNr() !== $g2->getSubNr()) {
                    return $g1->getSubNr() - $g2->getSubNr();
                }
                return $g1->getPoule()->getNumber() - $g2->getPoule()->getNumber();
            });
        }*/
        return $games;
    }

    public function getPlaces(): ArrayCollection
    {
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
        $pouleNr = (int)substr($location, 0, strpos($location, "."));
        $placeNr = (int)substr($location, strpos($location, ".") + 1);
        return $this->getPoule($pouleNr)->getPlace($placeNr);
    }

    public function getGamesInARowPlannings( int $state = null ): array {
        if( $this->getMaxNrOfGamesInARow() > 0 ) {
            return [];
        }
        $range = $this->getNrOfBatchGames();
        $gamesInARowPlannings = $this->getInput()->getPlannings()->filter( function( Planning $planning ) use ($range, $state): bool {
            return $planning->getMinNrOfBatchGames() === $range->min
                && $planning->getMaxNrOfBatchGames() === $range->max
                && $planning->getMaxNrOfGamesInARow() > 0
                && ($state === null || (($planning->getState() & $state) > 0) );
        });
        return $this->orderGamesInARowPlannings( $gamesInARowPlannings );
    }

    /**
     * from most efficient to less efficient
     *
     * @return array|Planning[]
     */
    protected function orderGamesInARowPlannings( Collection $gamesInARowPlannings ): array
    {
        $plannings = $gamesInARowPlannings->toArray();
        uasort($plannings, function (Planning $first, Planning $second) {
            if ($first->getMaxNrOfGamesInARow() === $second->getMaxNrOfGamesInARow()) {
                return $first->getNrOfBatchGames()->difference() > $second->getNrOfBatchGames()->difference() ? -1 : 1;
            }
            return $first->getMaxNrOfGamesInARow() < $second->getMaxNrOfGamesInARow() ? -1 : 1;
        });
        return array_values($plannings);
    }

    public function getBestGamesInARowPlanning(): ?Planning {
        $succeededGamesInARowPlannings = $this->getGamesInARowPlannings( Planning::STATE_SUCCEEDED );
        if( count( $succeededGamesInARowPlannings ) >= 1 ) {
            return reset($succeededGamesInARowPlannings);
        }
        if( $this->getState() === Planning::STATE_SUCCEEDED ) {
            return $this;
        }
        return null;
    }
}
