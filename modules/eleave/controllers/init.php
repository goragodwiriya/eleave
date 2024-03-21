<?php
/**
 * @filesource modules/eleave/controllers/init.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Init;

/**
 * Init Module
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller
{
    /**
     * รายการ permission ของโมดูล
     *
     * @param array $permissions
     *
     * @return array
     */
    public static function updatePermissions($permissions)
    {
        $permissions['can_manage_eleave'] = '{LNG_Can set the module} ({LNG_E-Leave})';
        $permissions['can_approve_eleave'] = '{LNG_Can be approve} ({LNG_E-Leave})';
        return $permissions;
    }
}
