<?php

namespace Ordness\CTP\objects;

use Ordness\CTP\Core;
use Ordness\CTP\handlers\GamesHandler;
use Ordness\CTP\handlers\MapsHandler;
use pocketmine\color\Color;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\particle\ExplodeParticle;
use pocketmine\world\particle\PotionSplashParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\ExplodeSound;
use pocketmine\world\sound\LaunchSound;
use pocketmine\world\sound\PopSound;
use pocketmine\world\World;

final class Game
{
    private ?World $world = null;
    /**
     * @var Point[]|null
     */
    private ?array $points;
    private ClosureTask $timeoutTask;
    private ClosureTask $countDownTask;
    private bool $started = false;
    private int $startTime;
    private ClosureTask $showTask;
    private ClosureTask $gameTask;

    public function __construct(private int $id, private Team $blue, private Team $red, private int $countMax)
    {
        $this->startTime = time() + 60;
        $this->timeoutTask = new ClosureTask(function (): void {
            if (!$this->started) {
                if (!$this->showTask->getHandler()?->isCancelled()) $this->showTask->getHandler()?->cancel();
                $this->start();
            }
        });
        Core::getInstance()->getScheduler()->scheduleRepeatingTask($this->showTask = new ClosureTask(function (): void {
            foreach ($this->getPlayers() as $str) {
                $player = Core::getInstance()->getServer()->getPlayerByPrefix($str);
                if(!$player) continue;
                if ($this->startTime - time() > 0) {
                    $player->sendActionBarMessage(Core::PREFIX . "§cPréparation du monde..");
                    $player->sendTip("§fLe §6CTP §fpeut commencer dans §e" . ($this->startTime - time()) . " secondes§f.");
                } else {
                    if ($this->getCount() >= $this->getCountMax()) {
                        $this->start();
                        if (!$this->showTask->getHandler()?->isCancelled()) $this->showTask->getHandler()?->cancel();
                    } else {
                        $player->sendActionBarMessage(Core::PREFIX . "§cEn attente des joueurs..");
                        $player->sendTip("§fLe §6CTP §fcommence dans §e" . ($this->startTime + 20 - time()) . " secondes§f.");
                    }
                }
            }
        }), 20);
        Core::getInstance()->getScheduler()->scheduleDelayedTask($this->timeoutTask, 80 * 20);
        if (MapsHandler::getRandomMap() === null) {
            Core::getInstance()->getLogger()->error("[$this->id] No maps found !");
            Core::getInstance()->getDB()->query("UPDATE Games SET started=1 WHERE id=$this->id;");
            $this->stop();
        }
    }

    /**
     * @return int
     */
    public function getCountMax(): int
    {
        return $this->countMax;
    }

    public function getCount(): int
    {
        return count($this->getRed()->getPlayers()) + count($this->getBlue()->getPlayers());
    }

    /**
     * @return ?World
     */
    public function getWorld(): ?World
    {
        return $this->world;
    }

    /**
     * @return Team
     */
    public function getBlue(): Team
    {
        return $this->blue;
    }

    /**
     * @return Team
     */
    public function getRed(): Team
    {
        return $this->red;
    }

    /**
     * @return string[]
     */
    public function getPlayers(): array
    {
        return array_merge($this->getBlue()->getPlayers(), $this->getRed()->getPlayers());
    }

