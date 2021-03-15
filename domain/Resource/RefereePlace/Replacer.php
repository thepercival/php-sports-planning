<?php
declare(strict_types=1);

namespace SportsPlanning\Resource\RefereePlace;

use DateTimeImmutable;
use SportsPlanning\Batch\SelfReferee as SelfRefereeBatch;
use SportsPlanning\Planning;
use SportsPlanning\Place as PlanningPlace;
use SportsPlanning\Resource\GameCounter\Unequal as UnequalGameCounter;
use SportsPlanning\Resource\GameCounter\Unequal as UnequalResource;
use SportsPlanning\Resource\GameCounter;
use SportsPlanning\Resource\GameCounter\Place as PlaceGameCounter;
use SportsPlanning\TimeoutException;
use SportsPlanning\Validator\GameAssignments as GameAssignmentValidator;
use SportsPlanning\Batch\Output as BatchOutput;

class Replacer
{
    protected DateTimeImmutable|null $timeoutDateTime = null;
    /**
     * @var array<Replace>
     */
    protected array $revertableReplaces;
    private bool $throwOnTimeout;

    public function __construct(protected bool $samePoule)
    {
        $this->revertableReplaces = [];
        $this->throwOnTimeout = true;
    }

    public function setTimeoutDateTime(DateTimeImmutable $timeoutDateTime): void
    {
        $this->timeoutDateTime = $timeoutDateTime;
    }

    /**
     * @param Planning $planning
     * @param SelfRefereeBatch $firstBatch
     * @return bool
     */
    public function replaceUnequals(Planning $planning, SelfRefereeBatch $firstBatch): bool
    {
        $gameAssignmentValidator = new GameAssignmentValidator($planning);
        /** @var array|UnequalGameCounter[] $unequals */
        $unequals = $gameAssignmentValidator->getRefereePlaceUnequals();
        if (count($unequals) === 0) {
            return true;
        }
        foreach ($unequals as $unequal) {
            if (!$this->replaceUnequal($firstBatch, $unequal)) {
                $this->revertReplaces();
                return false;
            }
        }
        return $this->replaceUnequals($planning, $firstBatch);
    }

    protected function replaceUnequal(SelfRefereeBatch $firstBatch, UnequalResource $unequal): bool
    {
        return $this->replaceUnequalHelper($firstBatch, $unequal->getMinGameCounters(), $unequal->getMaxGameCounters());
    }

    /**
     * @param SelfRefereeBatch $firstBatch
     * @param array<GameCounter> $minGameCounters
     * @param array<GameCounter> $maxGameCounters
     * @return bool
     */
    protected function replaceUnequalHelper(SelfRefereeBatch $firstBatch, array $minGameCounters, array $maxGameCounters): bool
    {
        if (count($minGameCounters) === 0 || count($maxGameCounters) === 0) {
            return true;
        }

        /** @var PlaceGameCounter $replacedGameCounter */
        foreach ($maxGameCounters as $replacedGameCounter) {
            /** @var PlaceGameCounter $replacementGameCounter */
            foreach ($minGameCounters as $replacementGameCounter) {
                if ($this->throwOnTimeout && (new DateTimeImmutable()) > $this->timeoutDateTime) {
                    throw new TimeoutException(
                        "exceeded timeout while replacing selfreferee",
                        E_ERROR
                    );
                }
                if (!$this->replace(
                    $firstBatch,
                    $replacedGameCounter->getPlace(),
                    $replacementGameCounter->getPlace(),
                )) {
                    continue;
                }
                $removeIndex = array_search($replacedGameCounter, $maxGameCounters, true);
                if ($removeIndex !== false) {
                    array_splice($maxGameCounters, $removeIndex, 1);
                }
                $removeIndex = array_search($replacementGameCounter, $minGameCounters, true);
                if ($removeIndex !== false) {
                    array_splice($minGameCounters, $removeIndex, 1);
                }
                return $this->replaceUnequalHelper($firstBatch, $minGameCounters, $maxGameCounters);
            }
        }
        return false;
    }

    public function replace(
        SelfRefereeBatch $batch,
        PlanningPlace $replaced,
        PlanningPlace $replacement
    ): bool {
        $batchHasReplacement = $batch->getBase()->isParticipating($replacement) || $batch->isParticipatingAsReferee($replacement);
        foreach ($batch->getBase()->getGames() as $game) {
            if ($game->getRefereePlace() !== $replaced || $batchHasReplacement) {
                continue;
            }
            if (($game->getPoule() === $replacement->getPoule() && !$this->samePoule)
                || ($game->getPoule() !== $replacement->getPoule() && $this->samePoule)) {
                continue;
            }
            $replace = new Replace($batch, $game, $replacement);
            if ($this->isAlreadyReplaced($replace)) {
                return false;
            }
            $this->revertableReplaces[] = $replace;
            $batch->removeAsReferee($game->getRefereePlace(), $game);
            $batch->addAsReferee($game, $replacement);
            return true;
        }
        $nextBatch = $batch->getNext();
        if ($nextBatch !== null) {
            return $this->replace($nextBatch, $replaced, $replacement);
        }
        return false;
    }

    protected function isAlreadyReplaced(Replace $replace): bool
    {
        foreach ($this->revertableReplaces as $revertableReplace) {
            if ($revertableReplace->getGame() === $replace->getGame()
                && $revertableReplace->getReplaced() === $replace->getReplaced()
                && $revertableReplace->getReplacement() === $replace->getReplacement()) {
                return true;
            }
        }
        return false;
    }

    protected function revertReplaces(): void
    {
        while (count($this->revertableReplaces) > 0) {
            $replace = array_pop($this->revertableReplaces);
            $replace->getBatch()->removeAsReferee($replace->getReplacement(), $replace->getGame());
            $replace->getBatch()->addAsReferee($replace->getGame(), $replace->getReplaced());
        }
    }

    public function disableThrowOnTimeout(): void
    {
        $this->throwOnTimeout = false;
    }
}
