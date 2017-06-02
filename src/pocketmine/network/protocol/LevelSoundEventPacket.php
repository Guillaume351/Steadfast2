<?php

namespace pocketmine\network\protocol;

class LevelSoundEventPacket extends PEPacket {

	const NETWORK_ID = Info::LEVEL_SOUND_EVENT_PACKET;
	const PACKET_NAME = "LEVEL_SOUND_EVENT_PACKET";
	
	const SOUND_NOTE = 72;

	public $eventId;
	public $x;
	public $y;
	public $z;
	public $blockId = 0;
	public $entityType = 1;
	public $babyMob = 0;
	public $global = 0;

	public function decode($playerProtocol) {
		
	}

	public function encode($playerProtocol) {
		if ($playerProtocol < Info::PROTOCOL_110 && $this->eventId == self::SOUND_NOTE) {
			$this->eventId = 70;
		}
		$this->reset($playerProtocol);
		$this->putByte($this->eventId);
		$this->putLFloat($this->x);
		$this->putLFloat($this->y);
		$this->putLFloat($this->z);
		$this->putSignedVarInt($this->blockId);
		$this->putSignedVarInt($this->entityType);
		$this->putByte($this->babyMob);
		$this->putByte($this->global);
	}

}
