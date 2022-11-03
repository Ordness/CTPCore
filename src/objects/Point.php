<?php

namespace Ordness\CTP\objects;

use Ordness\CTP\Core;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\Wool;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\Position;

final class Point
{
    private ClosureTask $loadTask;
    private ?Team $team = null;
    private ?Team $loadingTeam = null;
    private int $steps = 0;
    private bool $isLoading = false;
    private bool $paused = false;

    public function __construct(private int $radius, private Position|Vector3 $position)
    {
    }

    public function start(Game &$game): void
    {
        $this->position = Position::fromObject($this->position, $game->getWorld());
        $pointTask = new ClosureTask(function () use (&$game): void {
            foreach ($game->getPlayers() as $name) {
                $player = Core::getInstance()->getServer()->getPlayerByPrefix($name);
                if (!$player instanceof Player) continue;
                $team = in_array($player->getName(), $game->getRed()->getPlayers()) ? $game->getRed() : $game->getBlue();
                if ($this->team) {
                    $this->team->setScore($this->team->getScore() + 1);
                    return;
                }
                if (!$this->isLoading) {
                    if ($this->isInArea($player->getPosition())) {
                        if ($this->getTeam() === null) {
                            $this->load($team);
                        }
                    }
                } else {
                    $areInZone = function (array $team_players) {
                        foreach ($team_players as $name) if ($player = Core::getInstance()->getServer()->getPlayerByPrefix($name) and $this->isInArea($player->getPosition())) return true;
                        return false;
                    };
                    if ($this->loadingTeam->getColor()->toRGBA() !== $team->getColor()->toRGBA()) {
                        if ($areInZone($this->loadingTeam->getPlayers())) {
                            $this->paused = true;
                        } else {
                            $this->reset();
                        }
                    } else {
                        if (!$areInZone(Team::getTeamColorName($this->loadingTeam->getColor()) === "rouge" ? $game->getBlue()->getPlayers() : $game->getRed()->getPlayers())) {
                            $this->paused = false;
                        }
                    }
                }
            }
        });
        Core::getInstance()->getScheduler()->scheduleRepeatingTask($pointTask, 20);
    }

    /**
     * @return Team|null
     */
    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function getPosition(): Position|Vector3
    {
        return $this->position;
    }

    public function isInArea(Position $position): bool
    {
        $area1 = $this->position->subtract($this->radius, $this->radius * 2, $this->radius);
        $area2 = $this->position->add($this->radius, $this->radius * 2, $this->radius);
        return ($position->x >= min($area1->x, $area2->x) and $position->x <= max($area1->x, $area2->x)) and
            (($position->y >= min($area1->y, $area2->y) and $position->y <= max($area1->y, $area2->y))) and
            ($position->z >= min($area1->z, $area2->z) and $position->z <= max($area1->z, $area2->z));
    }

    private function reset(): void
    {
        $this->team = null;
        $this->loadingTeam = null;
        if (!$this->loadTask->getHandler()?->isCancelled()) $this->loadTask->getHandler()?->cancel();
        $this->steps = 0;
        $this->paused = false;
        for ($x = $this->getPosition()->getX() - $this->steps; $x <= $this->getPosition()->getX() + $this->steps; $x++) {
            for ($z = $this->getPosition()->getZ() - $this->steps; $z <= $this->getPosition()->getZ() + $this->steps; $z++) {
                $world = Core::getInstance()->getServer()->getWorldManager()->getWorldByName($this->getPosition()->getWorld()->getFolderName());
                $block = $world->getBlockAt($x, $this->getPosition()->getY(), $z);
                if(!$block instanceof Wool) break 2;
                $world->setBlockAt($x, $this->getPosition()->getY(), $z, $block->setColor(DyeColor::WHITE()));
            }
        }
    }

    private function load(Team $team): void
    {
        $this->loadingTeam = &$team;
        $this->isLoading = true;
        $this->loadTask = new ClosureTask(function () use (&$team): void {
            if ($this->paused) return;
            if ($this->steps > $this->radius) {
                $this->steps = 0;
                $this->team = &$team;
                return;
            }
            for ($x = $this->getPosition()->getX() - $this->steps; $x <= $this->getPosition()->getX() + $this->steps; $x++) {
                for ($z = $this->getPosition()->getZ() - $this->steps; $z <= $this->getPosition()->getZ() + $this->steps; $z++) {
                    $world = Core::getInstance()->getServer()->getWorldManager()->getWorldByName($this->getPosition()->getWorld()->getFolderName());
                    $block = $world->getBlockAt($x, $this->getPosition()->getY(), $z);
                    if(!$block instanceof Wool) break 2;
                    $world->setBlockAt($x, $this->getPosition()->getY(), $z, $block->setColor($team::getTeamColorName($team->getColor()) === "rouge" ? DyeColor::RED() : DyeColor::BLUE()));
                }
            }
            $this->steps++;
        });
        Core::getInstance()->getScheduler()->scheduleRepeatingTask($this->loadTask, 20);
    }
}