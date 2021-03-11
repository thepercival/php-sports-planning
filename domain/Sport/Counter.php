<?php
namespace SportsPlanning\Sport;

use SportsPlanning\Sport as PlanningSport;

/**
 * Class Counter DEPRECATED??
 * @package SportsPlanning\Sport
 */
class Counter
{
    /**
     * @var int
     */
    private $nrOfSportsToGo = 0;
    /**
     * @var int
     */
    private $nrOfGamesToGo;
    /**
     * @var array
     */
    private $minNrOfGamesMap = [];
    /**
     * @var array
     */
    private $nrOfGamesDoneMap = [];

    /**
     * Counter constructor.
     * @param array|int[] $minNrOfGamesMap
     * @param array|int[] $nrOfGamesDoneMap
     */
    public function __construct(int $nrOfGamesToGo, array $minNrOfGamesMap, array $nrOfGamesDoneMap, int $nrOfSportsToGo = null)
    {
        $this->nrOfGamesToGo = $nrOfGamesToGo;
        $this->minNrOfGamesMap = $minNrOfGamesMap;
        if ($nrOfSportsToGo === null) {
            $nrOfSportsToGo = array_sum($this->minNrOfGamesMap);
        }
        $this->nrOfSportsToGo = $nrOfSportsToGo;
        $this->nrOfGamesDoneMap = $nrOfGamesDoneMap;
    }

    public function getNrOfSportsToGo(): int
    {
        return $this->nrOfSportsToGo;
    }

    public function isAssignable(PlanningSport $sport): bool
    {
        $sportNr = $sport->getNumber();
        $isSportDone = $this->nrOfGamesDoneMap[$sportNr] >= $this->minNrOfGamesMap[$sportNr];
        return ($this->nrOfSportsToGo - ($isSportDone ? 0 : 1)) <= ($this->nrOfGamesToGo - 1);
    }

    public function addGame(PlanningSport $sport)
    {
        $sportNr = $sport->getNumber();
        if (array_key_exists($sportNr, $this->nrOfGamesDoneMap) === false) {
            $this->nrOfGamesDoneMap[$sportNr] = 0;
        }
        if ($this->nrOfGamesDoneMap[$sportNr] < $this->minNrOfGamesMap[$sportNr]) {
            $this->nrOfSportsToGo--;
        }
        $this->nrOfGamesDoneMap[$sportNr]++;
        $this->nrOfGamesToGo--;
    }

    public function copy(): Counter
    {
        return new Counter($this->nrOfGamesToGo, $this->minNrOfGamesMap, $this->nrOfGamesDoneMap, $this->nrOfSportsToGo);
    }

//    public function removeGame(PlanningSport $sport ) {
//        $sportNr = $sport->getNumber();
//        $this->nrOfGamesDoneMap[$sportNr]--;
//        if ($this->nrOfGamesDoneMap[$sportNr] < $this->minNrOfGamesMap[$sportNr]) {
//            $this->nrOfSportsToGo++;
//        }
//        $this->nrOfGamesToGo++;
//    }
}
