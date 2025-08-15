<?php

declare(strict_types = 1);

namespace Biswajit\Lifesteal;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use Biswajit\Lifesteal\EventListener;
use pocketmine\item\VanillaItems;
use pocketmine\utils\Config;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use customiesdevs\customies\item\CustomiesItemFactory;
use Biswajit\Lifesteal\item\Heart;
use pocketmine\crafting\ExactRecipeIngredient;
use pocketmine\crafting\ShapedRecipe;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\scheduler\ClosureTask;
use Symfony\Component\Filesystem\Path;
use function array_merge;

class Main extends PluginBase {

      /** @var Config */
      private Config $playerData;
      private Config $config;
      private static Main $instance;

     public static function getInstance(): Main
    {
        return self::$instance;
    }

	public function onEnable(): void{

        self::$instance = $this;
        API::getInstance()->init($this);

        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);

        $this->saveResource("config.yml");
        $this->saveResource("Lifesteal.mcpack");
        
		$rpManager = $this->getServer()->getResourcePackManager();
		$rpManager->setResourceStack(array_merge($rpManager->getResourceStack(), [new ZippedResourcePack(Path::join($this->getDataFolder(), "Lifesteal.mcpack"))]));
		(new \ReflectionProperty($rpManager, "serverForceResources"))->setValue($rpManager, true);

		CustomiesItemFactory::getInstance()->registerItem(heart::class, "lifesteal:heart", "heart");

         $this->playerData = new Config($this->getDataFolder() . "playerdata.yml", Config::YAML);
         $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, array());

		if($this->getConfig()->get("register-recipes")){
            $this->registerRecipes();
        }
     }

public function getPlayerData(): Config {
    return $this->playerData;
}

public function getConfig(): Config {
    return $this->config;
}

public function registerRecipes(): void {
    $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() : void{
        $heart = CustomiesItemFactory::getInstance()->get("lifesteal:heart");
        $dataFolder = $this->getDataFolder();
        $configFilePath = $dataFolder . 'config.yml';
        $config = yaml_parse_file($configFilePath);
        $recipe1 = $config['RecipeItem1'];
        $recipe2 = $config['RecipeItem2'];
        $recipe3 = $config['RecipeItem3'];
        $this->getServer()->getCraftingManager()->registerShapedRecipe(new ShapedRecipe(
            [
                "CBC",
                "BAB",
                "CBC"
            ],
            [
                "A" => new ExactRecipeIngredient(VanillaItems::$recipe1()),
                "B" => new ExactRecipeIngredient(VanillaItems::$recipe2()),
                "C" => new ExactRecipeIngredient(VanillaItems::$recipe3())
            ],
            [$heart]
        ));
    }), 2);
}

public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
    if ($command->getName() === "withdrawal") {

        if (!$sender instanceof Player) {
            $sender->sendMessage("This command can only be used in-game.");
            return false;
        }
        if (!isset($args[0])) {
            $sender->sendMessage("§e/withdrawal <amount>");
            return false;
            }

        $amount = (int) $args[0] + (int) $args[0];
        $health = $sender->getHealth();
        if($health <= $amount) {
         $sender->sendMessage("§cYou Don't Have $args[0] Health To Withdrawal !!");
            return false;
        }

        $heart = CustomiesItemFactory::getInstance()->get("lifesteal:heart");
        $heart->setCount((int)$args[0]);

        if (!$sender->getInventory()->canAddItem($heart)) {
            $sender->sendMessage("§cYour Inventory is Full. Please Empty it!");
            return false;
        }

    	$health = $sender->getHealth();
        $playerhealth = (int) $args[0];
        $amount = $playerhealth + $playerhealth;
        $sender->setMaxHealth($sender->getMaxHealth() - $amount);
        $sender->getInventory()->addItem($heart);
        $sender->sendMessage("§l§aYou have successfully withdrawn a heart.");
    }
	return false;
  }
}
