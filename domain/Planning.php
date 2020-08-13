<?php

namespace SportsPlanning;

use DateTimeImmutable;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use SportsHelpers\Range;
use SportsHelpers\SportConfig as SportConfigHelper;

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

    const STATE_FAILED = 1;
    const STATE_TIMEOUT = 2;
    const STATE_SUCCESS = 4;
    const STATE_UPDATING_SELFREFEE = 8;
    const STATE_PROCESSING = 16;

    const TIMEOUT_MULTIPLIER = 6;
    const DEFAULT_TIMEOUTSECONDS = 5;

    public function __construct(Input $input, Range $nrOfBatchGames, int $maxNrOfGamesInARow)
    {
        $this->input = $input;
        $this->minNrOfBatchGames = $nrOfBatchGames->min;
        $this->maxNrOfBatchGames = $nrOfBatchGames->max;
        $this->maxNrOfGamesInARow = $maxNrOfGamesInARow;
        $this->input->addPlanning($this);
        $this->initPoules($this->getInput()->getStructureConfig());
        $this->initSports($this->getInput()->getSportConfigHelpers());
        $this->initReferees($this->getInput()->getNrOfReferees());

        $this->createdDateTime = new DateTimeImmutable();
        $this->initTimeoutSeconds();
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

    protected function initTimeoutSeconds()
    {
        $this->timeoutSeconds = Planning::DEFAULT_TIMEOUTSECONDS;
//        if( $this->input->getTeamup() || max($this->input->getStructureConfig()) > 7 || $this->input->hasMultipleSports() ) {
//            $this->timeoutSeconds *= 6;
//        }
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

//    public function setInput( PlanningInput $input ) {
//        $this->input = $input;
//    }

    public function getPoules(): Collection
    {
        return $this->poules;
//        $structure = new Structure();
//        foreach( $this->getInput()->getStructureConfig() as $nrOfPlaces ) {
//            $structure->addPoule( new Poule( $this, $structure->getPoules()->count() + 1, $nrOfPlaces ) );
//        }
//        return $structure;
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
     * @param array|int[] $structureConfig
     */
    protected function initPoules(array $structureConfig)
    {
        $this->poules = new ArrayCollection();
        foreach ($structureConfig as $nrOfPlaces) {
            $this->poules->add(new Poule($this, $this->poules->count() + 1, $nrOfPlaces));
        }
    }

    public function getStructure(): Structure
    {
        return new Structure($this->getPoules());
    }

    public function getSports(): Collection
    {
        return $this->sports;
    }

    /**
     * @param array|SportConfigHelper[] $sportConfigHelpers
     */
    protected function initSports(array $sportConfigHelpers)
    {
        $fieldNr = 1;
        $this->sports = new ArrayCollection();
        foreach ($sportConfigHelpers as $sportConfigHelper) {
            $sport = new Sport($this, $this->sports->count() + 1, $sportConfigHelper->getNrOfGamePlaces() );
            $this->sports->add($sport);
            for ($fieldNrDelta = 0 ; $fieldNrDelta < $sportConfigHelper->getNrOfFields() ; $fieldNrDelta++) {
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

    public function createFirstBatch(): Batch
    {
        $games = $this->getGames(Game::ORDER_BY_BATCH);
        $batch = new Batch();
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
     * @return array|Game[]
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
        } elseif ($order === Game::ORDER_BY_GAMENUMBER) {
            uasort($games, function (Game $g1, Game $g2): int {
                if ($g1->getRoundNr() !== $g2->getRoundNr()) {
                    return $g1->getRoundNr() - $g2->getRoundNr();
                }
                if ($g1->getSubNr() !== $g2->getSubNr()) {
                    return $g1->getSubNr() - $g2->getSubNr();
                }
                return $g1->getPoule()->getNumber() - $g2->getPoule()->getNumber();
            });
        }
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
}
