<?php

declare(strict_types=1);

namespace Biswajit\Lifesteal\recipes;

use Biswajit\Lifesteal\Main;
use Biswajit\Lifesteal\utils\HeartItem;
use Biswajit\Lifesteal\utils\RevivalItem;
use pocketmine\crafting\ShapedRecipe;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\block\VanillaBlocks;
use pocketmine\utils\TextFormat;

class HeartRecipe {
    /** @var Main */
    private $plugin;
    
    /**
     * HeartRecipe constructor
     * 
     * @param Main $plugin
     */
    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        
        // Register heart recipe
        $this->registerHeartRecipe();
        
        // Register revival recipe
        $this->registerRevivalRecipe();
    }
    
    /**
     * Register heart recipe
     */
    public function registerHeartRecipe(): void {
        $config = $this->plugin->getConfig();
        
        // Check if recipe is enabled
        if(!$config->getNested("heart-recipe.enabled", true)) {
            return;
        }
        
        // Get recipe shape
        $shape = $config->getNested("heart-recipe.shape", [
            "GDG",
            "DND",
            "GDG"
        ]);
        
        // Get recipe ingredients
        $ingredientMap = $config->getNested("heart-recipe.ingredients", [
            "G" => "GOLD_BLOCK",
            "D" => "DIAMOND_BLOCK",
            "N" => "NETHERITE_INGOT"
        ]);
        
        // Get amount
        $amount = $config->getNested("heart-recipe.amount", 1);
        
        // Create ingredient map
        $ingredients = [];
        foreach($ingredientMap as $char => $itemName) {
            $item = $this->getItemFromString($itemName);
            if($item !== null) {
                $ingredients[$char] = $item;
            }
        }
        
        // Check if ingredients are valid
        if(count($ingredients) === 0) {
            $this->plugin->getLogger()->warning("Invalid heart recipe ingredients! Recipe won't be registered.");
            return;
        }
        
        // Create heart item
        $heartName = $config->getNested("heart-item.name", "§c§lHeart");
        $heartLore = $config->getNested("heart-item.lore", ["§7Right-click to gain 1 heart"]);
        $heartItem = HeartItem::create($heartName, $heartLore, $amount);
        
        // Register recipe
        $recipe = new ShapedRecipe($shape, $ingredients, [$heartItem]);
        $this->plugin->getServer()->getCraftingManager()->registerShapedRecipe($recipe);
    }
    
    /**
     * Register revival recipe
     */
    public function registerRevivalRecipe(): void {
        $config = $this->plugin->getConfig();
        
        // Check if revival is enabled
        if(!$config->getNested("revival.enabled", true)) {
            return;
        }
        
        // Check if recipe is enabled
        if(!$config->getNested("revival.recipe.enabled", true)) {
            return;
        }
        
        // Get recipe shape
        $shape = $config->getNested("revival.recipe.shape", [
            "GTG",
            "THT",
            "GTG"
        ]);
        
        // Get recipe ingredients
        $ingredientMap = $config->getNested("revival.recipe.ingredients", [
            "G" => "GOLD_BLOCK",
            "T" => "TOTEM",
            "H" => "BEACON"
        ]);
        
        // Get amount
        $amount = $config->getNested("revival.recipe.amount", 1);
        
        // Create ingredient map
        $ingredients = [];
        foreach($ingredientMap as $char => $itemName) {
            $item = $this->getItemFromString($itemName);
            if($item !== null) {
                $ingredients[$char] = $item;
            }
        }
        
        // Check if ingredients are valid
        if(count($ingredients) === 0) {
            $this->plugin->getLogger()->warning("Invalid revival recipe ingredients! Recipe won't be registered.");
            return;
        }
        
        // Create revival item
        $revivalName = $config->getNested("revival.item-name", "§6§lRevival Bacon");
        $revivalLore = $config->getNested("revival.item-lore", ["§7Use this item to revive a banned player"]);
        $revivalItem = RevivalItem::create($revivalName, $revivalLore, $amount);
        
        // Register recipe
        $recipe = new ShapedRecipe($shape, $ingredients, [$revivalItem]);
        $this->plugin->getServer()->getCraftingManager()->registerShapedRecipe($recipe);
    }
    
    /**
     * Get item from string
     * 
     * @param string $itemName
     * @return Item|null
     */
    private function getItemFromString(string $itemName): ?Item {
        // Common item names mapping
        $itemMap = [
            "GOLD_BLOCK" => VanillaBlocks::GOLD(),
            "DIAMOND_BLOCK" => VanillaBlocks::DIAMOND(),
            "NETHERITE_INGOT" => VanillaItems::NETHERITE_INGOT(),
            "TOTEM" => VanillaItems::TOTEM(),
            "BEACON" => VanillaItems::BEACON(),
            "IRON_BLOCK" => VanillaBlocks::IRON(),
            "EMERALD_BLOCK" => VanillaBlocks::EMERALD(),
            "OBSIDIAN" => VanillaItems::OBSIDIAN(),
            "TNT" => VanillaItems::TNT(),
            "DIAMOND" => VanillaItems::DIAMOND(),
            "GOLD_INGOT" => VanillaItems::GOLD_INGOT(),
            "IRON_INGOT" => VanillaItems::IRON_INGOT(),
            "EMERALD" => VanillaItems::EMERALD(),
            "ENDER_PEARL" => VanillaItems::ENDER_PEARL(),
            "ENDER_EYE" => VanillaItems::ENDER_EYE(),
            "NETHER_STAR" => VanillaItems::NETHER_STAR()
        ];
        
        // Check if item exists in map
        if(isset($itemMap[$itemName])) {
            return $itemMap[$itemName];
        }
        
        return null;
    }
}
