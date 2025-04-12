<?php

declare(strict_types=1);

namespace Biswajit\Lifesteal\database;

use Biswajit\Lifesteal\Main;
use Biswajit\Lifesteal\utils\BanManager;
use Biswajit\Lifesteal\utils\HealthManager;
use Biswajit\Lifesteal\utils\LeaderboardManager;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;

class Database {
    /** @var Main */
    private $plugin;
    
    /** @var DataConnector */
    private $database;
    
    /** @var BanManager */
    private $banManager;
    
    /** @var HealthManager */
    private $healthManager;
    
    /** @var LeaderboardManager */
    private $leaderboardManager;
    
    /**
     * Database constructor
     * 
     * @param Main $plugin
     */
    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        
        // Initialize database
        $this->initDatabase();
        
        // Initialize managers
        $this->banManager = new BanManager($plugin, $this->database);
        $this->healthManager = new HealthManager($plugin, $this->database, $this->banManager);
        $this->leaderboardManager = new LeaderboardManager($plugin, $this->database);
    }
    
    /**
     * Initialize database
     */
    private function initDatabase(): void {
        $config = $this->plugin->getConfig()->get("database");
        
        // Create database connector
        $this->database = libasynql::create($this->plugin, $config, [
            "sqlite" => "sqlite.sql",
            "mysql" => "mysql.sql"
        ]);
        
        // Initialize tables
        $this->database->executeGeneric("lifesteal.init.player");
        $this->database->executeGeneric("lifesteal.init.bans");
        
        // Wait for completion
        $this->database->waitAll();
    }
    
    /**
     * Get database connector
     * 
     * @return DataConnector
     */
    public function getConnector(): DataConnector {
        return $this->database;
    }
    
    /**
     * Get ban manager
     * 
     * @return BanManager
     */
    public function getBanManager(): BanManager {
        return $this->banManager;
    }
    
    /**
     * Get health manager
     * 
     * @return HealthManager
     */
    public function getHealthManager(): HealthManager {
        return $this->healthManager;
    }
    
    /**
     * Get leaderboard manager
     * 
     * @return LeaderboardManager
     */
    public function getLeaderboardManager(): LeaderboardManager {
        return $this->leaderboardManager;
    }
    
    /**
     * Close database connection
     */
    public function close(): void {
        if($this->database !== null) {
            $this->database->waitAll();
            $this->database->close();
        }
    }
}
