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

/**
 * Value object representing the results of a reach validation check.
 * Contains computed distance, allowed distance, and player ping information.
 *
 * @since 1.1.0
 */
final readonly class ReachCheckResult
{
	public function __construct(
		private float $distance,
		private float $allowedDistance,
		private int $ping
	) {
	}

	/**
	 * Gets the actual distance between attacker and victim.
	 *
	 * @return float The distance in blocks
	 */
	public function getDistance(): float
	{
		return $this->distance;
	}

	/**
	 * Gets the maximum allowed distance for this player.
	 *
	 * @return float The allowed reach distance in blocks
	 */
	public function getAllowedDistance(): float
	{
		return $this->allowedDistance;
	}

	/**
	 * Gets the player's network ping at time of check.
	 *
	 * @return int The ping in milliseconds
	 */
	public function getPing(): int
	{
		return $this->ping;
	}
}
