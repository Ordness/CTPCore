<?php

namespace Ordness\CTP\handlers;

use Ordness\CTP\Core;
use Ordness\CTP\objects\Map;
use pocketmine\Server;
use ValueError;

abstract class MapsHandler
{
    /**
     * @var Map[]
     */
    private static array $maps = [];

    public static function loadMaps(): void
    {
        foreach (Core::getInstance()->getConfig()->get('maps', []) as $map => $data) {
            if (Server::getInstance()->getWorldManager()->loadWorld($map)) {
                Server::getInstance()->getLogger()->info("[MAPS] Map $map loaded !");
                self::$maps[$map] = new Map($map, $data["spawns"], ...$data['points']);
            } else {
                Server::getInstance()->getLogger()->error("[MAPS] Map $map not found!");
            }
        }
    }

    public static function getRandomMap(): ?Map
    {
        try {
            return self::$maps[@array_rand(self::$maps)] ?? null;
        }
        catch (ValueError $error) {
            return null;
        }
    }
}