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

namespace Jorgebyte\ReachLimiter\listener;

use Jorgebyte\ReachLimiter\cache\StaffCache;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

/**
 * Event listener for staff session management.
 * Keeps the staff notification cache in sync with player join/quit events.
 * Ensures staff members are available for notifications as soon as they join.
 *
 * @since 1.1.0
 */
final readonly class StaffSessionListener implements Listener
{
	public function __construct(
		private StaffCache $staffCache
	) {
	}

	/**
	 * Called when a player joins the server.
	 * Refreshes the staff cache in case the joining player has staff permissions.
	 *
	 * @param PlayerJoinEvent $event The player join event
	 *
	 * @since 1.1.0
	 */
	public function onJoin(PlayerJoinEvent $event): void
	{
		$this->staffCache->refresh($event->getPlayer());
	}

	/**
	 * Called when a player disconnects from the server.
	 * Removes the player from the staff cache if they were cached.
	 *
	 * @param PlayerQuitEvent $event The player quit event
	 *
	 * @since 1.1.0
	 */
	public function onQuit(PlayerQuitEvent $event): void
	{
		$this->staffCache->remove($event->getPlayer());
	}
}
