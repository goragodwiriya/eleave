<?php
/**
 * @filesource modules/eleave/models/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Index;

use Gcms\Login;
use Kotchasan\File;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=eleave
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
     * @param array $params
     *
     * @return \Kotchasan\Database\QueryBuilder
     */
    public static function toDataTable($params)
    {
        $where = array(
            array('F.member_id', $params['member_id']),
            array('F.status', $params['status'])
        );
        if (!empty($params['leave_id'])) {
            $where[] = array('F.leave_id', $params['leave_id']);
        }
        if (!empty($params['from'])) {
            $where[] = array('F.start_date', '>=', $params['from']);
        }
        if (!empty($params['to'])) {
            $where[] = array('F.start_date', '<=', $params['to']);
        }
        return static::createQuery()
            ->select('F.id', 'F.create_date', 'F.leave_id', 'F.start_date', 'F.days', 'F.start_period', 'F.end_date', 'F.end_period', 'F.status', 'F.reason')
            ->from('leave_items F')
            ->where($where);
    }

    /**
     * รับค่าจาก action (index.php)
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        $ret = [];
        // session, referer, member
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            // ค่าที่ส่งมา
            $action = $request->post('action')->toString();
            // id ที่ส่งมา
            if (preg_match_all('/,?([0-9]+),?/', $request->post('id')->toString(), $match)) {
                if ($action == 'detail') {
                    // แสดงรายละเอียดคำขอลา
                    $index = \Eleave\View\Model::get((int) $match[1][0]);
                    if ($index) {
                        $ret['modal'] = Language::trans(\Eleave\View\View::create()->render($index));
                    }
                } elseif ($action == 'delete') {
                    // รายการของตัวเองที่ยังไม่ได้อนุมัติ
                    $query = $this->db()->createQuery()
                        ->select('id')
                        ->from('leave_items')
                        ->where(array(
                            array('id', $match[1]),
                            array('member_id', $login['id']),
                            array('status', '!=', 1)
                        ));
                    $ids = [];
                    foreach ($query->execute() as $item) {
                        $ids[] = $item->id;
                        // ลบไฟล์แนบ
                        File::removeDirectory(ROOT_PATH.DATA_FOLDER.'eleave/'.$item->id.'/');
                    }
                    if (!empty($ids)) {
                        // ลบ database
                        $this->db()->delete($this->getTableName('leave_items'), array('id', $ids), 0);
                        // log
                        \Index\Log\Model::add(0, 'eleave', 'Delete', '{LNG_Delete} {LNG_My leave} ID : '.implode(', ', $ids), $login['id']);
                    }
                    // reload
                    $ret['location'] = 'reload';
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
