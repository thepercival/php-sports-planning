<?php
/**
 * Created by PhpStorm->
 * User: coen
 * Date: 9-3-18
 * Time: 11:55
 */

namespace SportsPlanning\Resource;

use DateTimeImmutable;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
use Monolog\Logger;

use SportsPlanning\HelperTmp;
use SportsPlanning\Planning;
use SportsPlanning\Game;
use SportsPlanning\Input as PlanningInput;
use SportsPlanning\Place;
use SportsPlanning\Sport;
use SportsPlanning\Resources as Resources;
use SportsPlanning\Input;
use SportsPlanning\Sport\Counter as SportCounter;
use SportsPlanning\Sport\NrFields as SportNrFields;
use SportsPlanning\Batch;
use SportsPlanning\Output\Batch as BatchOutput;
use SportsPlanning\Batch\RefereePlacePredicter;
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
    private $nrOfPoules;
    /**
     * @var int
     */
    private $nrOfSports;
    /**
     * @var bool
     */
    private $tryShuffledFields = false;
    /**
     * @var array|Place[]
     */
    private $places;
    /**
     * @var DateTimeImmutable
     */
    private $timeoutDateTime;
    /**
     * @var RefereePlacePredicter
     */
    private $refereePlacePredicter;

    /**
     * @var BatchOutput
     */
    protected $batchOutput;

    protected $debugIterations;

    public function __construct(Planning $planning)
    {
        $this->planning = $planning;
        $this->nrOfPoules = $this->planning->getPoules()->count();
        $this->refereePlacePredicter = new RefereePlacePredicter($this->planning->getPoules());

//        $logger = new Logger('eded');
//        $processor = new UidProcessor();
//        $logger->pushProcessor($processor);
//        $path = 'php://stdout';
//        $handler = new StreamHandler($path);
//        $logger->pushHandler($handler);
//        $this->batchOutput = new BatchOutput($logger);
//        $outputPlanning = new PlanningOutput();
//        $outputPlanning->output($planning, true );
    }

    protected function getInput(): Input
    {
        return $this->planning->getInput();
    }

    protected function setLogger(LoggerInterface $logger)
    {
        $this->batchOutput = new BatchOutput($logger);
    }

    protected function init()
    {
        if ($this->planning->getInput()->hasMultipleSports()) {
            $this->tryShuffledFields = true;
        }
    }

    /**
     *
     */
    protected function getSportCounters(): array
    {
        $sports = $this->planning->getSports()->toArray();
        $teamup = $this->getInput()->getTeamup();
        $selfReferee = $this->getInput()->getSelfReferee();
        $nrOfHeadtohead = $this->getInput()->getNrOfHeadtohead();

        $sportsNrFields = $this->convertSports($sports);
        $nrOfGamesDoneMap = [];
        foreach ($sportsNrFields as $sportNrFields) {
            $nrOfGamesDoneMap[$sportNrFields->getSportNr()] = 0;
        }

        $sportCounters = [];
        foreach ($this->planning->getPoules() as $poule) {
            $pouleNrOfPlaces = $poule->getPlaces()->count();
            $nrOfGamesToGo = (new HelperTmp())->getNrOfGamesPerPlace(
                $pouleNrOfPlaces,
                $teamup,
                PlanningInput::SELFREFEREE_DISABLED,
                $nrOfHeadtohead
            );

            // $sportsNrFieldsGames = $sportService->getPlanningMinNrOfGames($sportsNrFields, $pouleNrOfPlaces, $teamup, $selfReferee, $nrOfHeadtohead );
            // hier moet de $sportsNrFieldsGames puur berekent worden op basis van aantal sporten
            $minNrOfGamesMap = $this->convertToMap($sportsNrFields/*$sportsNrFieldsGames*/);
            /** @var Place $placeIt */
            foreach ($poule->getPlaces() as $placeIt) {
                $sportCounters[$placeIt->getLocation()] = new SportCounter(
                    $nrOfGamesToGo,
                    $minNrOfGamesMap,
                    $nrOfGamesDoneMap
                );
            }
        }
        return $sportCounters;
    }

    /**
     * @param array $sports |Sport[]
     * @return array|SportNrFields[]
     */
    protected function convertSports(array $sports): array
    {
        return array_map(
            function (Sport $sport): SportNrFields {
                return new SportNrFields(
                    $sport->getNumber(),
                    $sport->getFields()->count(),
                    $sport->getNrOfGamePlaces()
                );
            },
            $sports
        );
    }

    /**
     * @param array|SportNrFields[] $sportsNrFields
     * @return array
     */
    protected function convertToMap(array $sportsNrFields): array
    {
        $minNrOfGamesMap = [];
        /** @var SportNrFields $sportNrFields */
        foreach ($sportsNrFields as $sportNrFields) {
            $minNrOfGamesMap[$sportNrFields->getSportNr()] = $sportNrFields->getNrOfFields();
        }
        return $minNrOfGamesMap;
    }

    public function assign(array $games): int
    {
        $this->debugIterations = 0;
        $oCurrentDateTime = new DateTimeImmutable();
        $this->timeoutDateTime = $oCurrentDateTime->modify("+" . $this->planning->getTimeoutSeconds() . " seconds");
        $this->init();
        $batch = new Batch();

        try {
            $fields = $this->planning->getFields()->toArray();

            $resources = new Resources($fields/*, $this->getSportCounters()*/);
            $batch = $this->assignBatch($games, $resources, $batch);
            if ($batch === null) {
                return Planning::STATE_FAILED;
            }

            $firstBatch = $batch->getFirst();
            $refereeService = new RefereeService($this->planning);
            $refereeService->assign($firstBatch);

//            if( $this->batchOutput !== null ) {
////            $mem = $this->convert(memory_get_usage(true)); // 123 kb
////            $this->batchOutput->output($firstBatch, ' final (' . ($this->debugIterations) . ' : ' . $mem . ')');
//            }

        } catch (TimeoutException $e) {
            return Planning::STATE_TIMEOUT;
        }
        return Planning::STATE_SUCCESS;
    }

    /**
     * @param array $games
     * @param Resources $resources
     * @param Batch $batch
     * @return Batch|null
     * @throws TimeoutException
     */
    protected function assignBatch(array $games, Resources $resources, Batch $batch): ?Batch
    {
//        if( $this->batchOutput !== null ) { // before assigned
//            $this->batchOutput->outputGames( $games ); die();
//        }

        if ($this->assignBatchHelper($games, $games, $resources, $batch, $this->planning->getMaxNrOfBatchGames())) {
            return $this->getActiveLeaf($batch->getLeaf());
        }
        return null;
    }

    protected function getActiveLeaf(Batch $batch): Batch
    {
        if ($batch->hasPrevious() === false) {
            return $batch;
        }
        if (count($batch->getPrevious()->getGames()) === $this->planning->getMaxNrOfBatchGames()) {
            return $batch;
        }
        return $this->getActiveLeaf($batch->getPrevious());
    }

    //// uasort( $games, function( Game $gameA, Game $gameB ) use ( $continueResources6 ) {
    ////                $this->outputBatch->outputGames( $gameA, null, 'gameA: ' );
    ////                $this->outputBatch->outputGames( $gameB, null, 'gameB: ' );