    public function start(): void
    {
        Core::getInstance()->getDB()->query("UPDATE Games SET started=1 WHERE id=$this->id;");
        $this->started = true;
        $map = MapsHandler::getRandomMap();
        $worldName = $map?->duplicate($this->id);
        $this->world = (function() use ($worldName): ?World {
            foreach (Core::getInstance()->getServer()->getWorldManager()->getWorlds() as $world){
                if($world->getFolderName() === $worldName){
                    return $world;
                }
            }
            return null;
        })();
        $this->points = $map?->getPoints();
        if (!$this->world) {
            Core::getInstance()->getLogger()->error("[$this->id] Error while duplicating map !");
            $this->stop();
            return;
        }
        $this->getRed()->setSpawnPoint(Position::fromObject($map->getRedSpawn()->add(0.5, 0, 0.5), $this->world));
        $this->getBlue()->setSpawnPoint(Position::fromObject($map->getBlueSpawn()->add(0.5, 0, 0.5), $this->world));
        $this->getRed()->teleportToSpawn(...$this->getRed()->getPlayers());
        $this->getBlue()->teleportToSpawn(...$this->getBlue()->getPlayers());
        $this->getRed()->equip(...$this->getRed()->getPlayers());
        $this->getBlue()->equip(...$this->getBlue()->getPlayers());
        foreach ($this->getPlayers() as $name) {
            $player = Core::getInstance()->getServer()->getPlayerByPrefix($name);
            $player?->sendMessage(Core::PREFIX . "Téléportation au §6Capture The Point§f...");
            $player?->setImmobile(true);
        }
        $countDown = 10;
        $this->countDownTask = new ClosureTask(function () use (&$countDown) {
            $color = fn(int $count) => match (true) {
                $count <= 10 and $count > 5 => "§6",
                $count <= 5 and $count > 3 => "§e",
                $count === 3 => "§c",
                $count === 2 => "§4",
                $count === 1 => "§0",
                $count === 0 => "§a",
                default => "§f",
            };
            foreach ($this->getPlayers() as $name) {
                $player = Core::getInstance()->getServer()->getPlayerByPrefix($name);
                if ($countDown <= 0) {
                    $player?->broadcastSound(new ExplodeSound());
                    $player?->getWorld()->addParticle($player->getPosition(), new ExplodeParticle());
                    $player?->setImmobile(false);
                    $player?->sendTitle("§aGO !", "§fCapturez les différents §6points §fet accumulez des §epoints §f!");
                    if (!$this->countDownTask->getHandler()?->isCancelled()) $this->countDownTask->getHandler()?->cancel();
                    $this->onStart();
                }
                else {
                    $player?->sendTip(Core::PREFIX . "§6Capture The Point");
                    $player?->sendActionBarMessage("§fDébut de la partie dans {$color($countDown)}$countDown secondes§f...");
                    $player?->broadcastSound(new PopSound());
                }
            }
            $countDown -= 1;
        });
        Core::getInstance()->getScheduler()->scheduleRepeatingTask($this->countDownTask, 20);
        $this->timeoutTask->getHandler()?->cancel();
    }

    public function onStart(): void
    {
        foreach ($this->points ?? [] as $point) {
            $point->start($this);
        }
        $players = [];
        foreach ($this->getPlayers() as $name) {
            if ($player = Core::getInstance()->getServer()->getPlayerByPrefix($name)) {
                $players[] = $player;
            }
        }
        $this->getRed()->getBossBar()->addPlayers($players);
        $this->getBlue()->getBossBar()->addPlayers($players);
        $this->gameTask = new ClosureTask(function (): void {
            if($this->getRed()->getScore() >= 100){
                $this->stop();
                $this->gameTask->getHandler()?->cancel();
            }
            else if($this->getBlue()->getScore() >= 100){
                $this->stop();
                $this->gameTask->getHandler()?->cancel();
            }
            else {
                $this->getRed()->updateBossBar();
                $this->getBlue()->updateBossBar();   
            }
        });
        Core::getInstance()->getScheduler()->scheduleRepeatingTask($this->gameTask, 5);
    }

    public function stop(): void
    {
        GamesHandler::removeGame($this);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function addPlayer(Color $color, string $username): void
    {
        if ($color->toRGBA() === $this->blue->getColor()->toRGBA()) {
            $this->blue->addPlayer($username);
        } else {
            $this->red->addPlayer($username);
        }
        $player = Core::getInstance()->getServer()->getPlayerByPrefix($username);
        $player?->broadcastSound(new LaunchSound());
        $player?->getPosition()->getWorld()->addParticle($player->getPosition(), new PotionSplashParticle($color));
    }

}