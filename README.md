![5f2cc91d628c3814618c0fd03a7ff8c67440f834](https://github.com/GabBiswajit/Lifesteal/assets/121815367/f79a66ec-ea2e-40a4-9467-f872b2157275)

# Lifesteal Plugin for PocketMine-MP API 5

A comprehensive Lifesteal plugin for PocketMine-MP servers, allowing players to gain hearts by defeating other players and lose hearts upon death.

## Features

- **Heart System**: Players gain or lose hearts based on PvP encounters
- **Database Integration**: Uses libasynql for SQLite/MySQL database support
- **Heart Items**: Players can withdraw hearts as physical items
- **Custom Recipes**: Configurable recipes for heart and revival items
- **Revival System**: Special "Revival Bacon" item that can bring banned players back
- **Ban Management**: Timed bans when players lose all hearts
- **Time Display**: Shows remaining ban time when players try to join
- **Admin Commands**: Complete set of commands for server administrators

## Commands

| Command | Description | Permission |
|---------|-------------|------------|
| `/lifesteal help` | Show help menu | lifesteal.command |
| `/lifesteal sethearts <player> <hearts>` | Set a player's hearts | lifesteal.command.sethearts |
| `/lifesteal gethearts [player]` | Get a player's hearts | lifesteal.command.gethearts |
| `/lifesteal resethearts <player>` | Reset a player's hearts | lifesteal.command.resethearts |
| `/lifesteal withdraw [amount]` | Withdraw hearts into items | lifesteal.command.withdraw |
| `/lifesteal revive` | Get a revival item | lifesteal.admin |
| `/lifesteal unban <player>` | Unban a player | lifesteal.admin |
| `/lifesteal reload` | Reload configuration | lifesteal.command.reload |

## Permissions

| Permission | Description | Default |
|------------|-------------|---------|
| `lifesteal.admin` | Access to all Lifesteal admin commands | op |
| `lifesteal.command.sethearts` | Allows setting a player's health | op |
| `lifesteal.command.gethearts` | Allows checking a player's health | true |
| `lifesteal.command.resethearts` | Allows resetting a player's health | op |
| `lifesteal.command.reload` | Allows reloading the plugin configuration | op |
| `lifesteal.command.withdraw` | Allows withdrawing hearts into items | true |
| `lifesteal.command.revive` | Allows getting or using revival items | op |

## Configuration

The plugin is highly configurable. You can modify:

- Default, minimum, and maximum heart values
- Heart gain/loss amounts
- Ban duration and messages
- Heart and revival item appearance and recipes
- Database connection settings

## Elimination System

When a player loses all their hearts, they are banned for a configurable period (default 7 days). The ban message shows the remaining time until they can rejoin.

## Revival System

Administrators can create "Revival Bacon" items that can be used to unban eliminated players. This can be crafted using the configured recipe or obtained through the `/lifesteal revive` command.

## Installation

1. Place the plugin in your server's plugins folder
2. Start the server to generate configuration files
3. Configure as needed in `plugins/Lifesteal/config.yml`
4. Restart the server

## Dependencies

- PocketMine-MP API 5.0.0+
- libasynql (included)

## Development

Developed by Biswajit.
