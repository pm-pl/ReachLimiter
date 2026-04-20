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

namespace Jorgebyte\ReachLimiter\config;

use pocketmine\utils\Config;

use function array_map;
use function floor;
use function is_array;
use function is_string;
use function strtolower;
use function trim;

/**
 * Immutable runtime settings for reach validation and notifications.
 * Loaded from configuration file and provides centralized access to all plugin settings.
 *
 * @since 1.1.0
 */
final readonly class ReachSettings
{
	/** @var array<string, true> Map of allowed world names for quick lookup */
	private array $allowedWorldsMap;

	/**
	 * Constructs reach settings with all configuration parameters.
	 *
	 * @param float $maxReach Maximum reach distance in blocks
	 * @param float $extraBuffer Extra buffer tolerance
	 * @param int $maxPingIgnoreCheck Maximum ping to ignore checks
	 * @param float $pingBufferPer100ms Buffer per 100ms of ping
	 * @param list<string> $allowedWorlds Worlds where reach checking is enabled
	 * @param bool $notifyPlayer Whether to notify violating players
	 * @param string $notifyPlayerMessage Message shown to violating players
	 * @param float $notifyPlayerCooldown Cooldown for player notifications
	 * @param bool $notifyStaff Whether to notify staff members
	 * @param string $notifyStaffMessage Message shown to staff
	 * @param float $notifyStaffCooldown Cooldown for staff notifications
	 * @param bool $logViolations Whether to log violations to console
	 *
	 * @since 1.1.0
	 */
	public function __construct(
		private float $maxReach,
		private float $extraBuffer,
		private int $maxPingIgnoreCheck,
		private float $pingBufferPer100ms,
		array $allowedWorlds,
		private bool $notifyPlayer,
		private string $notifyPlayerMessage,
		private float $notifyPlayerCooldown,
		private bool $notifyStaff,
		private string $notifyStaffMessage,
		private float $notifyStaffCooldown,
		private bool $logViolations
	) {
		$allowedWorldsMap = [];

		foreach ($allowedWorlds as $worldName) {
			$worldName = strtolower(trim($worldName));

			if ('' !== $worldName) {
				$allowedWorldsMap[$worldName] = true;
			}
		}
		$this->allowedWorldsMap = $allowedWorldsMap;
	}

	/**
	 * Factory method to create ReachSettings from a PocketMine Config object.
	 *
	 * @param Config $config The configuration to load settings from
	 *
	 * @return self A new ReachSettings instance
	 *
	 * @since 1.1.0
	 */
	public static function fromConfig(Config $config): self
	{
		$raw = $config->getAll();

		$legacyMaxReach = (float)($raw['max-reach'] ?? 3.85);
		$checks = is_array($raw['checks'] ?? null) ? $raw['checks'] : [];
		$notify = is_array($raw['notify'] ?? null) ? $raw['notify'] : [];
		$playerNotify = is_array($notify['player'] ?? null) ? $notify['player'] : [];
		$staffNotify = is_array($notify['staff'] ?? null) ? $notify['staff'] : [];

		$worldsRaw = $raw['worlds'] ?? [];
		$worlds = [];

		if (is_array($worldsRaw)) {
			$worlds = array_map(
				static fn (mixed $world): string => is_string($world) ? $world : '',
				$worldsRaw
			);
		}

		$maxReachRaw = $checks['max-reach'] ?? $legacyMaxReach;
		$extraBufferRaw = $checks['extra-buffer'] ?? 0.45;
		$pingIgnoreRaw = $checks['max-ping-ignore-check'] ?? 220;
		$pingBufferRaw = $checks['ping-buffer-per-100ms'] ?? 0.10;

		return new self(
			max(0.1, is_numeric($maxReachRaw) ? (float)$maxReachRaw : $legacyMaxReach),
			max(0.0, is_numeric($extraBufferRaw) ? (float)$extraBufferRaw : 0.45),
			max(0, is_numeric($pingIgnoreRaw) ? (int)$pingIgnoreRaw : 220),
			max(0.0, is_numeric($pingBufferRaw) ? (float)$pingBufferRaw : 0.10),
			$worlds,
			(bool)($playerNotify['enabled'] ?? true),
			(string)($playerNotify['message'] ?? '§cHit cancelled: target is out of allowed reach.'),
			max(0.0, (float)($playerNotify['cooldown'] ?? 1.2)),
			(bool)($staffNotify['enabled'] ?? true),
			(string)($staffNotify['message'] ?? 'e[ReachLimiter] %player% -> %target% (%distance%m > %allowed%m, ping=%ping%)'),
			max(0.0, (float)($staffNotify['cooldown'] ?? 0.8)),
			(bool)($raw['log-violations'] ?? false)
		);
	}

	/**
	 * Calculates the allowed reach distance accounting for player ping.
	 *
	 * @param int $ping The player's network ping in milliseconds
	 *
	 * @return float The maximum allowed reach distance
	 *
	 * @since 1.1.0
	 */
	public function getAllowedReachForPing(int $ping): float
	{
		$allowedReach = $this->maxReach + $this->extraBuffer;

		if ($this->pingBufferPer100ms <= 0.0 || $ping <= 0) {
			return $allowedReach;
		}

		return $allowedReach + (floor($ping / 100) * $this->pingBufferPer100ms);
	}

	/**
	 * Checks if a world is allowed for reach validation.
	 *
	 * @param string $worldName The world name to check
	 *
	 * @return bool True if checking is enabled for this world
	 *
	 * @since 1.1.0
	 */
	public function isWorldAllowed(string $worldName): bool
	{
		if ([] === $this->allowedWorldsMap) {
			return true;
		}

		return isset($this->allowedWorldsMap[strtolower($worldName)]);
	}

	/**
	 * Gets the maximum ping threshold before skipping checks entirely.
	 *
	 * @return int The threshold in milliseconds (0 = no limit)
	 *
	 * @since 1.1.0
	 */
	public function getMaxPingIgnoreCheck(): int
	{
		return $this->maxPingIgnoreCheck;
	}

	/**
	 * Checks if player notifications are enabled.
	 *
	 * @return bool True if enabled
	 *
	 * @since 1.1.0
	 */
	public function shouldNotifyPlayer(): bool
	{
		return $this->notifyPlayer;
	}

	/**
	 * Gets the message template for player notifications.
	 *
	 * @return string The notification message template
	 *
	 * @since 1.1.0
	 */
	public function getNotifyPlayerMessage(): string
	{
		return $this->notifyPlayerMessage;
	}

	/**
	 * Gets the cooldown between player notifications.
	 *
	 * @return float The cooldown in seconds
	 *
	 * @since 1.1.0
	 */
	public function getNotifyPlayerCooldown(): float
	{
		return $this->notifyPlayerCooldown;
	}

	/**
	 * Checks if staff notifications are enabled.
	 *
	 * @return bool True if enabled
	 *
	 * @since 1.1.0
	 */
	public function shouldNotifyStaff(): bool
	{
		return $this->notifyStaff;
	}

	/**
	 * Gets the message template for staff notifications.
	 *
	 * @return string The notification message template
	 *
	 * @since 1.1.0
	 */
	public function getNotifyStaffMessage(): string
	{
		return $this->notifyStaffMessage;
	}

	/**
	 * Gets the cooldown between staff notifications for the same player.
	 *
	 * @return float The cooldown in seconds
	 *
	 * @since 1.1.0
	 */
	public function getNotifyStaffCooldown(): float
	{
		return $this->notifyStaffCooldown;
	}

	/**
	 * Checks if violation logging is enabled.
	 *
	 * @return bool True if logging enabled
	 *
	 * @since 1.1.0
	 */
	public function shouldLogViolations(): bool
	{
		return $this->logViolations;
	}
}
