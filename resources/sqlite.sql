-- #! sqlite
-- #{ lifesteal
-- #  { init
-- #    { player
CREATE TABLE IF NOT EXISTS players (
    player TEXT PRIMARY KEY NOT NULL,
    hearts INTEGER NOT NULL DEFAULT 10,
    kills INTEGER NOT NULL DEFAULT 0,
    deaths INTEGER NOT NULL DEFAULT 0
);
-- #    }
-- #    { bans
CREATE TABLE IF NOT EXISTS bans (
    player TEXT PRIMARY KEY NOT NULL,
    expiry INTEGER NOT NULL,
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
INSERT OR REPLACE INTO players (player, hearts, kills, deaths) 
VALUES (:player, :hearts, :kills, :deaths);
-- #    }
-- #    { ban
-- #      :player string
-- #      :expiry int
-- #      :reason string
INSERT OR REPLACE INTO bans (player, expiry, reason)
VALUES (:player, :expiry, :reason);
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
-- # No initialization needed for SQLite
-- #      }
-- #      { kills
-- # No initialization needed for SQLite
-- #      }
-- #      { kdr
-- # No initialization needed for SQLite
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
         ELSE CAST(kills AS REAL) / deaths 
       END as value
FROM players
WHERE kills > 0
ORDER BY value DESC
LIMIT :limit;
-- #      }
-- #    }
-- #  }
-- #}