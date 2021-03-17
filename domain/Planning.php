<?php

declare(strict_types=1);

namespace SportsPlanning;

use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use SportsHelpers\Identifiable;
use SportsHelpers\SportRange;
use SportsHelpers\SportConfig;
use SportsHelpers\PouleStructure;

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
    protected int $validity = -1;
    /**
     * @var ArrayCollection<int|string,Poule>
     */
    protected ArrayCollection $poules;
    /**
     * @var ArrayCollection<int|string,Sport>
     */
    protected ArrayCollection $sports;
    /**
     * @var ArrayCollection<int|string,Referee>
     */
    protected ArrayCollection $referees;

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
        $this->initPoules($this->getInput()->getPouleStructure());
        $this->initSports($this->getInput()->getSportConfigs());
        $this->initReferees($this->getInput()->getNrOfReferees());

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

    public function setMaxNrOfGamesInARow(int $maxNrOfGamesInARow): void
    {
        $this->maxNrOfGamesInARow = $maxNrOfGamesInARow;
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

    public function getDefaultTimeoutSeconds(): int
    {
        $defaultTimeoutSecondds = Planning::DEFAULT_TIMEOUTSECONDS;
        if ($this->input->getPouleStructure()->getNrOfGames($this->getInput()->getSportConfigs()) > 50) {
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

    /**
     * @return ArrayCollection<int|string,Poule>
     */
    public function getPoules(): ArrayCollection
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
     * @param PouleStructure $pouleStructure
     * @return void
     */
    protected function initPoules(PouleStructure $pouleStructure): void
    {
        $this->poules = new ArrayCollection();
        foreach ($pouleStructure->toArray() as $nrOfPlaces) {
            $this->poules->add(new Poule($this, $this->poules->count() + 1, $nrOfPlaces));
        }
    }

    public function getPouleStructure(): PouleStructure
    {
        $poules = [];
        foreach ($this->getPoules() as $poule) {
            $poules[] = $poule->getPlaces()->count();
        }
        return new PouleStructure($poules);
    }

    /**
     * @return ArrayCollection<int|string,Sport>
     */
    public function getSports(): ArrayCollection
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
     * @param list<SportConfig> $sportConfigs
     */
    protected function initSports(array $sportConfigs): void
    {
        $fieldNr = 1;
        $this->sports = new ArrayCollection();
        foreach ($sportConfigs as $sportConfig) {
            $sport = new Sport(
                $this,
                $this->sports->count() + 1,
                $sportConfig->getGameMode(),
                $sportConfig->getNrOfGamePlaces(),
                $sportConfig->getGameAmount(),
            );
            $this->sports->add($sport);
            for ($fieldNrDelta = 0 ; $fieldNrDelta < $sportConfig->getNrOfFields() ; $fieldNrDelta++) {
                new Field($sport);
            }
            $fieldNr += $sport->getFields()->count();
        }
    }

    /**
     * @return ArrayCollection<int|string,Field>
     */
    public function getFields(): ArrayCollection
    {
        /** @var ArrayCollection<int|string,Field> $fields */
        $fields = new ArrayCollection();
        foreach ($this->getSports() as $sport) {
            foreach ($sport->getFields() as $field) {
                $fields->add($field);
            }
        }
        return $fields;
    }

    /**
     * @return ArrayCollection<int|string,Referee>
     */
    public function getReferees(): ArrayCollection
    {
        return $this->referees;
    }

    protected function initReferees(int $nrOfReferees): void
    {
        $this->referees = new ArrayCollection();
        for ($refereeNr = 1 ; $refereeNr <= $nrOfReferees ; $refereeNr++) {
            $this->referees->add(new Referee($this, $refereeNr));
        }
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

    public function createFirstBatch(): Batch|SelfRefereeSamePouleBatch|SelfRefereeOtherPouleBatch
    {
        $games = $this->getGames(Game::ORDER_BY_BATCH);
        $batch = new Batch();
        if ($this->getInput()->selfRefereeEnabled()) {
            if ($this->getInput()->getSelfReferee() === SelfReferee::SAMEPOULE) {
                $batch = new SelfRefereeSamePouleBatch($batch);
            } else {
                $poules = array_values($this->getPoules()->toArray());
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
        foreach ($this->getPoules() as $poule) {
            $games = array_merge($games, $poule->getGames());
        }
        if ($order === Game::ORDER_BY_BATCH) {
            uasort($games, function (Game $g1, Game  $g2): int {
                if ($g1->getBatchNr() === $g2->getBatchNr()) {
                    $field1 = $g1->getField();
                    $field2 = $g2->getField();
                    $fieldNr1 = $field1 !== null ? $field1->getNumber() : 0;
                    $fieldNr2 = $field2 !== null ? $field2->getNumber() : 0;
                    return $fieldNr1 - $fieldNr2;
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
     * @return ArrayCollection<int|string,Place>
     */
    public function getPlaces(): ArrayCollection
    {
        /** @var ArrayCollection<int|string,Place> $places */
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
