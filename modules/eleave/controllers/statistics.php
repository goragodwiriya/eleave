<?php
/**
 * @filesource modules/eleave/controllers/statistics.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Statistics;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=eleave-statistics
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * สรุปการลารายบุคคล ในปีปัจจุบัน
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::get('Statistics for leave');
        // เลือกเมนูลา
        $this->menu = 'eleave';
        // ค่าที่ส่งมา
        $params = array(
            'year_offset' => (int) Language::get('YEAR_OFFSET'),
            'fiscal_year' => \Eleave\Fiscalyear\Model::get()
        );
        $start_date = $request->request('start_date')->date();
        if ($start_date) {
            $fiscalyear = \Eleave\Fiscalyear\Model::dateToFiscalyear($start_date);
        } else {
            $year = $request->request('year', $params['fiscal_year'])->toInt();
            $fiscalyear = \Eleave\Fiscalyear\Model::toDate($year);
        }
        $params['year'] = $fiscalyear['fiscal_year'];
        $params['from'] = $fiscalyear['from'];
        $params['to'] = $fiscalyear['to'];
        // สมาชิก
        if ($login = Login::isMember()) {
            // สามารถอนุมัติได้
            $can_approve_eleave = $request->request('id')->exists() && Login::checkPermission($login, 'can_approve_eleave');
            if ($can_approve_eleave) {
                // ผู้อนุมัติ อ่านข้อมูลตามที่เลือก
                $params['member_id'] = $request->request('id', $login['id'])->toInt();
                // มาจากรายงาน
                $this->menu = 'report';
            } else {
                // อ่านข้อมูลของตัวเอง
                $params['member_id'] = $login['id'];
            }
            if (isset($params['member_id'])) {
                // แสดงผล
                $section = Html::create('section');
                // breadcrumbs
                $breadcrumbs = $section->add('nav', array(
                    'class' => 'breadcrumbs'
                ));
                $ul = $breadcrumbs->add('ul');
                $ul->appendChild('<li><span class="icon-verfied">{LNG_E-Leave}</span></li>');
                // อ่านข้อมูล user
                $user = \Index\Editprofile\Model::get($params['member_id']);
                // สมาชิก
                if ($user) {
                    // ข้อความ title bar
                    $this->title .= ' '.$user['name'];
                    $ul->appendChild('<li><span>'.$user['name'].'</span></li>');
                }
                $ul->appendChild('<li><span>{LNG_Statistics for leave}</span></li>');
                $section->add('header', array(
                    'innerHTML' => '<h2 class="icon-stats">'.$this->title.'</h2>'
                ));
                if ($this->menu == 'report') {
                    // ผู้อนุมัติ แสดง menu
                    $section->appendChild(\Index\Tabmenus\View::render($request, 'report', 'eleave'));
                }
                $div = $section->add('div', array(
                    'class' => 'content_bg'
                ));
                // ตาราง
                $div->appendChild(\Eleave\Statistics\View::create()->render($request, $params));
                // คืนค่า HTML
                return $section->render();
            }
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
