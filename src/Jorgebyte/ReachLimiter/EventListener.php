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

use Jorgebyte\ReachLimiter\manager\ReachManager;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

final readonly class EventListener implements Listener
{
    public function __construct(
        private float $maxReach,
    ) {
    }

    /**
     * @priority HIGH
     * @handleCancelled false
     */
    public function onAttack(EntityDamageByEntityEvent $event): void
    {
        $damager = $event->getDamager();
        $entity = $event->getEntity();

        if (!$damager instanceof Player || !$entity instanceof Living || $event->isCancelled()) {
            return;
        }

        if (!ReachManager::isInReach($damager, $entity, $this->maxReach)) {
            $event->cancel();
        }
    }
}
