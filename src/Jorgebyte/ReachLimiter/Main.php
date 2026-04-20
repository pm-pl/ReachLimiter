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

namespace Jorgebyte\ReachLimiter;

use Jorgebyte\ReachLimiter\cache\StaffCache;
use Jorgebyte\ReachLimiter\config\ReachSettings;
use Jorgebyte\ReachLimiter\core\ReachManager;
use Jorgebyte\ReachLimiter\listener\ReachCheckListener;
use Jorgebyte\ReachLimiter\listener\StaffSessionListener;
use Jorgebyte\ReachLimiter\notification\ViolationNotifier;
use Jorgebyte\ReachLimiter\util\Permissions;
use pocketmine\plugin\PluginBase;

/**
 * Main entry point for the ReachLimiter plugin.
 * Initializes all components and registers event listeners.
 *
 * @since 1.1.0
 */
final class Main extends PluginBase
{
	protected function onEnable(): void
	{
		$this->saveDefaultConfig();

		$settings = ReachSettings::fromConfig($this->getConfig());

		$staffCache = new StaffCache(Permissions::STAFF_NOTIFY);
		$staffCache->warmUp($this->getServer());

		$reachManager = new ReachManager($settings);

		$notifier = new ViolationNotifier($settings, $staffCache);

		$pluginManager = $this->getServer()->getPluginManager();
		$pluginManager->registerEvents(new ReachCheckListener($settings, $reachManager, $notifier), $this);
		$pluginManager->registerEvents(new StaffSessionListener($staffCache), $this);
	}
}
