<?php

namespace OguzhanUmutlu\TheFloorIsLava;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\level\LevelUnloadEvent;
use pocketmine\event\Listener;
use pocketmine\level\Level;
use pocketmine\plugin\PluginBase;

class TheFloorIsLava extends PluginBase implements Listener {
    /*** @var array[] */
    public $arenas = [];
    /*** @var TheFloorIsLava */
    public static $instance;
    /*
     * [
     *   "level" => Level $level,
     *   "speed" => int $seconds,
     *   "lavaLevel" => int $laveLevel,
     *   "task" => LavaTask $task
     * ]
     * */
    public function onEnable() {
        self::$instance = $this;
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if($command->getName() != "tfil" || !$sender->hasPermission($command->getPermission())) return true;
        switch($args[0] ?? null) {
            case "add":
                if(!isset($args[1])) {
                    $sender->sendMessage("§c> Usage: /tfil add <worldName".">");
                    break;
                }
                if(!$this->getServer()->isLevelLoaded($args[1]))
                    $this->getServer()->loadLevel($args[1]);
                $level = $this->getServer()->getLevelByName($args[1]);
                if(!$level instanceof Level) {
                    $sender->sendMessage("§c> World not found.");
                    break;
                }
                if(isset($this->arenas[$level->getFolderName()])) {
                    $sender->sendMessage("§c> There is already an arena that is set to this world!");
                    break;
                }
                $task = new LavaTask();
                $arena = [
                    "level" => $level->getFolderName(),
                    "speed" => 30,
                    "isWorking" => false,
                    "lavaLevel" => 0,
                    "task" => $task
                ];
                $this->arenas[$level->getFolderName()] = $arena;
                $task->setArena($arena);
                $sender->sendMessage("§a> Arena added!");
                break;
            case "start":
                if(!isset($args[1])) {
                    $sender->sendMessage("§c> Usage: /tfil start <worldName".">");
                    break;
                }
                if(!$this->getServer()->isLevelLoaded($args[1]))
                    $this->getServer()->loadLevel($args[1]);
                $level = $this->getServer()->getLevelByName($args[1]);
                if(!$level instanceof Level) {
                    $sender->sendMessage("§c> World not found.");
                    break;
                }
                if(!isset($this->arenas[$level->getFolderName()])) {
                    $sender->sendMessage("§c> There isn't any arena that is set to this world!");
                    break;
                }
                $arena = $this->arenas[$level->getFolderName()];
                $task = $arena["task"];
                if(!$task instanceof LavaTask) break;
                $task->setTicks($arena["speed"]*20);
                $sender->sendMessage("§a> Lava is now rising!");
                break;
            case "stop":
                if(!isset($args[1])) {
                    $sender->sendMessage("§c> Usage: /tfil stop <worldName".">");
                    break;
                }
                if(!$this->getServer()->isLevelLoaded($args[1]))
                    $this->getServer()->loadLevel($args[1]);
                $level = $this->getServer()->getLevelByName($args[1]);
                if(!$level instanceof Level) {
                    $sender->sendMessage("§c> World not found.");
                    break;
                }
                if(!isset($this->arenas[$level->getFolderName()])) {
                    $sender->sendMessage("§c> There isn't any arena that is set to this world!");
                    break;
                }
                $arena = $this->arenas[$level->getFolderName()];
                $task = $arena["task"];
                if(!$task instanceof LavaTask) break;
                $task->stop();
                $sender->sendMessage("§a> Lava rising is stopped!");
                break;
            case "setSpeed":
                if(!isset($args[1]) || !isset($args[2]) || !is_numeric($args[2])) {
                    $sender->sendMessage("§c> Usage: /tfil setSpeed <worldName"."> <lavaPerXSecond".">");
                    break;
                }
                if(!$this->getServer()->isLevelLoaded($args[1]))
                    $this->getServer()->loadLevel($args[1]);
                $level = $this->getServer()->getLevelByName($args[1]);
                if(!$level instanceof Level) {
                    $sender->sendMessage("§c> World not found.");
                    break;
                }
                if(!isset($this->arenas[$level->getFolderName()])) {
                    $sender->sendMessage("§c> There isn't any arena that is set to this world!");
                    break;
                }
                $this->arenas[$level->getFolderName()]["speed"] = (float)$args[2];
                $arena = $this->arenas[$level->getFolderName()];
                $task = $arena["task"];
                if(!$task instanceof LavaTask) break;
                $stop = !$task->getHandler() || $task->getHandler()->isCancelled();
                $task->setTicks($arena["speed"]*20);
                if($stop)
                    $task->stop();
                $sender->sendMessage("§a> Lava speed set to §bper ".$arena["speed"]." second§a!");
                break;
            case "remove":
                if(!isset($args[1])) {
                    $sender->sendMessage("§c> Usage: /tfil setSpeed <worldName"."> <lavaPerXSecond".">");
                    break;
                }
                if(!$this->getServer()->isLevelLoaded($args[1]))
                    $this->getServer()->loadLevel($args[1]);
                $level = $this->getServer()->getLevelByName($args[1]);
                if(!$level instanceof Level) {
                    $sender->sendMessage("§c> World not found.");
                    break;
                }
                if(!isset($this->arenas[$level->getFolderName()])) {
                    $sender->sendMessage("§c> There isn't any arena that is set to this world!");
                    break;
                }
                $task = $this->arenas[$level->getFolderName()]["task"];
                if(!$task instanceof LavaTask) break;
                if($task->getHandler())
                    $task->getHandler()->cancel();
                unset($this->arenas[$level->getFolderName()]);
                $sender->sendMessage("§a> Arena removed!");
                break;
            case "limit":
                $sender->sendMessage("§e> Active arenas' worlds: ".implode(", ",array_map(function($arena){return $arena["level"] instanceof Level ? $arena["level"]->getFolderName() : "NOT FOUND!";},$this->arenas)));
                break;
            default:
                $sender->sendMessage("§c> Usage: /tfli <add, start, stop, setSpeed, remove, list>");
                break;
        }
        return true;
    }

    public function onLevelRemove(LevelUnloadEvent $event) {
        unset($this->arenas[$event->getLevel()->getFolderName()]);
    }
}