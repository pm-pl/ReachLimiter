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

namespace Jorgebyte\ReachLimiter\notification;

use Jorgebyte\ReachLimiter\cache\StaffCache;
use Jorgebyte\ReachLimiter\config\ReachSettings;
use Jorgebyte\ReachLimiter\core\ReachCheckResult;
use pocketmine\entity\Living;
use pocketmine\player\Player;
use pocketmine\Server;
use WeakMap;

use function microtime;
use function round;
use function str_replace;
use function strtolower;

/**
 * Handles player/staff notifications and optional server logs for reach violations.
 * Uses cooldowns to prevent spam and maintains separate notification rates for players and staff.
 *
 * @since 1.1.0
 */
final class ViolationNotifier
{
	/** @var WeakMap<Player, float> Tracks last notification time for each player avoiding memory leaks */
	private WeakMap $lastPlayerNotifyAt;

	/** @var WeakMap<Player, array<string, float>> Tracks last notification time per staff member per violator avoiding memory leaks */
	private WeakMap $lastStaffNotifyAt;

	public function __construct(
		private readonly ReachSettings $settings,
		private readonly StaffCache $staffCache
	) {
		$this->lastPlayerNotifyAt = new WeakMap();
		$this->lastStaffNotifyAt = new WeakMap();
	}

	/**
	 * Processes a reach violation by notifying relevant parties and logging as needed.
	 *
	 * @param Player $attacker The player who violated reach limits
	 * @param Living $victim The target of the attack
	 * @param ReachCheckResult $result The computed violation details
	 *
	 * @since 1.1.0
	 */
	public function notify(Player $attacker, Living $victim, ReachCheckResult $result): void
	{
		$this->notifyAttacker($attacker, $victim, $result);
		$this->notifyStaff($attacker, $victim, $result);
		$this->logViolation($attacker, $victim, $result);
	}

	/**
	 * Sends a notification to the attacking player about their violation.
	 * Respects cooldown settings to avoid spam.
	 *
	 * @param Player $attacker The player who violated reach
	 * @param Living $victim The target entity
	 * @param ReachCheckResult $result The violation details
	 *
	 * @since 1.1.0
	 */
	private function notifyAttacker(Player $attacker, Living $victim, ReachCheckResult $result): void
	{
		if (!$this->settings->shouldNotifyPlayer()) {
			return;
		}

		$now = microtime(true);
		$last = $this->lastPlayerNotifyAt[$attacker] ?? 0.0;

		if (($now - $last) < $this->settings->getNotifyPlayerCooldown()) {
			return;
		}

		$attacker->sendMessage($this->formatMessage(
			$this->settings->getNotifyPlayerMessage(),
			$attacker,
			$victim,
			$result
		));
		$this->lastPlayerNotifyAt[$attacker] = $now;
	}

	/**
	 * Sends a notification to all online staff members with appropriate permissions.
	 * Each staff member has an independent cooldown per violating player.
	 *
	 * @param Player $attacker The player who violated reach
	 * @param Living $victim The target entity
	 * @param ReachCheckResult $result The violation details
	 *
	 * @since 1.1.0
	 */
	private function notifyStaff(Player $attacker, Living $victim, ReachCheckResult $result): void
	{
		if (!$this->settings->shouldNotifyStaff()) {
			return;
		}

		$now = microtime(true);
		$violatorName = strtolower($attacker->getName());
		$formattedMessage = null;

		foreach ($this->staffCache->getRecipients() as $staff) {
			$cooldowns = $this->lastStaffNotifyAt[$staff] ?? [];
			$last = $cooldowns[$violatorName] ?? 0.0;

			if (($now - $last) < $this->settings->getNotifyStaffCooldown()) {
				continue;
			}

			if (null === $formattedMessage) {
				$formattedMessage = $this->formatMessage(
					$this->settings->getNotifyStaffMessage(),
					$attacker,
					$victim,
					$result
				);
			}

			$staff->sendMessage($formattedMessage);

			$cooldowns[$violatorName] = $now;
			$this->lastStaffNotifyAt[$staff] = $cooldowns;
		}
	}

	/**
	 * Logs a violation to the server logger if logging is enabled.
	 * Uses debug level logging for detailed diagnostic information.
	 *
	 * @param Player $attacker The player who violated reach
	 * @param Living $victim The target entity
	 * @param ReachCheckResult $result The violation details
	 *
	 * @since 1.1.0
	 */
	private function logViolation(Player $attacker, Living $victim, ReachCheckResult $result): void
	{
		if (!$this->settings->shouldLogViolations()) {
			return;
		}

		Server::getInstance()->getLogger()->debug($this->formatMessage(
			'[ReachLimiter] %player% -> %target% (%distance%m > %allowed%m, ping=%ping%, world=%world%)',
			$attacker,
			$victim,
			$result
		));
	}

	/**
	 * Formats a message template by replacing placeholders with actual violation data.
	 * Supports: %player%, %target%, %distance%, %allowed%, %ping%, %world%
	 *
	 * @param string $message The template message
	 * @param Player $attacker The attacking player
	 * @param Living $victim The victim entity
	 * @param ReachCheckResult $result The violation result
	 *
	 * @return string The formatted message
	 *
	 * @since 1.1.0
	 */
	private function formatMessage(
		string $message,
		Player $attacker,
		Living $victim,
		ReachCheckResult $result
	): string {
		return str_replace(
			['%player%', '%target%', '%distance%', '%allowed%', '%ping%', '%world%'],
			[
				$attacker->getName(),
				$victim->getName(),
				(string)round($result->getDistance(), 2),
				(string)round($result->getAllowedDistance(), 2),
				(string)$result->getPing(),
				$attacker->getWorld()->getFolderName(),
			],
			$message
		);
	}
}
