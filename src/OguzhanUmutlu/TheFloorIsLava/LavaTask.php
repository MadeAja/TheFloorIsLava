<?php

namespace OguzhanUmutlu\TheFloorIsLava;

use pocketmine\level\Level;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class LavaTask extends Task {
    /*** @var array */
    private $arena;
    public function __construct(?array $arena = null) {
        if($arena)
            $this->arena = $arena;
    }

    /*** @param array $arena */
    public function setArena(array $arena): void {
        $this->arena = $arena;
    }

    public function setTicks(int $ticks): void {
        if(!$this->arena)
            return;
        $this->stop();
        TheFloorIsLava::$instance->getScheduler()->scheduleRepeatingTask($this, floor($ticks));
    }

    public function stop(): void {
        if($this->getHandler())
            $this->getHandler()->cancel();
    }

    public function onRun(int $currentTick) {
        $level = $this->arena["level"];
        var_dump(!$level instanceof Level, !isset(TheFloorIsLava::$instance->arenas[$this->arena["level"]->getFolderName()]), $this->arena["lavaLevel"] >= $this->arena["level"]->getWorldHeight());
        if(!$level instanceof Level || !isset(TheFloorIsLava::$instance->arenas[$this->arena["level"]->getFolderName()]) || $this->arena["lavaLevel"] >= $this->arena["level"]->getWorldHeight()) {
            $this->stop();
            return;
        }
        var_dump(2);
        $async = new FillLavaTask($level->getId(), array_map(function($c){return $c->fastSerialize();},array_values($level->getChunks())), $this->arena["lavaLevel"]+1);
        Server::getInstance()->getAsyncPool()->submitTask($async);
        foreach($level->getPlayers() as $player)
            $player->sendMessage("§d> Lava rised to ".$this->arena["lavaLevel"]."!");
        $this->arena["lavaLevel"]++;
    }
}