-- #! mysql
-- #{ lifesteal
-- #  { init
-- #    { player
CREATE TABLE IF NOT EXISTS players (
    player VARCHAR(16) PRIMARY KEY NOT NULL,
    hearts INT NOT NULL DEFAULT 10,
    kills INT NOT NULL DEFAULT 0,
    deaths INT NOT NULL DEFAULT 0
);
-- #    }
-- #    { bans
CREATE TABLE IF NOT EXISTS bans (
    player VARCHAR(16) PRIMARY KEY NOT NULL,
    expiry INT NOT NULL,
    reason TEXT NOT NULL
);
-- #    }
-- #  }
-- #  { get
-- #    { player
-- #      :player string
SELECT * FROM players WHERE player = :player;
-- #    }
-- #    { ban
-- #      :player string
SELECT * FROM bans WHERE player = :player;
-- #    }
-- #    { all_bans
SELECT * FROM bans;
-- #    }
-- #  }
-- #  { update
-- #    { player
-- #      :player string
-- #      :hearts int
-- #      :kills int
-- #      :deaths int
INSERT INTO players (player, hearts, kills, deaths) 
VALUES (:player, :hearts, :kills, :deaths)
ON DUPLICATE KEY UPDATE hearts = :hearts, kills = :kills, deaths = :deaths;
-- #    }
-- #    { ban
-- #      :player string
-- #      :expiry int
-- #      :reason string
INSERT INTO bans (player, expiry, reason)
VALUES (:player, :expiry, :reason)
ON DUPLICATE KEY UPDATE expiry = :expiry, reason = :reason;
-- #    }
-- #  }
-- #  { delete
-- #    { ban
-- #      :player string
DELETE FROM bans WHERE player = :player;
-- #    }
-- #    { expired_bans
-- #      :time int
DELETE FROM bans WHERE expiry < :time AND expiry > 0;
-- #    }
-- #  }
-- #  { reset
-- #    { player
-- #      :player string
DELETE FROM players WHERE player = :player;
-- #    }
-- #  }
-- #  { leaderboard
-- #    { init
-- #      { hearts
-- # No initialization needed for MySQL
-- #      }
-- #      { kills
-- # No initialization needed for MySQL
-- #      }
-- #      { kdr
-- # No initialization needed for MySQL
-- #      }
-- #    }
-- #    { get
-- #      { hearts
-- #        :limit int
SELECT player, hearts as value
FROM players
WHERE hearts > 0
ORDER BY hearts DESC
LIMIT :limit;
-- #      }
-- #      { kills
-- #        :limit int
SELECT player, kills as value
FROM players
WHERE kills > 0
ORDER BY kills DESC
LIMIT :limit;
-- #      }
-- #      { kdr
-- #        :limit int
SELECT player, 
       CASE 
         WHEN deaths = 0 THEN kills 
         ELSE CAST(kills AS FLOAT) / deaths 
       END as value
FROM players
WHERE kills > 0
ORDER BY value DESC
LIMIT :limit;
-- #      }
-- #    }
-- #  }
-- #}