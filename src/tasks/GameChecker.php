<?php

namespace Ordness\CTP\tasks;

use Ordness\CTP\Core;
use Ordness\CTP\handlers\GameHandler;
use Ordness\CTP\objects\Game;
use Ordness\CTP\objects\Team;
use pocketmine\color\Color;
use pocketmine\scheduler\Task;

final class GameChecker extends Task
{
    public function onRun(): void
    {
        $game_query = Core::getInstance()->getDB()->query("SELECT id FROM Games WHERE UNIX_TIMESTAMP(CURRENT_TIMESTAMP) - UNIX_TIMESTAMP(date) < 120 AND started = 0;");
        foreach ($game_query->fetch_all() as $game_array){
            $id = $game_array[0];
            if(GameHandler::getGame($id)) return;
            $teams_query = Core::getInstance()->getDB()->query("SELECT * FROM Teams WHERE id_game=$id;");
            $teams = [];
            foreach ($teams_query->fetch_all() as $team_array) {
                $color = $team_array[1];
                $teams[] = new Team(Color::fromRGBA($color));
            }
            if(count($teams) < 2) return;
            GameHandler::addGame(new Game($id, $teams[0], $teams[1]));
        }
    }
}