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
    ) {
        (new RefereePlaceReplacer($samePoule))->replace($firstBatch, $replaced, $replacement);
    }

    /**
     * @param SelfRefereeBatch|Batch $batch
     * @param PlanningField $replacedField
     * @param PlanningField $replacedByField
     * @param int $amount
     * @return bool
     */
    protected function replaceField(
        $batch,
        PlanningField $replacedField,
        PlanningField $replacedByField,
        int $amount = 1
    ): bool {
        return $this->replaceFieldHelper($batch->getNext(), $replacedField, $replacedByField, 0, $amount);
    }

    /**
     * @param SelfRefereeBatch|Batch $batch
     * @param PlanningField $fromField
     * @param PlanningField $toField
     * @param int $amountReplaced
     * @param int $maxAmount
     * @return bool
     */
    private function replaceFieldHelper(
        $batch,
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
        if ($batch->hasNext()) {
            return $this->replaceFieldHelper($batch->getNext(), $fromField, $toField, $amountReplaced, $maxAmount);
        }
        return false;
    }

    /**
     * @param SelfRefereeBatch|Batch $batch
     * @param PlanningField $field
     * @return bool
     */
    protected function hasBatchField($batch, PlanningField $field): bool
    {
        foreach ($batch->getGames() as $game) {
            if ($game->getField() === $field) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param SelfRefereeBatch|Batch $batch
     * @param PlanningReferee $replacedReferee
     * @param PlanningReferee $replacedByReferee
     * @param int $amount
     * @return bool
     */
    protected function replaceReferee(
        $batch,
        PlanningReferee $replacedReferee,
        PlanningReferee $replacedByReferee,
        int $amount = 1
    ): bool {
        return $this->replaceRefereeHelper($batch->getNext(), $replacedReferee, $replacedByReferee, 0, $amount);
    }

    /**
     * @param SelfRefereeBatch|Batch $batch
     * @param PlanningReferee $fromReferee
     * @param PlanningReferee $toReferee
     * @param int $amountReplaced
     * @param int $maxAmount
     * @return bool
     */
    private function replaceRefereeHelper(
        $batch,
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
        if ($batch->hasNext()) {
            return $this->replaceRefereeHelper(
                $batch->getNext(),
                $fromReferee,
                $toReferee,
                $amountReplaced,
                $maxAmount
            );
        }
        return false;
    }

    /**
     * @param SelfRefereeBatch|Batch $batch
     * @param PlanningReferee $referee
     * @return bool
     */
    protected function hasBatchReferee($batch, PlanningReferee $referee): bool
    {
        foreach ($batch->getGames() as $game) {
            if ($game->getReferee() === $referee) {
                return true;
            }
        }
        return false;
    }
}


