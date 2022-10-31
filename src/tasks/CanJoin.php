<?php

namespace Ordness\CTP\tasks;

use mysqli;
use Ordness\CTP\Core;
use Ordness\CTP\handlers\GamesHandler;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

final class CanJoin extends AsyncTask
{
    public function __construct(private string $username)
    {
    }

    public function onRun(): void
    {
        $db = new MySQLi("45.145.166.29", "u14_fd44NLyfv3", "Y2G2jno4eC4e=KiZ+!sCbh9f", "s14_ordness", 3306);
        $query = $db->query("
            SELECT DISTINCT username FROM Games 
            INNER JOIN Teams ON Teams.id_game = Games.id
            INNER JOIN Players ON Players.id_team = Teams.id
            WHERE UNIX_TIMESTAMP(CURRENT_TIMESTAMP) - UNIX_TIMESTAMP(date) < 120 AND started = 0;
        ");
        $players = [];
        foreach ($query->fetch_all() as $array) {
            $players[] = $array[0];
        }
        $this->setResult(!in_array($this->username, $players));
    }

    public function onCompletion(): void
    {
        if ($this->getResult()) {
            $message = Core::PREFIX . "§cVous n'êtes pas autorisé à rejoindre cette partie.";
            Server::getInstance()->getPlayerByPrefix($this->username)?->kick($message);
        } else {
            $query = Core::getInstance()->getDB()->query("SELECT DISTINCT Teams.id_game FROM Players INNER JOIN Teams ON id_team = Teams.id;");
            $id = $query->fetch_all()[0][0] ?? null;
            if ($id) {
                $game = GamesHandler::getGame($id);
                $team_color = Core::getInstance()->getDB()->query("SELECT DISTINCT Teams.color FROM Players INNER JOIN Teams ON id_team = Teams.id;")->fetch_all()[0][0];
                $game?->addPlayer($team_color, $this->username);
            }
            Core::getInstance()->getDB()->query("DELETE FROM Players WHERE username=\"$this->username\";");
        }
    }
}