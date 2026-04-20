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

namespace Jorgebyte\ReachLimiter\core;

use Jorgebyte\ReachLimiter\config\ReachSettings;
use pocketmine\entity\Living;
use pocketmine\player\Player;
use WeakMap;

use function array_shift;
use function count;
use function floor;
use function sort;
use function sqrt;

/**
 * Central service for reach validation logic.
 * Encapsulates all business logic related to reach checking and validation.
 *
 * @since 1.1.0
 */
final class ReachManager
{
	/** @var int Amount of recent ping values to keep for median calculation */
	private const MAX_PING_SAMPLES = 5;

	/** @var WeakMap<Player, list<int>> Tracks recent ping samples for lag-spike filtering */
	private WeakMap $pingHistory;

	public function __construct(
		private readonly ReachSettings $settings
	) {
		$this->pingHistory = new WeakMap();
	}

	/**
	 * Validates if an attacker's reach to a victim exceeds the allowed distance.
	 *
	 * Returns null if the check should be ignored (high ping, etc.),
	 * or a ReachCheckResult if a violation was detected.
	 *
	 * @param Player $attacker The player performing the action
	 * @param Living $victim The target being attacked
	 *
	 * @return ReachCheckResult|null The check result, or null if not a violation
	 *
	 * @since 1.1.0
	 */
	public function validate(Player $attacker, Living $victim): ?ReachCheckResult
	{
		$rawPing = $attacker->getNetworkSession()->getPing();
		$ping = $this->getSmoothedPing($attacker, $rawPing);

		$maxPingIgnore = $this->settings->getMaxPingIgnoreCheck();

		// Skip validation if smoothed ping is too high
		if ($maxPingIgnore > 0 && $ping > $maxPingIgnore) {
			return null;
		}

		$allowed = $this->settings->getAllowedReachForPing($ping);

		// Calculates the exact distance from the attacker's eyes to the edge of the victim's bounding box
		// This eliminates false positives caused by hitting the edge of a player's hitbox
		$eyePos = $attacker->getEyePos();
		$targetBB = $victim->getBoundingBox();

		$closestX = max($targetBB->minX, min($eyePos->x, $targetBB->maxX));
		$closestY = max($targetBB->minY, min($eyePos->y, $targetBB->maxY));
		$closestZ = max($targetBB->minZ, min($eyePos->z, $targetBB->maxZ));

		$dx = $eyePos->x - $closestX;
		$dy = $eyePos->y - $closestY;
		$dz = $eyePos->z - $closestZ;

		$distanceSquared = ($dx ** 2) + ($dy ** 2) + ($dz ** 2);

		if ($distanceSquared <= ($allowed ** 2)) {
			return null;
		}

		return new ReachCheckResult(sqrt($distanceSquared), $allowed, $ping);
	}

	/**
	 * Calculates a smoothed ping using a rolling median filter.
	 * This successfully prevents sudden network lag spikes from causing false positives.
	 *
	 * @param Player $player The attacking player
	 * @param int|null $currentPing The instantaneous raw network ping
	 *
	 * @return int The smoothed median ping stable value
	 */
	private function getSmoothedPing(Player $player, ?int $currentPing): int
	{
		// Fallback safely if ping is not resolved
		if (null === $currentPing) {
			return 0;
		}

		$history = $this->pingHistory[$player] ?? [];
		$history[] = $currentPing;

		if (count($history) > self::MAX_PING_SAMPLES) {
			array_shift($history);
		}

		$this->pingHistory[$player] = $history;

		// Sort history array to extract the median
		$sorted = $history;
		sort($sorted);

		$midIndex = (int)floor((count($sorted) - 1) / 2);

		return $sorted[$midIndex];
	}
}
