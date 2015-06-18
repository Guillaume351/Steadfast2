<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace pocketmine\level\format\mcregion;

use pocketmine\level\format\generic\BaseFullChunk;
use pocketmine\level\format\LevelProvider;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\Byte;
use pocketmine\nbt\tag\ByteArray;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Int;
use pocketmine\nbt\tag\IntArray;
use pocketmine\Player;
use pocketmine\utils\Binary;

class Chunk extends BaseFullChunk{

	/** @var Compound */
	protected $nbt;

	public function __construct($level, Compound $nbt = null){
		if($nbt === null){
			$this->nbt = new Compound("Level", []);
			return;
		}

		$this->nbt = $nbt;

		if(isset($this->nbt->Entities) and $this->nbt->Entities instanceof Enum){
			$this->nbt->Entities->setTagType(NBT::TAG_Compound);
		}else{
			$this->nbt->Entities = new Enum("Entities", []);
			$this->nbt->Entities->setTagType(NBT::TAG_Compound);
		}

		if(isset($this->nbt->TileEntities) and $this->nbt->TileEntities instanceof Enum){
			$this->nbt->TileEntities->setTagType(NBT::TAG_Compound);
		}else{
			$this->nbt->TileEntities = new Enum("TileEntities", []);
			$this->nbt->TileEntities->setTagType(NBT::TAG_Compound);
		}

		if(isset($this->nbt->TileTicks) and $this->nbt->TileTicks instanceof Enum){
			$this->nbt->TileTicks->setTagType(NBT::TAG_Compound);
		}else{
			$this->nbt->TileTicks = new Enum("TileTicks", []);
			$this->nbt->TileTicks->setTagType(NBT::TAG_Compound);
		}

		if(!isset($this->nbt->BiomeColors) or !($this->nbt->BiomeColors instanceof IntArray)){
			$this->nbt->BiomeColors = new IntArray("BiomeColors", \array_fill(0, 256, (\PHP_INT_SIZE === 8 ? \unpack("N", "\x00\x85\xb2\x4a")[1] << 32 >> 32 : \unpack("N", "\x00\x85\xb2\x4a")[1])));
		}

		if(!isset($this->nbt->HeightMap) or !($this->nbt->HeightMap instanceof IntArray)){
			$this->nbt->HeightMap = new IntArray("HeightMap", \array_fill(0, 256, 127));
		}

		if(!isset($this->nbt->Blocks)){
			$this->nbt->Blocks = new ByteArray("Blocks", \str_repeat("\x00", 32768));
		}

		if(!isset($this->nbt->Data)){
			$this->nbt->Data = new ByteArray("Data", $half = \str_repeat("\x00", 16384));
			$this->nbt->SkyLight = new ByteArray("SkyLight", $half);
			$this->nbt->BlockLight = new ByteArray("BlockLight", $half);
		}

		parent::__construct($level, $this->nbt["xPos"], $this->nbt["zPos"], $this->nbt->Blocks->getValue(), $this->nbt->Data->getValue(), $this->nbt->SkyLight->getValue(), $this->nbt->BlockLight->getValue(), $this->nbt->BiomeColors->getValue(), $this->nbt->HeightMap->getValue(), $this->nbt->Entities->getValue(), $this->nbt->TileEntities->getValue());
		unset($this->nbt->Blocks);
		unset($this->nbt->Data);
		unset($this->nbt->SkyLight);
		unset($this->nbt->BlockLight);
		unset($this->nbt->BiomeColors);
		unset($this->nbt->HeightMap);
		unset($this->nbt->Biomes);
	}

	public function getBlockId($x, $y, $z){
		return \ord($this->blocks{($x << 11) | ($z << 7) | $y});
	}

	public function setBlockId($x, $y, $z, $id){
		$this->blocks{($x << 11) | ($z << 7) | $y} = \chr($id);
		$this->hasChanged = \true;
	}

	public function getBlockData($x, $y, $z){
		$m = \ord($this->data{($x << 10) | ($z << 6) | ($y >> 1)});
		if(($y & 1) === 0){
			return $m & 0x0F;
		}else{
			return $m >> 4;
		}
	}

	public function setBlockData($x, $y, $z, $data){
		$i = ($x << 10) | ($z << 6) | ($y >> 1);
		$old_m = \ord($this->data{$i});
		if(($y & 1) === 0){
			$this->data{$i} = \chr(($old_m & 0xf0) | ($data & 0x0f));
		}else{
			$this->data{$i} = \chr((($data & 0x0f) << 4) | ($old_m & 0x0f));
		}
		$this->hasChanged = \true;
	}

