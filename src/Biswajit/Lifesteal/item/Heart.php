<?php
declare(strict_types=1);

namespace Biswajit\Lifesteal\item;

use customiesdevs\customies\item\CreativeInventoryInfo;
use customiesdevs\customies\item\ItemComponentsTrait;
use pocketmine\item\ItemIdentifier;

class Heart extends \pocketmine\item\Item implements \customiesdevs\customies\item\ItemComponents{
	use ItemComponentsTrait;

	public function __construct(ItemIdentifier $identifier, string $name = "Heart"){
		parent::__construct($identifier, $name);
		$this->initComponent("heart", new CreativeInventoryInfo(CreativeInventoryInfo::CATEGORY_ITEMS));
	}

	public function isFireProof() : bool{
		return true;
	}
}
