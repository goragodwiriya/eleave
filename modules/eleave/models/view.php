<?php
/**
 * @filesource modules/eleave/models/view.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\View;

/**
 * โมเดลสำหรับอ่านเอกสาร
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านคำขอลาที่ $id
     * ไม่พบ คืนค่า null
     *
     * @param int $id
     *
     * @return object
     */
    public static function get($id)
    {
        return static::createQuery()
            ->from('leave_items I')
            ->join('leave A', 'INNER', array('A.id', 'I.leave_id'))
            ->join('user U', 'LEFT', array('U.id', 'I.member_id'))
            ->where(array('I.id', $id))
            ->cacheOn()
            ->toArray()
            ->first('I.*', 'A.topic leave_type', 'U.name');
    }

    /**
     * อ่านรายการไฟล์
     * และ ประวัติการดาวน์โหลดของคนที่ login
     *
     * @param int $id
     * @param array $login
     *
     * @return array
     */
    public static function files($id, $login)
    {
        return static::createQuery()
            ->select('F.topic', 'F.ext', 'D.downloads')
            ->from('eleave_files F')
            ->join('eleave_download D', 'LEFT', array(array('D.file_id', 'F.id'), array('D.member_id', $login['id'])))
            ->where(array('F.eleave_id', $id))
            ->groupBy('F.id')
            ->cacheOn()
            ->execute();
    }
}
