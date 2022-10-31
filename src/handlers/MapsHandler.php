<?php

namespace Ordness\CTP\handlers;

use Ordness\CTP\Core;
use Ordness\CTP\objects\Map;
use pocketmine\Server;

abstract class MapsHandler
{
    /**
     * @var Map[]
     */
    private static array $maps = [];

    public static function loadMaps(): void {
        foreach (Core::getInstance()->getConfig()->get('maps', []) as $map){
            if(Server::getInstance()->getWorldManager()->loadWorld($map)){
                Server::getInstance()->getLogger()->info("Map $map loaded!");
                self::$maps[$map] = new Map();
            } else {
                Server::getInstance()->getLogger()->error("Map $map not found!");
            }
        }
    }

    public static function getRandomMap(): ?Map
    {
        return self::$maps[array_rand(self::$maps)] ?? null;
    }
}