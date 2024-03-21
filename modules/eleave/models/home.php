<?php
/**
 * @filesource modules/eleave/models/home.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Home;

use Gcms\Login;

/**
 * โมเดลสำหรับอ่านข้อมูลแสดงในหน้า  Home
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * คืนค่าจำนวนการลาแยกแต่ละสถานะ
     * ถ้า login เป็นผู้อนุมัติคืนค่ารายการที่เกี่ยวข้อง
     * ถ้าไม่ใช่คืนค่าประวัติของตัวเอง
     *
     * @param array $login
     * @param array $fiscalyear
     *
     * @return object
     */
    public static function get($login, $fiscalyear)
    {
        $qs = [];
        if (Login::checkPermission($login, 'can_approve_eleave')) {
            $qs[] = static::createQuery()
                ->select('0 type', 'status', 'SQL(COUNT(id) AS count)')
                ->from('leave_items')
                ->where(array(
                    array('status', 0),
                    array('start_date', '>=', $fiscalyear['from']),
                    array('start_date', '<=', $fiscalyear['to'])
                ));
        }
        $qs[] = static::createQuery()
            ->select('1 type', 'status', 'SQL(COUNT(id) AS count)')
            ->from('leave_items')
            ->where(array(
                array('member_id', $login['id']),
                array('start_date', '>=', $fiscalyear['from']),
                array('start_date', '<=', $fiscalyear['to'])
            ))
            ->groupBy('status');
        $query = static::createQuery()
            ->select()
            ->unionAll($qs)
            ->cacheOn();
        $result = [];
        foreach ($query->execute() as $item) {
            $result[$item->type][$item->status] = $item->count;
        }
        return $result;
    }
}
