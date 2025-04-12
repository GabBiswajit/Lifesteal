<?php

declare(strict_types=1);

namespace Biswajit\Lifesteal\utils;

use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\nbt\tag\ByteTag;

class HeartItem {
    /** @var string */
    private const HEART_ITEM_TAG = "lifesteal:heart_item";
    
    /**
     * Check if item is a heart item
     * 
     * @param Item $item
     * @return bool
     */
    public static function isHeartItem(Item $item): bool {
        $tag = $item->getNamedTag();
        return $tag?->getTag(self::HEART_ITEM_TAG) !== null;
    }
    
    /**
     * Create a heart item
     * 
     * @param string $name
     * @param array $lore
     * @param int $amount
     * @return Item
     */
    public static function create(string $name, array $lore, int $amount = 1): Item {
        // Use an apple as the heart item
        $item = VanillaItems::APPLE()->setCount($amount);
        
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
        $nbt->setTag(self::HEART_ITEM_TAG, new ByteTag(1));
        $item->setNamedTag($nbt);
        
        return $item;
    }
}