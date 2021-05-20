<?php

declare(strict_types=1);

namespace SportsPlanning;

use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Exception;
use SportsHelpers\Identifiable;
use SportsHelpers\SelfReferee;
use SportsHelpers\SportRange;
use SportsPlanning\Batch\SelfReferee\OtherPoule as SelfRefereeOtherPouleBatch;
use SportsPlanning\Batch\SelfReferee\SamePoule as SelfRefereeSamePouleBatch;
use SportsPlanning\Game\Against as AgainstGame;
use SportsPlanning\Game\Together as TogetherGame;

class Planning extends Identifiable
{
    protected int $minNrOfBatchGames;
    protected int $maxNrOfBatchGames;
    protected DateTimeImmutable $createdDateTime;
    protected int $timeoutSeconds;
    protected int $state;
    protected int $nrOfBatches = 0;
    protected int $validity = -1;
    /**
     * @var array<int|string, list<AgainstGame|TogetherGame>>|null
     */
    protected array|null $pouleGamesMap = null;
    /**
     * @phpstan-var ArrayCollection<int|string, AgainstGame>|PersistentCollection<int|string, AgainstGame>
     * @psalm-var ArrayCollection<int|string, AgainstGame>
     */
    protected ArrayCollection|PersistentCollection $againstGames;
    /**
     * @phpstan-var ArrayCollection<int|string, TogetherGame>|PersistentCollection<int|string, TogetherGame>
     * @psalm-var ArrayCollection<int|string, TogetherGame>
     */
    protected ArrayCollection|PersistentCollection $togetherGames;

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

    public function __construct(protected Input $input, SportRange $nrOfBatchGames, protected int $maxNrOfGamesInARow)
    {
        $this->minNrOfBatchGames = $nrOfBatchGames->getMin();
        $this->maxNrOfBatchGames = $nrOfBatchGames->getMax();
        $this->input->getPlannings()->add($this);

        $this->againstGames = new ArrayCollection();
        $this->togetherGames = new ArrayCollection();

        $this->createdDateTime = new DateTimeImmutable();
        $this->timeoutSeconds = $this->getDefaultTimeoutSeconds();
        $this->state = self::STATE_TOBEPROCESSED;
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

    public function getNrOfBatchGames(): SportRange
    {
        return new SportRange($this->getMinNrOfBatchGames(), $this->getMaxNrOfBatchGames());
    }

    public function getMaxNrOfGamesInARow(): int
    {
        return $this->maxNrOfGamesInARow;
    }


    public function isBatchGames(): bool
    {
        return $this->maxNrOfGamesInARow === 0;
    }


    public function getCreatedDateTime(): DateTimeImmutable
    {
        return $this->createdDateTime;
    }

    public function setCreatedDateTime(DateTimeImmutable $createdDateTime): void
    {
        $this->createdDateTime = $createdDateTime;
    }

    public function getTimeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }

    public function setTimeoutSeconds(int $timeoutSeconds): void
    {
        $this->timeoutSeconds = $timeoutSeconds;
    }

    protected function getDefaultTimeoutSeconds(): int
    {
        $defaultTimeoutSecondds = Planning::DEFAULT_TIMEOUTSECONDS;
        $sportVariants = array_values($this->input->createSportVariants()->toArray());
        if ($this->input->createPouleStructure()->getTotalNrOfGames($sportVariants) > 50) {
            $defaultTimeoutSecondds *= 2;
        }
        return $defaultTimeoutSecondds;
    }

    public function getState(): int
    {
        return $this->state;
    }

    public function setState(int $state): void
    {
        $this->state = $state;
    }

    public function getNrOfBatches(): int
    {
        return $this->nrOfBatches;
    }

    public function setNrOfBatches(int $nrOfBatches): void
    {
        $this->nrOfBatches = $nrOfBatches;
    }

    public function getValidity(): int
    {
        return $this->validity;
    }

    public function setValidity(int $validity): void
    {
        $this->validity = $validity;
    }

    public function getInput(): Input
    {
        return $this->input;
    }

