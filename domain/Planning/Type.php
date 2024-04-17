<?php

declare(strict_types=1);

namespace SportsPlanning\Planning;

enum Type: string
{
    case BatchGames = 'BatchGames';
    case GamesInARow = 'GamesInARow';
}
