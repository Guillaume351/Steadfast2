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
 * @link   http://www.pocketmine.net/
 *
 *
 */

namespace pocketmine\event\block;

use pocketmine\block\Block;
use pocketmine\event\Cancellable;
use pocketmine\item\Item;
use pocketmine\Player;

class BlockBreakEvent extends BlockEvent implements Cancellable{
	public static $handlerList = null;

	/** @var \pocketmine\Player */
	protected $player;

	/** @var \pocketmine\item\Item */
	protected $item;

	/** @var bool */
	protected $instaBreak = false;
	protected $drop = [];

	public function __construct(Player $player, Block $block, Item $item, $instaBreak = false, $drop = []){
		$this->block = $block;
		$this->item = $item;
		$this->player = $player;
		$this->instaBreak = (bool) $instaBreak;
		$this->drop = $drop;
	}

	public function getPlayer(){
		return $this->player;
	}

	public function getItem(){
		return $this->item;
	}

	public function getInstaBreak(){
		return $this->instaBreak;
	}

	/**
	 * @param boolean $instaBreak
	 */
	public function setInstaBreak($instaBreak){
		$this->instaBreak = (bool) $instaBreak;
	}
	
	public function getDrops() {
		return $this->drop;
	}
	
	public function setDrops($drop = []) {
		$this->drop = $drop;
	}
}