	public function getFullBlock($x, $y, $z){
		$i = ($x << 11) | ($z << 7) | $y;
		if(($y & 1) === 0){
			return (\ord($this->blocks{$i}) << 4) | (\ord($this->data{$i >> 1}) & 0x0F);
		}else{
			return (\ord($this->blocks{$i}) << 4) | (\ord($this->data{$i >> 1}) >> 4);
		}
	}

	public function getBlock($x, $y, $z, &$blockId, &$meta = \null){
		$full = $this->getFullBlock($x, $y, $z);
		$blockId = $full >> 4;
		$meta = $full & 0x0f;
	}

	public function setBlock($x, $y, $z, $blockId = \null, $meta = \null){
		$i = ($x << 11) | ($z << 7) | $y;

		$changed = \false;

		if($blockId !== \null){
			$blockId = \chr($blockId);
			if($this->blocks{$i} !== $blockId){
				$this->blocks{$i} = $blockId;
				$changed = \true;
			}
		}

		if($meta !== \null){
			$i >>= 1;
			$old_m = \ord($this->data{$i});
			if(($y & 1) === 0){
				$this->data{$i} = \chr(($old_m & 0xf0) | ($meta & 0x0f));
				if(($old_m & 0x0f) !== $meta){
					$changed = \true;
				}
			}else{
				$this->data{$i} = \chr((($meta & 0x0f) << 4) | ($old_m & 0x0f));
				if((($old_m & 0xf0) >> 4) !== $meta){
					$changed = \true;
				}
			}
		}

		if($changed){
			$this->hasChanged = \true;
		}

		return $changed;
	}

	public function getBlockSkyLight($x, $y, $z){
		$sl = \ord($this->skyLight{($x << 10) | ($z << 6) | ($y >> 1)});
		if(($y & 1) === 0){
			return $sl & 0x0F;
		}else{
			return $sl >> 4;
		}
	}

	public function setBlockSkyLight($x, $y, $z, $level){
		$i = ($x << 10) | ($z << 6) | ($y >> 1);
		$old_sl = \ord($this->skyLight{$i});
		if(($y & 1) === 0){
			$this->skyLight{$i} = \chr(($old_sl & 0xf0) | ($level & 0x0f));
		}else{
			$this->skyLight{$i} = \chr((($level & 0x0f) << 4) | ($old_sl & 0x0f));
		}
		$this->hasChanged = \true;
	}

	public function getBlockLight($x, $y, $z){
		$l = \ord($this->blockLight{($x << 10) | ($z << 6) | ($y >> 1)});
		if(($y & 1) === 0){
			return $l & 0x0F;
		}else{
			return $l >> 4;
		}
	}

	public function setBlockLight($x, $y, $z, $level){
		$i = ($x << 10) | ($z << 6) | ($y >> 1);
		$old_l = \ord($this->blockLight{$i});
		if(($y & 1) === 0){
			$this->blockLight{$i} = \chr(($old_l & 0xf0) | ($level & 0x0f));
		}else{
			$this->blockLight{$i} = \chr((($level & 0x0f) << 4) | ($old_l & 0x0f));
		}
		$this->hasChanged = \true;
	}

	public function getBlockIdColumn($x, $z){
		return \substr($this->blocks, ($x << 11) + ($z << 7), 128);
	}

	public function getBlockDataColumn($x, $z){
		return \substr($this->data, ($x << 10) + ($z << 6), 64);
	}

	public function getBlockSkyLightColumn($x, $z){
		return \substr($this->skyLight, ($x << 10) + ($z << 6), 64);
	}

	public function getBlockLightColumn($x, $z){
		return \substr($this->blockLight, ($x << 10) + ($z << 6), 64);
	}

	/**
	 * @return bool
	 */
	public function isPopulated(){
		return $this->nbt["TerrainPopulated"] > 0;
	}

	/**
	 * @param int $value
	 */
	public function setPopulated($value = 1){
		$this->nbt->TerrainPopulated = new Byte("TerrainPopulated", $value);
	}

	/**
	 * @return bool
	 */
	public function isGenerated(){
		return $this->nbt["TerrainPopulated"] > 0 or (isset($this->nbt->TerrainGenerated) and $this->nbt["TerrainGenerated"] > 0);
	}

	/**
	 * @param int $value
	 */
	public function setGenerated($value = 1){
		$this->nbt->TerrainGenerated = new Byte("TerrainGenerated", $value);
	}

