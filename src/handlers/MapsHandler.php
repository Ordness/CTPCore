<?php

namespace Ordness\CTP\handlers;

use Ordness\CTP\Core;
use Ordness\CTP\objects\Map;
use pocketmine\Server;

abstract class MapsHandler
{
    public static function loadMaps(): void {
        foreach (Core::getInstance()->getConfig()->get('maps', []) as $map){
            if(Server::getInstance()->getWorldManager()->loadWorld($map)){
                Server::getInstance()->getLogger()->info("Map $map loaded!");
                $map = new Map($map);
            } else {
                Server::getInstance()->getLogger()->error("Map $map not found!");
            }
        }
    }
}