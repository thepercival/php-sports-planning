<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 28-1-19
 * Time: 9:44
 */

namespace SportsPlanning\Tests;

include_once __DIR__ . '/../../helpers/Serializer.php';
include_once __DIR__ . '/../../helpers/PostSerialize.php';

use SportsPlanning\GameGenerator;
use SportsPlanning\Game;

class GenerateGamesTest extends \PHPUnit\Framework\TestCase
{
    public function testEvenNumberOfPoulePlaces()
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

        $gameGenerator = new GameGenerator($structure->getRootRound()->getPoules()->first());
        $teamUp = false;
        $g = $gameGenerator->generate($teamUp);

        $roundNr = 1;
        $subNr = 1;
        $this->assertSameGame($g, $roundNr, $subNr, [1], [4]);
        $subNr++;
        $this->assertSameGame($g, $roundNr, $subNr, [2], [3]);
        $roundNr++;
        $subNr = 1;
        $this->assertSameGame($g, $roundNr, $subNr, [2], [1]);
        $subNr++;
        $this->assertSameGame($g, $roundNr, $subNr, [4], [3]);
        $roundNr++;
        $subNr = 1;
        $this->assertSameGame($g, $roundNr, $subNr, [3], [1]);
        $subNr++;
        $this->assertSameGame($g, $roundNr, $subNr, [4], [2]);
    }

    protected function getPPNumber(array $games, int $roundNr, int $subNr, int $homeaway)
    {
        return $games[$roundNr-1][$subNr-1][$homeaway][0]->getNumber();
    }

    public function testEvenNumberOfPoulePlacesTeamup()
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

        $gameGenerator = new GameGenerator($structure->getRootRound()->getPoules()->first());
        $teamUp = true;
        $gameRounds = $gameGenerator->generate($teamUp);

        $roundNr = 1;
        $subNr = 1;
        // controleer ook de lengte van het roundnr/subnr
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [1,4], [2,3]);
        $roundNr++;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [2,1], [3,4]);
        $roundNr++;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [3,1], [2,4]);
        $roundNr++;
    }

    protected function assertSameGame(array $gameRounds, int $roundNr, int $subNr, array $home, array $away)
    {
        $gameRound = $gameRounds[$roundNr-1];
        $this->assertNotSame($gameRound, null);
        $combination = $gameRound->getCombinations()[$subNr-1];

        $this->assertSame(array_map(function ($poulePlace) {
            return $poulePlace->getNumber();
        }, $combination->getHome()), $home);
        $this->assertSame(array_map(function ($poulePlace) {
            return $poulePlace->getNumber();
        }, $combination->getAway()), $away);
    }
}
