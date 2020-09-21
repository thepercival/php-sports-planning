<?php


namespace SportsPlanning\Resource\RefereePlace;

use SportsPlanning\Batch\SelfReferee as SelfRefereeBatch;
use SportsPlanning\Planning;
use SportsPlanning\Game as PlanningGame;
use SportsPlanning\Place as PlanningPlace;
use SportsPlanning\Resource\GameCounter\Unequal as UnequalGameCounter;
use SportsPlanning\Resource\GameCounter\Unequal as UnequalResource;
use SportsPlanning\Resource\GameCounter;
use SportsPlanning\Resource\GameCounter\Place as PlaceGameCounter;
use SportsPlanning\Validator\GameAssignments as GameAssignmentValidator;

class Replacer
{
    protected bool $samePoule;

    /**
     * @var array | Replace[]
     */
    protected array $revertableReplaces;

    public function __construct(bool $samePoule)
    {
        $this->samePoule = $samePoule;
        $this->revertableReplaces = [];
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
     * @param array|GameCounter[] $minGameCounters
     * @param array|GameCounter[] $maxGameCounters
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
                if (!$this->replace(
                    $firstBatch,
                    $replacedGameCounter->getPlace(),
                    $replacementGameCounter->getPlace(),
                )) {
                    continue;
                }
                array_splice($maxGameCounters, array_search($replacedGameCounter, $maxGameCounters, true), 1);
                array_splice($minGameCounters, array_search($replacementGameCounter, $minGameCounters, true), 1);
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
        /** @var PlanningGame $game */
        foreach ($batch->getBase()->getGames() as $game) {
            if ($game->getRefereePlace() !== $replaced || $batchHasReplacement) {
                continue;
            }
            if (($game->getPoule() === $replacement->getPoule() && !$this->samePoule)
                || ($game->getPoule() !== $replacement->getPoule() && $this->samePoule)) {
                continue;
            }
            $replace = new Replace($game, $replacement);
            if ($this->isAlreadyReplaced($replace)) {
                return false;
            }
            $this->revertableReplaces[] = $replace;
            $game->setRefereePlace($replacement);
            return true;
        }
        if ($batch->hasNext()) {
            return $this->replace($batch->getNext(), $replaced, $replacement);
        }
        return false;
    }

    protected function isAlreadyReplaced(Replace $replace)
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

    protected function revertReplaces()
    {
        while (count($this->revertableReplaces) > 0) {
            $replace = array_pop($this->revertableReplaces);
            $replace->getGame()->setRefereePlace($replace->getReplaced());
        }
    }
}