    public function createFirstBatch(): Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch
    {
        $games = $this->getGames(Game::ORDER_BY_BATCH);
        $batch = new Batch();
        if ($this->input->selfRefereeEnabled()) {
            if ($this->input->getSelfReferee() === SelfReferee::SAMEPOULE) {
                $batch = new SelfRefereeSamePouleBatch($batch);
            } else {
                $poules = array_values($this->input->getPoules()->toArray());
                $batch = new SelfRefereeOtherPouleBatch($poules, $batch);
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
     * @return list<AgainstGame|TogetherGame>
     */
    public function getGames(int|null $order = null): array
    {
        $games = [];
        foreach ($this->input->getPoules() as $poule) {
            $games = array_merge($games, $this->getGamesForPoule($poule));
        }
        if ($order === Game::ORDER_BY_BATCH) {
            uasort($games, function (Game $g1, Game  $g2): int {
                if ($g1->getBatchNr() === $g2->getBatchNr()) {
                    if( $g1->getField()->getUniqueIndex() === $g2->getField()->getUniqueIndex() ) {
                        return 0;
                    }
                    return $g1->getField()->getUniqueIndex() < $g2->getField()->getUniqueIndex() ? -1 : 1;
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
        return array_values($games);
    }

    /**
     * @return list<AgainstGame|TogetherGame>
     */
    public function getGamesForPoule(Poule $poule): array
    {
        $pouleGamesMap = $this->getPouleGamesMap();
        if(!isset($pouleGamesMap[$poule->getNumber()])) {
            return [];
        }
        return $pouleGamesMap[$poule->getNumber()];
    }

    /**
     * @return array<int|string, list<AgainstGame|TogetherGame>>
     */
    protected function getPouleGamesMap(): array
    {
        if( $this->pouleGamesMap === null ) {
            $this->pouleGamesMap = [];
            $games = array_merge($this->getAgainstGames()->toArray(), $this->getTogetherGames()->toArray());
            foreach ($games as $game) {
                if( !isset($this->pouleGamesMap[$game->getPoule()->getNumber()]) ) {
                    $this->pouleGamesMap[$game->getPoule()->getNumber()] = [];
                }
                array_push($this->pouleGamesMap[$game->getPoule()->getNumber()], $game);
            }
        }
        return $this->pouleGamesMap;
    }

    /**
     * @phpstan-return ArrayCollection<int|string, AgainstGame>|PersistentCollection<int|string, AgainstGame>
     * @psalm-return ArrayCollection<int|string, AgainstGame>
     */
    public function getAgainstGames(): ArrayCollection|PersistentCollection
    {
        return $this->againstGames;
    }

    /**
     * @return list<AgainstGame>
     */
    public function getAgainstGamesForPoule(Poule $poule): array
    {
        $games = $this->getGamesForPoule($poule);
        return array_values(array_filter($games, function($game): bool {
            return $game instanceof AgainstGame;
        }));
    }

    /**
     * @phpstan-return ArrayCollection<int|string, TogetherGame>|PersistentCollection<int|string, TogetherGame>
     * @psalm-return ArrayCollection<int|string, TogetherGame>
     */
    public function getTogetherGames(): ArrayCollection|PersistentCollection
    {
        return $this->togetherGames;
    }

    /**
     * @return list<TogetherGame>
     */
    public function getTogetherGamesForPoule(Poule $poule): array
    {
        $games = $this->getGamesForPoule($poule);
        return array_values(array_filter($games, function($game): bool {
            return $game instanceof TogetherGame;
        }));
    }

    /**
     * @param int|null $state
     * @return list<Planning>
     */
    public function getGamesInARowPlannings(int $state = null): array
    {
        if ($this->getMaxNrOfGamesInARow() > 0) {
            return [];
        }
        $range = $this->getNrOfBatchGames();
        $gamesInARowPlannings = $this->getInput()->getPlannings()->filter(function (Planning $planning) use ($range, $state): bool {
            return $planning->getMinNrOfBatchGames() === $range->getMin()
                && $planning->getMaxNrOfBatchGames() === $range->getMax()
                && $planning->getMaxNrOfGamesInARow() > 0
                && ($state === null || (($planning->getState() & $state) > 0));
        });
        return $this->orderGamesInARowPlannings($gamesInARowPlannings);
    }

    // from most efficient to less efficient
    /**
     * @param Collection<int|string,Planning> $gamesInARowPlannings
     * @return list<Planning>
     */
    protected function orderGamesInARowPlannings(Collection $gamesInARowPlannings): array
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

    public function getBestGamesInARowPlanning(): Planning
    {
        $succeededGamesInARowPlannings = $this->getGamesInARowPlannings(Planning::STATE_SUCCEEDED);
        if (count($succeededGamesInARowPlannings) >= 1) {
            return reset($succeededGamesInARowPlannings);
        }
        if ($this->getState() === Planning::STATE_SUCCEEDED) {
            return $this;
        }
        throw new Exception('er kan geen planning gevonden worden', E_ERROR);
    }
}
