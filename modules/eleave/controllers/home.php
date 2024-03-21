<?php
/**
 * @filesource modules/eleave/controllers/home.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Home;

use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * Controller สำหรับการแสดงผลหน้า Home
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\KBase
{
    /**
     * ฟังก์ชั่นสร้าง card
     *
     * @param Request               $request
     * @param \Kotchasan\Collection $card
     * @param array                 $login
     */
    public static function addCard(Request $request, $card, $login)
    {
        if ($login) {
            $icons = ['icon-verfied', 'icon-valid', 'icon-invalid'];
            $leave_status = Language::get('LEAVE_STATUS');
            // ปีงบประมาณปัจจุบัน
            $fiscalyear = \Eleave\Fiscalyear\Model::toDate();
            // query ข้อมูล card
            $datas = \Eleave\Home\Model::get($login, $fiscalyear);
            // รายการลาของตัวเอง
            foreach ($leave_status as $status => $label) {
                $value = empty($datas[1][$status]) ? 0 : $datas[1][$status];
                $url = WEB_URL.'index.php?module=eleave&amp;from='.$fiscalyear['from'].'&amp;to='.$fiscalyear['to'].'&amp;status=';
                \Index\Home\Controller::renderCard($card, $icons[$status], '{LNG_My leave}', number_format($value), '{LNG_Request for leave} '.$label, $url.$status);
            }
            // รายการลาผู้อนุมัติรออนุมัติ
            if (isset($datas[0][0])) {
                $url = WEB_URL.'index.php?module=eleave-report&amp;from='.$fiscalyear['from'].'&amp;to='.$fiscalyear['to'].'&amp;status=0';
                if ($login['status'] != 1) {
                    $url .= '&amp;type='.$login['id'];
                }
                \Index\Home\Controller::renderCard($card, $icons[0], '{LNG_Can be approve}', number_format($datas[0][0]), '{LNG_Request for leave} '.$leave_status[0], $url);
            }
        }
    }
}
