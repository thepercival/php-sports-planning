<?php

namespace SportsPlanning\TestHelper;

use SportsPlanning\Batch\SelfReferee as SelfRefereeBatch;
use SportsPlanning\Batch;
use SportsPlanning\Place as PlanningPlace;
use SportsPlanning\Game as PlanningGame;
use SportsPlanning\Field as PlanningField;
use SportsPlanning\Referee as PlanningReferee;
use SportsPlanning\Resource\RefereePlace\Replacer as RefereePlaceReplacer;

trait PlanningReplacer
{
    protected function replaceRefereePlace(
        bool $samePoule,
        SelfRefereeBatch $firstBatch,
        PlanningPlace $replaced,
        PlanningPlace $replacement
    ): void {
        (new RefereePlaceReplacer($samePoule))->replace($firstBatch, $replaced, $replacement);
    }

    protected function replaceField(
        SelfRefereeBatch|Batch $batch,
        PlanningField $replacedField,
        PlanningField $replacedByField,
        int $amount = 1
    ): bool {
        $nextBatch = $batch->getNext();
        if ($nextBatch === null) {
            return false;
        }
        return $this->replaceFieldHelper($nextBatch, $replacedField, $replacedByField, 0, $amount);
    }

    private function replaceFieldHelper(
        SelfRefereeBatch|Batch $batch,
        PlanningField $fromField,
        PlanningField $toField,
        int $amountReplaced,
        int $maxAmount
    ): bool {
        $batchHasToField = $this->hasBatchField($batch, $toField);
        /** @var PlanningGame $game */
        foreach ($batch->getGames() as $game) {
            if ($game->getField() !== $fromField || $batchHasToField) {
                continue;
            }
            $game->setField($toField);
            if (++$amountReplaced === $maxAmount) {
                return true;
            }
        }
        $nextBatch = $batch->getNext();
        if ($nextBatch !== null) {
            return $this->replaceFieldHelper($nextBatch, $fromField, $toField, $amountReplaced, $maxAmount);
        }
        return false;
    }

    protected function hasBatchField(SelfRefereeBatch|Batch $batch, PlanningField $field): bool
    {
        foreach ($batch->getGames() as $game) {
            if ($game->getField() === $field) {
                return true;
            }
        }
        return false;
    }

    protected function replaceReferee(
        SelfRefereeBatch|Batch $batch,
        PlanningReferee $replacedReferee,
        PlanningReferee $replacedByReferee,
        int $amount = 1
    ): bool {
        $nextBatch = $batch->getNext();
        if ($nextBatch === null) {
            return false;
        }
        return $this->replaceRefereeHelper($nextBatch, $replacedReferee, $replacedByReferee, 0, $amount);
    }

    private function replaceRefereeHelper(
        SelfRefereeBatch|Batch $batch,
        PlanningReferee $fromReferee,
        PlanningReferee $toReferee,
        int $amountReplaced,
        int $maxAmount
    ): bool {
        $batchHasToReferee = $this->hasBatchReferee($batch, $toReferee);
        /** @var PlanningGame $game */
        foreach ($batch->getGames() as $game) {
            if ($game->getReferee() !== $fromReferee || $batchHasToReferee) {
                continue;
            }
            $game->setReferee($toReferee);
            if (++$amountReplaced === $maxAmount) {
                return true;
            }
        }
        $nextBatch = $batch->getNext();
        if ($nextBatch !== null) {
            return $this->replaceRefereeHelper(
                $nextBatch,
                $fromReferee,
                $toReferee,
                $amountReplaced,
                $maxAmount
            );
        }
        return false;
    }

    protected function hasBatchReferee(SelfRefereeBatch|Batch $batch, PlanningReferee $referee): bool
    {
        foreach ($batch->getGames() as $game) {
            if ($game->getReferee() === $referee) {
                return true;
            }
        }
        return false;
    }
}
