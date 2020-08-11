<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 1-2-19
 * Time: 12:43
 */

namespace SportsPlanning\Place;

use Doctrine\Common\Collections\ArrayCollection;
use SportsPlanning\Game;
use SportsPlanning\Game\Place as GamePlace;
use SportsPlanning\Place;

class Combination
{
    /**
     * @var array | Place[]
     */
    private $home;
    /**
     * @var array | Place[]
     */
    private $away;

    public function __construct(array $home, array $away)
    {
        $this->home = $home;
        $this->away = $away;
    }

    /**
     * @param array | Place[] $places
     * @return int
     */
    public static function getSum(array $places): int
    {
        $nr = 0;
        foreach ($places as $place) {
            $nr += static::getNumber($place);
        };
        return $nr;
    }

    /**
     * @param Place $place
     * @return int
     */
    public static function getNumber(Place $place): int
    {
        return pow(2, $place->getNumber() - 1);
    }

    /**
     * @return array | Place[]
     */
    public function getHome(): array
    {
        return $this->home;
    }

    /**
     * @return array | Place[]
     */
    public function getAway(): array
    {
        return $this->away;
    }

    /**
     * @return array | Place[]
     */
    public function get(): array
    {
        return array_merge($this->home, $this->away);
    }

    public function count(): int
    {
        return count($this->home) + count($this->away);
    }

    /**
     * @param Game $game
     * @param bool $reverseHomeAway
     */
    public function createGamePlaces(Game $game, bool $reverseHomeAway)
    {
        foreach ($this->getHome() as $homeIt) {
            new GamePlace($game, $homeIt, $reverseHomeAway ? Game::AWAY : Game::HOME);
        };
        foreach ($this->getAway() as $awayIt) {
            new GamePlace($game, $awayIt, $reverseHomeAway ? Game::HOME : Game::AWAY);
        }
    }

    public function hasOverlap(Combination $combination)
    {
        $number = new Combination\Number($this);
        return $number->hasOverlap(new Combination\Number($combination));
    }
}
