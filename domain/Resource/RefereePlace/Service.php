<?php

namespace SportsPlanning\Resource\RefereePlace;

use DateTimeImmutable;
use SportsPlanning\Output\Batch as BatchOutput;
use SportsPlanning\Planning;
use SportsPlanning\Game;
use SportsPlanning\Place;
use SportsPlanning\Input;
use SportsPlanning\Batch\SelfReferee as SelfRefereeBatch;
use SportsPlanning\Resource\GameCounter\Place as PlaceGameCounter;
use SportsPlanning\TimeoutException;

class Service
{
    /**
     * @var Planning
     */
    private $planning;
    /**
     * @var int
     */
    protected $nrOfPlaces;
    /**
     * @var array
     */
    // private $canBeSamePoule;
    /**
     * @var Replacer
     */
    private $replacer;

    protected const TIMEOUTSECONDS = 60;

    public function __construct(Planning $planning)
    {
        $this->planning = $planning;
        $this->nrOfPlaces = $this->planning->getPouleStructure()->getNrOfPlaces();
        $this->replacer = new Replacer($planning->getInput()->getSelfReferee() === Input::SELFREFEREE_SAMEPOULE);
    }

    protected function getInput(): Input
    {
        return $this->planning->getInput();
    }

    public function assign(SelfRefereeBatch $batch): bool
    {
        if (!$this->getInput()->selfRefereeEnabled()) {
            return true;
        }
        if ($this->assignHelper($batch)) {
            return true;
        }
        return false;
    }

    public function assignHelper(SelfRefereeBatch $batch): bool
    {
        $timeoutDateTime = (new DateTimeImmutable())->modify("+" . static::TIMEOUTSECONDS . " seconds");
        $refereePlaces = $this->getRefereePlaces();
        try {
            if ($this->assignBatch($batch, $batch->getBase()->getGames(), $refereePlaces, $timeoutDateTime)) {
                return true;
            };
        } catch (TimeoutException $timeoutExc) {
        }
        return false;
    }

    /**
     * @return array|PlaceGameCounter[]
     */
    protected function getRefereePlaces(): array
    {
        $refereePlaces = [];
        foreach ($this->planning->getPlaces() as $place) {
            $gameCounter = new PlaceGameCounter($place);
            $refereePlaces[$gameCounter->getIndex()] = $gameCounter;
        }
        return $refereePlaces;
    }

    /**
     * @param SelfRefereeBatch $batch
     * @param array|Game[] $batchGames
     * @param array|PlaceGameCounter[] $refereePlaces
     * @param DateTimeImmutable $timeoutDateTime
     * @return bool
     * @throws TimeoutException
     */
    protected function assignBatch(
        SelfRefereeBatch $batch,
        array $batchGames,
        array $refereePlaces,
        DateTimeImmutable $timeoutDateTime
    ): bool {
        if (count($batchGames) === 0) { // batchsuccess
            if ($batch->hasNext() === false) { // endsuccess
                return $this->equallyAssign($batch);
            }
            if ((new DateTimeImmutable()) > $timeoutDateTime) { // @FREDDY
                throw new TimeoutException(
                    "exceeded maximum duration of " . static::TIMEOUTSECONDS . " seconds",
                    E_ERROR
                );
            }
            $nextBatch = $batch->getNext();
            return $this->assignBatch($nextBatch, $nextBatch->getBase()->getGames(), $refereePlaces, $timeoutDateTime);
        }

        $game = array_shift($batchGames);
        /** @var PlaceGameCounter $refereePlace */
        foreach ($refereePlaces as $refereePlace) {
            if ($this->isRefereePlaceAssignable($batch, $game, $refereePlace->getPlace())) {
                $newRefereePlaces = $this->assignRefereePlace($batch, $game, $refereePlace->getPlace(), $refereePlaces);
                if ($this->assignBatch($batch, $batchGames, $newRefereePlaces, $timeoutDateTime)) {
                    return true;
                }
                // statics
                $game->emptyRefereePlace();
                $batch->removeAsReferee($refereePlace->getPlace());
            }
        }
        return false;
    }

    protected function equallyAssign(SelfRefereeBatch $batch): bool
    {
        return $this->replacer->replaceUnequals($this->planning, $batch->getFirst());
    }

    private function isRefereePlaceAssignable(SelfRefereeBatch $batch, Game $game, Place $refereePlace): bool
    {
        if ($batch->getBase()->isParticipating($refereePlace) || $batch->isParticipatingAsReferee($refereePlace)) {
            return false;
        }
        if ($this->planning->getInput()->getSelfReferee() === Input::SELFREFEREE_SAMEPOULE) {
            return $refereePlace->getPoule() === $game->getPoule();
        }
//        if (array_key_exists($batch->getNumber(), $this->canBeSamePoule)
//            && $this->canBeSamePoule[$batch->getNumber()] === $refereePlace->getPoule()) {
//            return true;
//        }
        return $refereePlace->getPoule() !== $game->getPoule();
    }

    /**
     * @param SelfRefereeBatch $batch
     * @param Game $game
     * @param Place $assignPlace
     * @param array|PlaceGameCounter[] $refereePlaces
     * @return array|PlaceGameCounter[]
     */
    private function assignRefereePlace(SelfRefereeBatch $batch, Game $game, Place $assignPlace, array $refereePlaces): array
    {
        $batch->addAsReferee($assignPlace);
        $game->setRefereePlace($assignPlace);

        $newRefereePlaces = [];
        foreach ($refereePlaces as $refereePlace) {
            $place = $refereePlace->getPlace();
            $newRefereePlace = new PlaceGameCounter($place, $refereePlace->getNrOfGames());
            $newRefereePlaces[$newRefereePlace->getIndex()] = $newRefereePlace;
            if ($place === $assignPlace) {
                $newRefereePlace->increase();
            }
        }
        uasort(
            $newRefereePlaces,
            function (PlaceGameCounter $a, PlaceGameCounter $b): int {
                return $a->getNrOfGames() < $b->getNrOfGames() ? -1 : 1;
            }
        );
        return $newRefereePlaces;
    }
}