	/**
	 * @param string        $data
	 * @param LevelProvider $provider
	 *
	 * @return Chunk
	 */
	public static function fromBinary($data, LevelProvider $provider = \null){
		$nbt = new NBT(NBT::BIG_ENDIAN);

		try{
			$nbt->readCompressed($data, ZLIB_ENCODING_DEFLATE);
			$chunk = $nbt->getData();

			if(!isset($chunk->Level) or !($chunk->Level instanceof Compound)){
				return \null;
			}

			return new Chunk($provider instanceof LevelProvider ? $provider : McRegion::class, $chunk->Level);
		}catch(\Exception $e){
			return \null;
		}
	}

	public static function fromFastBinary($data, LevelProvider $provider = null){

		try{
			$offset = 0;

			$chunk = new Chunk($provider instanceof LevelProvider ? $provider : McRegion::class, null);
			$chunk->provider = $provider;
			$chunk->x = Binary::readInt(substr($data, $offset, 4));
			$offset += 4;
			$chunk->z = Binary::readInt(substr($data, $offset, 4));
			$offset += 4;

			$chunk->blocks = substr($data, $offset, 32768);
			$offset += 32768;
			$chunk->data = substr($data, $offset, 16384);
			$offset += 16384;
			$chunk->skyLight = substr($data, $offset, 16384);
			$offset += 16384;
			$chunk->blockLight = substr($data, $offset, 16384);
			$offset += 16384;

			$chunk->heightMap = [];
			$chunk->biomeColors = [];
			$hm = unpack("C*", substr($data, $offset, 256));
			$offset += 256;
			$bc = unpack("N*", substr($data, $offset, 1024));
			$offset += 1024;

			for($i = 0; $i < 256; ++$i){
				$chunk->biomeColors[$i] = $bc[$i + 1];
				$chunk->heightMap[$i] = $hm[$i + 1];
			}

			$flags = ord($data{$offset++});

			$chunk->nbt->TerrainGenerated = new Byte("TerrainGenerated", $flags & 0b1);
			$chunk->nbt->TerrainPopulated = new Byte("TerrainPopulated", $flags >> 1);

			return $chunk;
		}catch(\Exception $e){
			return null;
		}
	}

	public function toFastBinary(){
		$biomeColors = pack("N*", ...$this->getBiomeColorArray());
		$heightMap = pack("N*", ...$this->getHeightMapArray());

		return
			Binary::writeInt($this->x) .
			Binary::writeInt($this->z) .
			$this->getBlockIdArray() .
			$this->getBlockDataArray() .
			$this->getBlockSkyLightArray() .
			$this->getBlockLightArray() .
			$this->getBiomeIdArray() .
			$biomeColors .
			$heightMap .
			chr(($this->isPopulated() ? 1 << 1 : 0) + ($this->isGenerated() ? 1 : 0));
	}

	public function toBinary(){
		$nbt = clone $this->getNBT();

		$nbt->xPos = new Int("xPos", $this->x);
		$nbt->zPos = new Int("zPos", $this->z);

		if($this->isGenerated()){
			$nbt->Blocks = new ByteArray("Blocks", $this->getBlockIdArray());
			$nbt->Data = new ByteArray("Data", $this->getBlockDataArray());
			$nbt->SkyLight = new ByteArray("SkyLight", $this->getBlockSkyLightArray());
			$nbt->BlockLight = new ByteArray("BlockLight", $this->getBlockLightArray());

			$nbt->Biomes = new ByteArray("Biomes", $this->getBiomeIdArray());
			$nbt->BiomeColors = new IntArray("BiomeColors", $this->getBiomeColorArray());

			$nbt->HeightMap = new IntArray("HeightMap", $this->getHeightMapArray());
		}

		$entities = [];

		foreach($this->getEntities() as $entity){
			if(!($entity instanceof Player) and !$entity->closed){
				$entity->saveNBT();
				$entities[] = $entity->namedtag;
			}
		}

		$nbt->Entities = new Enum("Entities", $entities);
		$nbt->Entities->setTagType(NBT::TAG_Compound);


		$tiles = [];
		foreach($this->getTiles() as $tile){
			$tile->saveNBT();
			$tiles[] = $tile->namedtag;
		}

		$nbt->TileEntities = new Enum("TileEntities", $tiles);
		$nbt->TileEntities->setTagType(NBT::TAG_Compound);
		$writer = new NBT(NBT::BIG_ENDIAN);
		$nbt->setName("Level");
		$writer->setData(new Compound("", ["Level" => $nbt]));

		return $writer->writeCompressed(ZLIB_ENCODING_DEFLATE, RegionLoader::$COMPRESSION_LEVEL);
	}

	/**
	 * @return Compound
	 */
	public function getNBT(){
		return $this->nbt;
	}
}