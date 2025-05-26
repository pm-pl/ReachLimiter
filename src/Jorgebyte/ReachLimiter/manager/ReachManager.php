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

namespace Jorgebyte\ReachLimiter\manager;

use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

final class ReachManager
{
    public static function isInReach(Player $attacker, Entity $victim, float $maxReach): bool
    {
        $maxReachSquared = $maxReach ** 2;
        $attackerPos = $attacker->getEyePos();
        $victimPos = self::getTargetPosition($victim);

        return $attackerPos->distanceSquared($victimPos) <= $maxReachSquared;
    }

    private static function getTargetPosition(Entity $entity): Vector3
    {
        return $entity->getPosition()->add(0, $entity->getEyeHeight() / 2, 0);
    }
}
