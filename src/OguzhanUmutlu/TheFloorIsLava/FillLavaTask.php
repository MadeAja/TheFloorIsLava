<?php

namespace OguzhanUmutlu\TheFloorIsLava;

use pocketmine\block\Block;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class FillLavaTask extends AsyncTask {
    private $chunks;
    private $levelId;
    private $maxY;

    public function __construct(int $levelId, array $chunks, int $maxY) {
        $this->chunks = $chunks;
        $this->levelId = $levelId;
        $this->maxY = $maxY;
    }

    public function onRun(){
        /** @var Chunk[] $chunks */
        $chunks = array_values(array_map(function($chunk){return Chunk::fastDeserialize($chunk);},$this->chunks));
        foreach($chunks as $i => $chunk)
            for($x = 0; $x < 16; $x++)
                for($y = 0; $y < $this->maxY; $y++)
                    for($z = 0; $z < 16; $z++) {
                        if($chunk->getX() == ($x >> 4) && $chunk->getZ() == ($z >> 4)) {
                            $chunk->setBlock($x, $y, $z, Block::LAVA);
                            $chunks[$i] = $chunk;
                        }
                    }
        $this->setResult((array)array_map(function($chunk){return $chunk->fastSerialize();},$chunks));
    }

    public function onCompletion(Server $server) {
        $chunks = (array)$this->getResult();
        $level = $server->getLevel($this->levelId);
        if($level instanceof Level)
            foreach($chunks as $chunk) {
                $chunk = Chunk::fastDeserialize($chunk);
                $level->setChunk($chunk->getX(), $chunk->getZ(), $chunk);
            }
    }
}