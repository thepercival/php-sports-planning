<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 1-2-19
 * Time: 21:01
 */

namespace SportsPlanning\Tests;

include_once __DIR__ . '/../../helpers/Serializer.php';
include_once __DIR__ . '/../../helpers/PostSerialize.php';

use SportsPlanning\Service as PlanningService;
use SportsPlanning\Game;

class ServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testFourPoulePlacesTwoTimeHeadtohead()
    {
        $serializer = getSerializer();

        $json_raw = file_get_contents(__DIR__ . "/../../data/competition.json");
        $json = json_decode($json_raw, true);
        $competition = $serializer->deserialize(json_encode($json), 'SportsPlanning\Competition', 'json');

        $json_raw = file_get_contents(__DIR__ . "/../../data/structure-gamegenerator.json");
        $json = json_decode($json_raw, true);
        $structure = $serializer->deserialize(json_encode($json), 'SportsPlanning\Structure', 'json');
        postSerialize($structure, $competition);
        $structure->setQualifyRules();

        $planningService = new PlanningService($competition);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $firstRoundNumber->getConfig()->setNrOfHeadtohead(2);
        $planningService->create($firstRoundNumber, $competition->getStartDateTime());
        $games = $planningService->getGamesForRoundNumber($firstRoundNumber, Game::ORDER_BYNUMBER);
        $this->assertSame(count($games), 12);

        $roundNr = 1;
        $subNr = 1;
        $this->assertSameGame($games[0], $roundNr, $subNr, [1], [4]);
        $subNr++;
        $this->assertSameGame($games[1], $roundNr, $subNr, [2], [3]);
        $roundNr++;
        $subNr = 1;
        $this->assertSameGame($games[2], $roundNr, $subNr, [2], [1]);
        $subNr++;
        $this->assertSameGame($games[3], $roundNr, $subNr, [4], [3]);
        $roundNr++;
        $subNr = 1;
        $this->assertSameGame($games[4], $roundNr, $subNr, [3], [1]);
        $subNr++;
        $this->assertSameGame($games[5], $roundNr, $subNr, [4], [2]);
        $roundNr++;
        $subNr = 1;

        $this->assertSameGame($games[6], $roundNr, $subNr, [4], [1]);
        $subNr++;
        $this->assertSameGame($games[7], $roundNr, $subNr, [3], [2]);
        $roundNr++;
        $subNr = 1;
        $this->assertSameGame($games[8], $roundNr, $subNr, [1], [2]);
        $subNr++;
        $this->assertSameGame($games[9], $roundNr, $subNr, [3], [4]);
        $roundNr++;
        $subNr = 1;
        $this->assertSameGame($games[10], $roundNr, $subNr, [1], [3]);
        $subNr++;
        $this->assertSameGame($games[11], $roundNr, $subNr, [2], [4]);
        $roundNr++;
        $subNr = 1;
    }

    public function testFourPoulePlacesTeamupHeadtoheadTwo()
    {
        $serializer = getSerializer();

        $json_raw = file_get_contents(__DIR__ . "/../../data/competition.json");
        $json = json_decode($json_raw, true);
        $competition = $serializer->deserialize(json_encode($json), 'SportsPlanning\Competition', 'json');

        $json_raw = file_get_contents(__DIR__ . "/../../data/structure-gamegenerator.json");
        $json = json_decode($json_raw, true);
        $structure = $serializer->deserialize(json_encode($json), 'SportsPlanning\Structure', 'json');
        postSerialize($structure, $competition);
        $structure->setQualifyRules();

        $planningService = new PlanningService($competition);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $firstRoundNumber->getConfig()->setNrOfHeadtohead(2);
        $firstRoundNumber->getConfig()->setTeamup(true);
        $planningService->create($firstRoundNumber, $competition->getStartDateTime());
        $games = $planningService->getGamesForRoundNumber($firstRoundNumber, Game::ORDER_BYNUMBER);
        $this->assertSame(count($games), 6);

        $roundNr = 1;
        $subNr = 1;
        $this->assertSameGame($games[0], $roundNr, $subNr, [1,4], [2,3]);
        $roundNr++;
        $this->assertSameGame($games[1], $roundNr, $subNr, [2,1], [3,4]);
        $roundNr++;
        $this->assertSameGame($games[2], $roundNr, $subNr, [3,1], [2,4]);
        $roundNr++;
        $this->assertSameGame($games[3], $roundNr, $subNr, [3,2], [4,1]);
        $roundNr++;
        $this->assertSameGame($games[4], $roundNr, $subNr, [4,3], [1,2]);
        $roundNr++;
        $this->assertSameGame($games[5], $roundNr, $subNr, [4,2], [1,3]);
        $roundNr++;
    }

    public function testFivePoulePlacesTeamup()
    {
        $serializer = getSerializer();

        $json_raw = file_get_contents(__DIR__ . "/../../data/competition.json");
        $json = json_decode($json_raw, true);
        $competition = $serializer->deserialize(json_encode($json), 'SportsPlanning\Competition', 'json');

        $json_raw = file_get_contents(__DIR__ . "/../../data/structure-gamegenerator-five.json");
        $json = json_decode($json_raw, true);
        $structure = $serializer->deserialize(json_encode($json), 'SportsPlanning\Structure', 'json');
        postSerialize($structure, $competition);
        $structure->setQualifyRules();

        $planningService = new PlanningService($competition);
        $firstRoundNumber = $structure->getFirstRoundNumber();
        $firstRoundNumber->getConfig()->setTeamup(true);
        $planningService->create($firstRoundNumber, $competition->getStartDateTime());
        $games = $planningService->getGamesForRoundNumber($firstRoundNumber, Game::ORDER_BYNUMBER);
        $this->assertSame(count($games), 15);

        $roundNr = 1;
        $subNr = 1;
        $this->assertSameGame($games[0], $roundNr, $subNr, [2, 5], [3, 4]);
        $roundNr++;
        $this->assertSameGame($games[1], $roundNr, $subNr, [1, 2], [4, 5]);
        $roundNr++;
        $this->assertSameGame($games[2], $roundNr, $subNr, [5, 4], [2, 3]);
        $roundNr++;
        $this->assertSameGame($games[3], $roundNr, $subNr, [1, 3], [4, 5]);
        $roundNr++;
        $this->assertSameGame($games[4], $roundNr, $subNr, [4, 2], [3, 5]);
        $roundNr++;
        $this->assertSameGame($games[5], $roundNr, $subNr, [4, 1], [3, 5]);
        $roundNr++;
        $this->assertSameGame($games[6], $roundNr, $subNr, [5, 1], [3, 4]);
        $roundNr++;
        $this->assertSameGame($games[7], $roundNr, $subNr, [2, 5], [1, 3]);
        $roundNr++;
        $this->assertSameGame($games[8], $roundNr, $subNr, [3, 4], [1, 2]);
        $roundNr++;
        $this->assertSameGame($games[9], $roundNr, $subNr, [1, 3], [2, 4]);
        $roundNr++;
        $this->assertSameGame($games[10], $roundNr, $subNr, [4, 1], [2, 3]);
        $roundNr++;
        $this->assertSameGame($games[11], $roundNr, $subNr, [3, 5], [1, 2]);
        $roundNr++;
        $this->assertSameGame($games[12], $roundNr, $subNr, [5, 1], [2, 3]);
        $roundNr++;
        $this->assertSameGame($games[13], $roundNr, $subNr, [2, 5], [4, 1]);
        $roundNr++;
        $this->assertSameGame($games[14], $roundNr, $subNr, [4, 2], [5, 1]);
        $roundNr++;
    }

    protected function assertSameGame(Game $game, int $roundNr, int $subNr, array $home, array $away)
    {
        $this->assertSame($game->getRoundNumber(), $roundNr);
        $this->assertSame($game->getSubNumber(), $subNr);
        $this->assertSame(array_values(array_map(function ($gamePoulePlace) {
            return $gamePoulePlace->getPoulePlace()->getNumber();
        }, $game->getPoulePlaces(Game::HOME)->toArray())), $home);
        $this->assertSame(array_values(array_map(function ($gamePoulePlace) {
            return $gamePoulePlace->getPoulePlace()->getNumber();
        }, $game->getPoulePlaces(Game::AWAY)->toArray())), $away);
    }
}
