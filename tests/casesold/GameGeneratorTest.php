<?php

namespace SportsPlanning\Tests\Planning;

include_once __DIR__ . '/../../data/CompetitionCreator.php';

use Doctrine\Common\Collections\ArrayCollection;
use SportsPlanning\Place as PlanningPlace;
use SportsPlanning\GameRound;
use SportsPlanning\GameGenerator;
use Voetbal\Game as GameBase;
use Voetbal\Qualify\Service as QualifyService;
use Voetbal\Ranking\Service as RankingService;
use SportsPlanning\Input as PlanningInput;
use SportsPlanning\Input\Service as PlanningInputService;
use Voetbal\Structure\Service as StructureService;
use SportsPlanning\Service as PlanningService;
use Voetbal\Qualify\Group as QualifyGroup;

class GameGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PlanningInputService
     */
    protected $planningInputService;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->planningInputService = new PlanningInputService();
    }

    public function testOneSportAnd4()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 4, 1);
        $firstRoundNumber = $structure->getFirstRoundNumber();


        $planningInput = $this->planningInputService->convert($firstRoundNumber);
        $gameGenerator = new GameGenerator($planningInput);
        $firstPoule = $planningInput->getPoule(1);
        $gameRounds = $gameGenerator->createPouleGameRounds($firstPoule, $firstRoundNumber->getValidPlanningConfig()->getTeamup());

        $roundNr = 1;
        $subNr = 1;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [1], [4]);
        $subNr++;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [2], [3]);
        $roundNr++;
        $subNr = 1;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [2], [1]);
        $subNr++;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [4], [3]);
        $roundNr++;
        $subNr = 1;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [3], [1]);
        $subNr++;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [4], [2]);
        $this->assertSame(count($gameRounds), $roundNr);
        foreach ($firstPoule->getPlaces() as $place) {
            $this->assertValidGamesParticipations($place, $gameRounds, 3);
        }
    }

    /**
     * with one poule referee can be from same poule
     */
    public function testOneSportHtoh2And44()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 8, 2);
        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstRoundNumber->getValidPlanningConfig()->setNrOfHeadtohead(2);

        $planningInput = $this->planningInputService->convert($firstRoundNumber);
        $gameGenerator = new GameGenerator($planningInput);
        $gameGenerator->create();
        $games = $firstRoundNumber->getGames(GameBase::ORDER_POULE);
        $this->assertSame(count($games), 24);
    }

    public function testOneSportTeamupAnd4()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 4, 1);
        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstRoundNumber->getValidPlanningConfig()->setTeamup(true);

        $planningInput = $this->planningInputService->convert($firstRoundNumber);
        $gameGenerator = new GameGenerator($planningInput);

        $firstPoule = $planningInput->getPoule(1);
        $gameRounds = $gameGenerator->createPouleGameRounds($firstPoule, $firstRoundNumber->getValidPlanningConfig()->getTeamup());

        $roundNr = 1;
        $subNr = 1;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [1, 4], [2, 3]);
        $roundNr++;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [2, 1], [3, 4]);
        $roundNr++;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [3, 1], [2, 4]);
        foreach ($firstPoule->getPlaces() as $place) {
            $this->assertValidGamesParticipations($place, $gameRounds, 3);
        }
    }

    public function testOneSportTeamupAnd5()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 5, 1);
        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstRoundNumber->getValidPlanningConfig()->setTeamup(true);

        $planningInput = $this->planningInputService->convert($firstRoundNumber);
        $gameGenerator = new GameGenerator($planningInput);

        $firstPoule = $planningInput->getPoule(1);
        $gameRounds = $gameGenerator->createPouleGameRounds($firstPoule, $firstRoundNumber->getValidPlanningConfig()->getTeamup());

        // gameRounds.forEach( gameRound => {
        //     const out = '';
        //     gameRound.getCombinations().forEach( combination => {
        //         console.log(
        //             combination.getHome().map( homePlace => homePlace.getNumber() ).join(' & ')
        //             + ' vs ' +
        //             combination.getAway().map( homePlace => homePlace.getNumber() ).join(' & ')
        //         );
        //     });
        // });

        $roundNr = 1;
        $subNr = 1;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [2, 5], [3, 4]);
        $roundNr++;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [1, 2], [4, 5]);
        $roundNr++;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [5, 4], [2, 3]);
        $roundNr++;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [1, 3], [4, 5]);
        $roundNr++;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [4, 2], [3, 5]);
        $roundNr++;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [4, 1], [3, 5]);
        $roundNr++;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [5, 1], [3, 4]);
        $roundNr++;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [2, 5], [1, 3]);
        $roundNr++;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [3, 4], [1, 2]);
        $roundNr++;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [1, 3], [2, 4]);
        $roundNr++;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [4, 1], [2, 3]);
        $roundNr++;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [3, 5], [1, 2]);
        $roundNr++;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [5, 1], [2, 3]);
        $roundNr++;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [2, 5], [4, 1]);
        $roundNr++;
        $this->assertSameGame($gameRounds, $roundNr, $subNr, [4, 2], [5, 1]);

        $this->assertSame(count($gameRounds), 15);
        foreach ($firstPoule->getPlaces() as $place) {
            $this->assertValidGamesParticipations($place, $gameRounds, 12);
        }
    }

    public function testOneSportTeamupAnd6()
    {
        $competition = createCompetition();

        $structureService = new StructureService();
        $structure = $structureService->create($competition, 6, 1);
        $firstRoundNumber = $structure->getFirstRoundNumber();

        $firstRoundNumber->getValidPlanningConfig()->setTeamup(true);

        $planningInput = $this->planningInputService->convert($firstRoundNumber);
        $gameGenerator = new GameGenerator($planningInput);

        $firstPoule = $planningInput->getPoule(1);
        $gameRounds = $gameGenerator->createPouleGameRounds($firstPoule, $firstRoundNumber->getValidPlanningConfig()->getTeamup());

        $this->assertSame(count($gameRounds), 45);
        foreach ($firstPoule->getPlaces() as $place) {
            $this->assertValidGamesParticipations($place, $gameRounds, 30);
        }
    }

    /**
     * @param array|GameRound[] $gameRounds
     * @param int $roundNr
     * @param int $subNr
     * @param array|int[] $home
     * @param array|int[] $away
     */
    protected function assertSameGame(array $gameRounds, int $roundNr, int $subNr, array $home, array $away)
    {
        $combination = $gameRounds[$roundNr - 1]->getCombinations()[$subNr - 1];

        // should be deepequal

        $homeCombinations = array_map(function ($place) {
            return $place->getNumber();
        }, $combination->getHome());
        $this->assertSame($homeCombinations, $home);

        $awayCombinations = array_map(function ($place) {
            return $place->getNumber();
        }, $combination->getAway());
        $this->assertSame($awayCombinations, $away);
    }

    /**
     * check if every place has the same amount of games
     * check if one place is not two times in one game
     *
     * @param PlanningPlace $place
     * @param array|GameRound[] $gameRounds
     * @param int|null $expectedValue
     */
    public function assertValidGamesParticipations(PlanningPlace $place, array $gameRounds, int $expectedValue = null)
    {
        // const sportPlanningConfigService = new SportPlanningConfigService();
        $nrOfGames = 0;
        foreach ($gameRounds as $gameRound) {
            foreach ($gameRound->getCombinations() as $combination) {
                // combination is game
                $nrOfSingleGameParticipations = 0;
                foreach ($combination->get() as $placeIt) {
                    if ($placeIt === $place) {
                        $nrOfSingleGameParticipations++;
                    }
                }
                if ($nrOfSingleGameParticipations === 1) {
                    $nrOfGames++;
                }
                $this->assertLessThan(2, $nrOfSingleGameParticipations);
            }
        }

        // const config = place.getRound().getNumber().getValidPlanningConfig();
        // const nrOfGamesPerPlace = sportPlanningConfigService.getNrOfGamesPerPlace(place.getPoule(), config.getNrOfHeadtohead());
        // expect(nrOfGamesPerPlace).to.equal(nrOfGames);
        if ($expectedValue !== null) {
            $this->assertSame($expectedValue, $nrOfGames);
        }
    }
}
