<?php
/**
 * @filesource modules/eleave/controllers/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Index;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=eleave
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * แสดงรายการเอกสาร
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ปีงบประมาณปัจจุบัน
        $fiscalyear = \Eleave\Fiscalyear\Model::toDate();
        // ค่าที่ส่งมา
        $params = array(
            'from' => $request->request('from', $fiscalyear['from'])->date(),
            'to' => $request->request('to', $fiscalyear['to'])->date(),
            'leave_id' => $request->request('leave_id')->toInt(),
            'status' => $request->request('status')->toInt(),
            'leave_status' => Language::get('LEAVE_STATUS')
        );
        $params['status'] = isset($params['leave_status'][$params['status']]) ? $params['status'] : 1;
        // ข้อความ title bar
        $this->title = Language::get('Request for leave');
        $title = $params['leave_status'][$params['status']];
        $this->title .= ' '.$title;
        // เลือกเมนู
        $this->menu = 'eleave';
        // สมาชิก
        if ($login = Login::isMember()) {
            $params['member_id'] = $login['id'];
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-verfied">{LNG_My leave}</span></li>');
            $ul->appendChild('<li><span>{LNG_Request for leave}</span></li>');
            $ul->appendChild('<li><span>'.$title.'</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-list">'.$this->title.'</h2>'
            ));
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // ตาราง
            $div->appendChild(\Eleave\Index\View::create()->render($request, $params));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