//                $nrOfSportsToGoA = $continueResources6->getGameNrOfSportsToGo($gameA);
//                $nrOfSportsToGoB = $continueResources6->getGameNrOfSportsToGo($gameB);
//                return $nrOfSportsToGoA >= $nrOfSportsToGoB ? -1 : 1;
//            });

    /**
     * @param array $games
     * @param Resources $resources
     * @param Batch $batch
     * @param int $nrOfGamesTried
     * @return bool
     * @throws TimeoutException
     */
    protected function assignBatchHelper(
        array $games,
        array $gamesForBatch,
        Resources $resources,
        Batch $batch,
        int $maxNrOfBatchGames,
        int $nrOfGamesTried = 0
    ): bool
    {
        if ((count($batch->getGames()) === $maxNrOfBatchGames
                || (count($gamesForBatch) === 0) && count($games) === count($batch->getGames()))
            && $this->refereePlacesCanBeAssigned($batch)
        ) { // batchsuccess
            $nextBatch = $this->toNextBatch($batch, $resources, $games);

//            if( $this->batchOutput !== null ) { // before assigned
//                $this->batchOutput->output($batch, ' batch completed nr ' . $batch->getNumber() );
//            }
            // update planninginputs set state = 1 where state = 2;
            // delete from plannings where inputId = 26178;
            if (count($gamesForBatch) === 0 && count($games) === 0) { // endsuccess
                return true;
            }
            $gamesForBatchTmp = array_filter(
                $games,
                function (Game $game) use ($nextBatch): bool {
                    return $this->areAllPlacesAssignableByGamesInARow($nextBatch, $game);
                }
            );
//            if( count($gamesForBatchTmp) === 0 ) {
//                return false;
//            }
            return $this->assignBatchHelper($games, $gamesForBatchTmp, $resources, $nextBatch, $maxNrOfBatchGames, 0);
        }
        if ((new DateTimeImmutable()) > $this->timeoutDateTime) { // @FREDDY
            throw new TimeoutException(
                "exceeded maximum duration of " . $this->planning->getTimeoutSeconds() . " seconds", E_ERROR
            );
        }
        if ($nrOfGamesTried === count($gamesForBatch)) {
            return false;
        }
//        $this->debugIterations++;
//        echo "iteration " . $this->debugIterations . " (27489) (".$this->convert(memory_get_usage(true))." / ".ini_get('memory_limit').")" . PHP_EOL;

        $game = array_shift($gamesForBatch);
        if ($this->isGameAssignable($batch, $game, $resources)) {
            $resourcesAssign = $resources->copy();
            $this->assignGame($batch, $game, $resourcesAssign);
            $gamesForBatchTmp = array_filter(
                $gamesForBatch,
                function (Game $game) use ($batch): bool {
                    return $this->areAllPlacesAssignable($batch, $game);
                }
            );
            if ($this->assignBatchHelper($games, $gamesForBatchTmp, $resourcesAssign, $batch, $maxNrOfBatchGames)) {
                return true;
            }
            $this->releaseGame($batch, $game);
        }
        $gamesForBatch[] = $game;
        if ($this->assignBatchHelper(
            $games,
            $gamesForBatch,
            $resources->copy(),
            $batch,
            $maxNrOfBatchGames,
            ++$nrOfGamesTried
        )) {
            return true;
        }

//        $resourcesSwitchFields = $resources->copy();
//        while ($resourcesSwitchFields->switchFields()) {
//            if ($this->assignBatchHelper($games, $gamesForBatch, $resourcesSwitchFields, $batch, $maxNrOfBatchGames)) {
//                return true;
//            }
//        }

        if ($maxNrOfBatchGames === $this->planning->getMaxNrOfBatchGames() && $this->planning->getNrOfBatchGames(
            )->difference() > 0) {
            if ($this->assignBatchHelper($games, $gamesForBatch, $resources->copy(), $batch, $maxNrOfBatchGames - 1)) {
                return true;
            }
        }
        return false;
    }

    protected function convert($size)
    {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

    protected function assignGame(Batch $batch, Game $game, Resources $resources)
    {
        $this->assignField($game, $resources);
        $batch->add($game);
        $resources->assignSport($game, $game->getField()->getSport());
    }

    protected function releaseGame(Batch $batch, Game $game)
    {
        $batch->remove($game);
        // $this->releaseSport($game, $game->getField()->getSport());
        // $this->releaseField($game);
    }

    /**
     * @param Batch $batch
     * @param Resources $resources
     * @return Batch
     */
    protected function toNextBatch(Batch $batch, Resources $resources, array &$games): Batch
    {
        foreach ($batch->getGames() as $game) {
            $game->setBatchNr($batch->getNumber());
            // hier alle velden toevoegen die er nog niet in staan
            if (array_search($game->getField(), $resources->getFields(), true) === false) {
                $resources->addField($game->getField());
            }
            $foundGameIndex = array_search($game, $games, true);
            if ($foundGameIndex !== false) {
                unset($games[$foundGameIndex]);
            }
        }
        $nextBatch = $batch->createNext();

        return $nextBatch;
    }

    private function isGameAssignable(Batch $batch, Game $game, Resources $resources): bool
    {
        if (!$this->isSomeFieldAssignable($game, $resources)) {
            return false;
        }
        return $this->areAllPlacesAssignable($batch, $game);
    }

    /**
     * de wedstrijd is assignbaar als
     * 1 alle plekken, van een wedstrijd, nog niet in de batch
     * 2 alle plekken, van een wedstrijd, de sport nog niet vaak genoeg gedaan heeft of alle sporten al gedaan
     *
     * @param Batch $batch
     * @param Game $game
     * @return bool
     */
    private function areAllPlacesAssignable(Batch $batch, Game $game, bool $checkGamesInARow = true): bool
    {
        $maxNrOfGamesInARow = $this->getInput()->getMaxNrOfGamesInARow();
        foreach ($this->getPlaces($game) as $place) {
            if ($batch->isParticipating($place)) {
                return false;
            }
            $nrOfGamesInARow = $batch->hasPrevious() ? ($batch->getPrevious()->getGamesInARow($place)) : 0;
            if ($nrOfGamesInARow < $maxNrOfGamesInARow || $maxNrOfGamesInARow === -1) {
                continue;
            }
            return false;
        }
        return true;

//        $nrOfPlacesNotInBatch = 0; @FREDDY
//        foreach( $this->getPlaces($game) as $place ) {
//            if (!$batch->hasPlace($place)) {
//                $nrOfPlacesNotInBatch++;
//            }
//        }
//        $enoughPlacesFree = ( ($batch->getNrOfPlaces() + $nrOfPlacesNotInBatch) <= 4 );
//
//        foreach( $this->getPlaces($game) as $place ) {
//            if( !$batch->hasPlace($place) && !$enoughPlacesFree ) {
//                return false;
//            }
//            if( $batch->getNrOfGames($place) === 3 ) {
//                return false;
//            }
//        }
//        return true;
    }


    private function areAllPlacesAssignableByGamesInARow(Batch $batch, Game $game): bool
    {
        foreach ($this->getPlaces($game) as $place) {
            $nrOfGamesInARow = $batch->hasPrevious() ? ($batch->getPrevious()->getGamesInARow($place)) : 0;
            if ($nrOfGamesInARow >= $this->planning->getMaxNrOfGamesInARow()) {
                return false;
            }
        }
        return true;
    }

    private function isSomeFieldAssignable(Game $game, Resources $resources): bool
    {
        foreach ($resources->getFields() as $fieldIt) {
            if ($resources->isSportAssignable($game, $fieldIt->getSport())) {
                return true;
            }
        }
        return false;
    }

    private function releaseField(Game $game/*, Resources $resources*/)
    {
//        if ($resources->getFieldIndex() !== null) {
//            $fieldIndex = array_search($game->getField(), $resources->getFields() );
//            if ($fieldIndex === false) {
//                $resources->unshiftField( $game->getField() );
//            }
//            $resources->resetFieldIndex();
//        }
        $game->emptyField();
    }

    private function assignField(Game $game, Resources $resources)
    {
        $fields = array_filter(
            $resources->getFields(),
            function ($fieldIt) use ($game, $resources): bool {
                return $resources->isSportAssignable($game, $fieldIt->getSport());
            }
        );
        if (count($fields) >= 1) {
            $field = reset($fields);
            $fieldIndex = array_search($field, $resources->getFields(), true);
            $removedField = $resources->removeField($fieldIndex);
            $resources->setFieldIndex($fieldIndex);
            $game->setField($removedField);
        }
    }

    /**
     * @param Game $game
     * @return array|Place[]
     */
    protected function getPlaces(Game $game): array
    {
        return array_map(
            function ($gamePlace) {
                return $gamePlace->getPlace();
            },
            $game->getPlaces()->toArray()
        );
    }

    protected function refereePlacesCanBeAssigned(Batch $batch)
    {
        return $this->refereePlacePredicter->canStillAssign($batch, $this->getInput()->getSelfReferee());
    }
}
