<?php
/**
 * @filesource modules/eleave/controllers/report.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Report;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=eleave-report
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * แสดงรายการขอลา (admin)
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::trans('{LNG_Report} {LNG_Request for leave}');
        // เลือกเมนู
        $this->menu = 'report';
        // สมาชิก
        $login = Login::isMember();
        // สามารถอนุมัติได้
        if (Login::checkPermission($login, 'can_approve_eleave')) {
            // ค่าที่ส่งมา
            $params = array(
                'from' => $request->request('from')->date(),
                'to' => $request->request('to')->date(),
                'leave_id' => $request->request('leave_id')->toInt(),
                'member_id' => $request->request('member_id')->toInt(),
                'status' => $request->request('status')->toInt(),
                'leave_status' => Language::get('LEAVE_STATUS')
            );
            $params['status'] = isset($params['leave_status'][$params['status']]) ? $params['status'] : 1;
            // ข้อความ title bar
            $this->title = Language::trans('{LNG_Report} {LNG_Request for leave}');
            $title = $params['leave_status'][$params['status']];
            $this->title .= ' '.$title;
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-verfied">{LNG_Report}</span></li>');
            $ul->appendChild('<li><span>{LNG_Request for leave}</span></li>');
            $ul->appendChild('<li><span>'.$title.'</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-report">'.$this->title.'</h2>'
            ));
            // menu
            $section->appendChild(\Index\Tabmenus\View::render($request, 'report', 'eleave'.$params['status']));
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // ตาราง
            $div->appendChild(\Eleave\Report\View::create()->render($request, $params, $login));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
