<?php
/**
 * @filesource modules/eleave/controllers/balance.php
 */

namespace Eleave\Balance;

use Eleave\Request\Model as RequestModel;
use Kotchasan\Http\Request;

/**
 * สิทธิ์การลาคงเหลือ พนักงาน × ประเภทการลา ในปี (api/eleave/balance)
 * ตอบข้อมูลล้วน: แถวคำนวณใน Model::getReport() · ตัวกรองบนหัวคอลัมน์ · options.summary · meta (ตัวกรองที่ใช้จริง)
 */
class Controller extends \Gcms\Table
{
    /**
     * @var array
     */
    protected $allowedSortColumns = ['member_name', 'department_name', 'leave_topic'];

    /**
     * สรุปของรายงานที่คำนวณใน toDataRows() (getOptions ส่งต่อ)
     *
     * @var array
     */
    protected $summary = [];

    /**
     * ปี (ค่าเริ่มต้น = ปีปัจจุบัน) · ประเภทการลา · พนักงาน · แผนก (สองตัวหลังเฉพาะผู้ที่ดูได้ทุกคน)
     *
     * @param Request $request
     * @param object  $login
     *
     * @return array
     */
    protected function getCustomParams(Request $request, $login): array
    {
        $year = $request->get('year')->toInt();
        $canViewAll = RequestModel::canApproveRequests($login);

        return [
            'year' => $year > 0 ? $year : (int) date('Y'),
            'leave_id' => $request->get('leave_id')->number(),
            'member_id' => $canViewAll ? $request->get('member_id')->number() : '',
            'department' => $canViewAll ? $request->get('department')->topic() : ''
        ];
    }

    /**
     * @param array  $params
     * @param object $login
     *
     * @return array
     */
    protected function toDataRows(array $params, $login)
    {
        $report = Model::getReport($login, $params);
        $this->summary = $report['summary'];

        return $report['rows'];
    }

    /**
     * ตัวเลือกของตัวกรอง — false = ผู้ดูใช้ตัวกรองนี้ไม่ได้ (หน้าเว็บซ่อน)
     *
     * @param array  $params
     * @param object $login
     *
     * @return array
     */
    protected function getFilters($params, $login = null)
    {
        $canViewAll = RequestModel::canApproveRequests($login);

        return [
            'year' => Model::getYearOptions((int) $params['year']),
            'leave_id' => RequestModel::getLeaveTypeOptions(false),
            'member_id' => $canViewAll ? Model::getMemberOptions() : false,
            'department' => $canViewAll ? RequestModel::getDepartmentOptions() : false
        ];
    }

    /**
     * จำนวนพนักงาน · ประเภทการลา · แถวที่ลาเกินสิทธิ์ (ทั้งรายงาน ไม่ใช่เฉพาะหน้าที่แสดง)
     *
     * @param array  $params
     * @param object $login
     *
     * @return array
     */
    protected function getOptions(array $params, $login)
    {
        return [
            'summary' => [
                'employee_count' => $this->summary['employee_count'] ?? 0,
                'leave_type_count' => $this->summary['leave_type_count'] ?? 0,
                'negative_count' => $this->summary['negative_count'] ?? 0
            ]
        ];
    }
}
