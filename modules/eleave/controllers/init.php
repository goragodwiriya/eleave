<?php
/**
 * @filesource modules/eleave/controllers/init.php
 */

namespace Eleave\Init;

use Eleave\Helper\Controller as Helper;
use Kotchasan\Language;

class Controller extends \Gcms\Controller
{
    /**
     * Register eleave permissions.
     *
     * @param array $permissions
     * @param mixed $params
     * @param object|null $login
     *
     * @return array
     */
    public static function initPermission($permissions, $params = null, $login = null)
    {
        $permissions[] = [
            'value' => 'can_manage_eleave',
            'text' => '{LNG_Can manage} {LNG_Leave}'
        ];

        return $permissions;
    }

    /**
     * Register eleave menus.
     *
     * @param array $menus
     * @param mixed $params
     * @param object|null $login
     *
     * @return array
     */
    public static function initMenus($menus, $params = null, $login = null)
    {
        if (!$login) {
            return $menus;
        }

        $leave_status = Language::get('LEAVE_STATUS');

        // เมนูสำหรับผูสมาชิก (เห็นทุกคน)
        $children = [];
        foreach ($leave_status as $status_id => $status_text) {
            $children[] = [
                'title' => $status_text,
                'url' => '/my-leaves?status='.$status_id,
                'icon' => 'icon-list'
            ];
        }
        $children[] = [
            'title' => 'Leave Statistics',
            'url' => '/leave-statistics',
            'icon' => 'icon-stats'
        ];
        $children[] = [
            'title' => '{LNG_Add} {LNG_Leave Request}',
            'url' => '/leave-request',
            'icon' => 'icon-edit'
        ];
        $menus = parent::insertMenuAfter($menus, [
            'eleave' => [
                'title' => 'My Leave Requests',
                'icon' => 'icon-calendar',
                'children' => $children
            ]
        ], 0);

        // เมนูสำหรับผู้อนุมัติ
        if (Helper::canAccessApprovalArea($login)) {
            $admin_children = [];
            foreach ($leave_status as $status_id => $status_text) {
                $admin_children[] = [
                    'title' => $status_text,
                    'url' => '/leave-approvals?status='.$status_id,
                    'icon' => 'icon-list'
                ];
            }
            $admin_children[] = [
                'title' => 'Leave Statistics',
                'url' => '/leave-user-statistics',
                'icon' => 'icon-stats'
            ];
            $menus = parent::insertMenuAfter($menus, [
                [
                    'title' => 'Leave Approvals',
                    'url' => '/leave-approvals',
                    'icon' => 'icon-verfied',
                    'children' => $admin_children
                ]
            ], 1);
        }

        if (!\Gcms\Api::hasPermission($login, ['can_manage_eleave', 'can_config'])) {
            return $menus;
        }

        $settings_menu = [
            [
                'title' => 'Leave',
                'icon' => 'icon-calendar',
                'children' => [
                    [
                        'title' => 'Settings',
                        'url' => '/leave-settings',
                        'icon' => 'icon-cog'
                    ],
                    [
                        'title' => 'Leave types',
                        'url' => '/leave-types',
                        'icon' => 'icon-list'
                    ]
                ]
            ]
        ];
        return parent::insertMenuChildren($menus, $settings_menu, 'settings', null, 1);
    }
}