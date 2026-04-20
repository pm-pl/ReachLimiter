<?php

declare(strict_types=1);
/**
 * This file is part of the ReachLimiter plugin for PocketMine-MP.
 *
 * (c) Jorgebyte - ReachLimiter
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author  Jorgebyte
 * @link    https://discord.jorgebyte.com/
 * @license GNU General Public License v3.0
 */

namespace Jorgebyte\ReachLimiter\cache;

use pocketmine\player\Player;
use pocketmine\Server;

use function strtolower;

/**
 * Maintains a lightweight cache of online staff members eligible to receive violation notifications.
 * Automatically syncs with player join/quit events to stay current.
 *
 * @since 1.1.0
 */
final class StaffCache
{
	/** @var array<string, Player> Map of lowercase player names to Player objects */
	private array $staffByLowerName = [];

	public function __construct(
		private readonly string $staffPermission
	) {
	}

	/**
	 * Initializes the cache with all currently online staff members.
	 * Should be called once during plugin startup.
	 *
	 * @param Server $server The PocketMine server instance
	 *
	 * @since 1.1.0
	 */
	public function warmUp(Server $server): void
	{
		$this->staffByLowerName = [];

		foreach ($server->getOnlinePlayers() as $player) {
			$this->refresh($player);
		}
	}

	/**
	 * Updates or adds a player in the staff cache based on their permissions.
	 * Called when a player joins or when permissions change.
	 *
	 * @param Player $player The player to check and cache
	 *
	 * @since 1.1.0
	 */
	public function refresh(Player $player): void
	{
		$key = strtolower($player->getName());

		if ($player->hasPermission($this->staffPermission)) {
			$this->staffByLowerName[$key] = $player;

			return;
		}

		unset($this->staffByLowerName[$key]);
	}

	/**
	 * Removes a player from the staff cache.
	 * Should be called when a player disconnects.
	 *
	 * @param Player $player The player to remove
	 *
	 * @since 1.1.0
	 */
	public function remove(Player $player): void
	{
		unset($this->staffByLowerName[strtolower($player->getName())]);
	}

	/**
	 * Returns a current list of all valid staff members to notify.
	 * Automatically filters out offline players and rechecks permissions.
	 *
	 * @return list<Player> Array of online staff members with valid permissions
	 *
	 * @since 1.1.0
	 */
	public function getRecipients(): array
	{
		$recipients = [];

		foreach ($this->staffByLowerName as $key => $player) {
			if (!$player->hasPermission($this->staffPermission)) {
				unset($this->staffByLowerName[$key]);
				continue;
			}

			$recipients[] = $player;
		}

		return $recipients;
	}
}
