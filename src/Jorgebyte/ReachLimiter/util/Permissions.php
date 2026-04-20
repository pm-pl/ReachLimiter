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

namespace Jorgebyte\ReachLimiter\util;

/**
 * Constants for permission nodes used throughout the plugin.
 */
final class Permissions
{
	/** Permission to bypass reach checks @since 1.1.0 */
	public const BYPASS = 'reachlimiter.bypass';

	/** Permission to receive staff notifications @since 1.1.0 */
	public const STAFF_NOTIFY = 'reachlimiter.notify';

	private function __construct()
	{
	}
}
