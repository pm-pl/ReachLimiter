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

use Jorgebyte\ReachLimiter\config\ReachSettings;
use Jorgebyte\ReachLimiter\core\ReachManager;
use Jorgebyte\ReachLimiter\notification\ViolationNotifier;
use Jorgebyte\ReachLimiter\util\Permissions;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

/**
 * Event listener for reach validation on player attacks.
 * Monitors EntityDamageByEntityEvent to validate attack distances and prevent exploits.
 *
 * @since 1.1.0
 */
final readonly class ReachCheckListener implements Listener
{
	public function __construct(
		private ReachSettings $settings,
		private ReachManager $reachManager,
		private ViolationNotifier $notifier
	) {
	}

	/**
	 * Handles entity damage events to validate reach distance.
	 * Ignores projectile damage and validates only direct melee hits.
	 *
	 * Priority: HIGH - Runs early to prevent further processing of invalid attacks
	 * HandleCancelled: false - Skips already-cancelled events
	 *
	 * @param EntityDamageByEntityEvent $event The damage event to validate
	 *
	 * @priority HIGH
	 *
	 * @handleCancelled false
	 *
	 * @since 1.1.0
	 */
	public function onAttack(EntityDamageByEntityEvent $event): void
	{
		// Only process direct melee attacks
		if (EntityDamageEvent::CAUSE_ENTITY_ATTACK !== $event->getCause()) {
			return;
		}

		$damager = $event->getDamager();
		$victim = $event->getEntity();

		// Validate entities and ensure player is not attacking themselves
		if (!$damager instanceof Player || !$victim instanceof Living || $damager === $victim) {
			return;
		}

		// Check world consistency and active settings
		$world = $damager->getWorld();

		if ($world !== $victim->getWorld() || !$this->settings->isWorldAllowed($world->getFolderName())) {
			return;
		}

		// Check permissions last to minimize expensive operations
		if ($damager->hasPermission(Permissions::BYPASS)) {
			return;
		}

		// Process reach validation
		$result = $this->reachManager->validate($damager, $victim);

		if (null !== $result) {
			$event->cancel();
			$this->notifier->notify($damager, $victim, $result);
		}
	}
}
