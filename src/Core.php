<?php

namespace Ordness\CTP;

use MySQLi;
use Ordness\CTP\handlers\MapsHandler;
use Ordness\CTP\listeners\PlayerListener;
use Ordness\CTP\tasks\GameChecker;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

class Core extends PluginBase
{
    public const PREFIX = "§l§eO§frdness §e» §r";
    use SingletonTrait;

    protected function onEnable(): void
    {
        $this->saveResource("config.yml", true);
        $this::setInstance($this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerListener(), $this);
        if($this->getDB()->ping()) $this->initDB();
        $this->getScheduler()->scheduleRepeatingTask(new GameChecker(), 10*20);
        MapsHandler::loadMaps();
    }

    /**
     * @return MySQLi
     */
    public function getDB(): MySQLi
    {
        return new MySQLi("45.145.166.29", "u14_fd44NLyfv3", "Y2G2jno4eC4e=KiZ+!sCbh9f", "s14_ordness", 3306);
    }

    private function initDB(): void
    {
        $this->getDB()->query("CREATE TABLE IF NOT EXISTS Games(id INTEGER NOT NULL UNIQUE AUTO_INCREMENT, date DATETIME, started BIT NOT NULL DEFAULT 0, PRIMARY KEY(id));");
        $this->getDB()->query("CREATE TABLE IF NOT EXISTS Teams(id INTEGER NOT NULL UNIQUE AUTO_INCREMENT, color INTEGER NOT NULL, id_game INTEGER NOT NULL, PRIMARY KEY(id), FOREIGN KEY(id_game) REFERENCES Games(id));");
        $this->getDB()->query("CREATE TABLE if NOT EXISTS Players(id INTEGER NOT NULL UNIQUE AUTO_INCREMENT, username TEXT NOT NULL UNIQUE, id_team INTEGER NOT NULL,PRIMARY KEY(id),FOREIGN KEY(id_team) REFERENCES Teams(id));");
        $this->getLogger()->notice("Database initialized");
    }
}