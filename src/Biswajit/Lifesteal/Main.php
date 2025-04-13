<?php

declare(strict_types=1);

namespace Biswajit\Lifesteal;

use Biswajit\Lifesteal\commands\LifestealCommand;
use Biswajit\Lifesteal\database\Database;
use Biswajit\Lifesteal\listeners\PlayerListener;
use Biswajit\Lifesteal\recipes\HeartRecipe;
use Biswajit\Lifesteal\utils\FormManager;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class Main extends PluginBase {
    /** @var Database Handles database interactions */
    private Database $database;

    /** @var FormManager Manages form handling */
    private FormManager $formManager;

    /** @var HeartRecipe Registers custom recipes */
    private HeartRecipe $heartRecipe;

    protected function onEnable(): void {
        // Save default resources
        $this->saveDefaultConfig();

        // Initialize database
        $this->database = new Database($this);

        // Initialize form manager
        $this->formManager = new FormManager($this);

        // Register recipes
        $this->heartRecipe = new HeartRecipe($this);

        // Register commands
        $this->getServer()->getCommandMap()->register("lifesteal", new LifestealCommand($this));

        // Register event listeners
        $this->getServer()->getPluginManager()->registerEvents(new PlayerListener($this), $this);

    }

    protected function onDisable(): void {
        // Close database connection
        if($this->database !== null) {
            $this->database->close();
        }

    }

    /**
     * Get database
     * 
     * @return Database
     */
    public function getDatabase(): Database {
        return $this->database;
    }

    /**
     * Get form manager
     * 
     * @return FormManager
     */
    public function getFormManager(): FormManager {
        return $this->formManager;
    }

    /**
     * Get heart recipe
     * 
     * @return HeartRecipe
     */
    public function getHeartRecipe(): HeartRecipe {
        return $this->heartRecipe;
    }
}
