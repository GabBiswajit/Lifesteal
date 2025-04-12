<?php

declare(strict_types=1);

namespace Biswajit\Lifesteal\utils;

use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\nbt\tag\ByteTag;

class RevivalItem {
    /** @var string */
    private const REVIVAL_ITEM_TAG = "lifesteal:revival_item";
    
    /**
     * Check if item is a revival item
     * 
     * @param Item $item
     * @return bool
     */
    public static function isRevivalItem(Item $item): bool {
        $tag = $item->getNamedTag();
        return $tag->getTag(self::REVIVAL_ITEM_TAG) !== null;
    }
    
    /**
     * Create a revival item (bacon)
     * 
     * @param string $name
     * @param array $lore
     * @param int $amount
     * @return Item
     */
    public static function create(string $name, array $lore, int $amount = 1): Item {
        // Use cooked porkchop (bacon) as the revival item
        $item = VanillaItems::COOKED_PORKCHOP()->setCount($amount);
        
        // Set custom name and lore
        $item->setCustomName($name);
        $item->setLore($lore);
        
        // Add glowing effect (enchantment)
        $item->addEnchantment(new EnchantmentInstance(
            VanillaEnchantments::UNBREAKING(),
            1
        ));
        
        // Add custom tag
        $nbt = $item->getNamedTag();
        $nbt->setTag(self::REVIVAL_ITEM_TAG, new ByteTag(1));
        $item->setNamedTag($nbt);
        
        return $item;
    }
}