<?php

declare(strict_types=1);

/**
 * This file is part of the ReachLimiter plugin for PocketMine-MP.
 *
 * @package   ReachLimiter
 * @author    Jorgebyte
 * @version   1.0.0
 * @api       5.0.0
 * @license   GNU General Public License v3.0
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Jorgebyte\ReachLimiter;

use pocketmine\plugin\PluginBase;

class Main extends PluginBase
{
    private const DEFAULT_MAX_REACH = 3.85;

    protected function onEnable(): void
    {
        $this->saveResource("config.yml");
        $maxReach = floatval($this->getConfig()->get("max-reach", self::DEFAULT_MAX_REACH));
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($maxReach), $this);
    }
}
