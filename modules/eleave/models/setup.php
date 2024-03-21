<?php
/**
 * @filesource modules/eleave/models/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Setup;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=eleave-setup
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Query ข้อมูลสำหรับส่งให้กับ DataTable
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable()
    {
        return static::createQuery()
            ->select('id', 'topic', 'num_days', 'published')
            ->from('leave');
    }

    /**
     * รับค่าจาก action (setup.php)
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        $ret = [];
        // session, referer, สามารถจัดการโมดูลได้, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            if (Login::notDemoMode($login) && Login::checkPermission($login, 'can_manage_eleave')) {
                // รับค่าจากการ POST
                $action = $request->post('action')->toString();
                // id ที่ส่งมา
                if (preg_match_all('/,?([0-9]+),?/', $request->post('id')->toString(), $match)) {
                    // ตาราง
                    $table = $this->getTableName('leave');
                    if ($action === 'delete') {
                        // ลบ
                        $this->db()->delete($table, array('id', $match[1]), 0);
                        // log
                        \Index\Log\Model::add(0, 'eleave', 'Delete', '{LNG_Delete} {LNG_Leave type} ID : '.implode(', ', $match[1]), $login['id']);
                        // reload
                        $ret['location'] = 'reload';
                    } elseif ($action === 'published') {
                        // สถานะการเผยแพร่
                        $search = $this->db()->first($table, (int) $match[1][0]);
                        if ($search) {
                            $published = $search->published == 1 ? 0 : 1;
                            $this->db()->update($table, $search->id, array('published' => $published));
                            // คืนค่า
                            $ret['elem'] = 'published_'.$search->id;
                            $ret['title'] = Language::get('PUBLISHEDS', '', $published);
                            $ret['class'] = 'icon-published'.$published;
                            // log
                            \Index\Log\Model::add(0, 'eleave', 'Save', $ret['title'].' ID : '.$match[1][0], $login['id']);
                        }
                    }
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
