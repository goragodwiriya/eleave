<?php
/**
 * @filesource modules/eleave/controllers/approve.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Approve;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=eleave-approve
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * แบบฟอร์มขอลา (admin)
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // สมาชิก
        $login = Login::isMember();
        // ตรวจสอบรายการที่เลือก
        $index = \Eleave\Leave\Model::get($request->request('id')->toInt(), $login);
        // ข้อความ title bar
        $this->title = Language::trans('{LNG_Approve} {LNG_Request for leave}');
        // เลือกเมนู
        $this->menu = 'report';
        // สิทธิ์ผู้อนุมัติ
        if ($index && Login::checkPermission($login, 'can_approve_eleave')) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-verfied">{LNG_Request for leave}</span></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=eleave-report}">{LNG_Report}</a></li>');
            $ul->appendChild('<li><span>{LNG_Approve}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-write">'.$this->title.'</h2>'
            ));
            // menu
            $section->appendChild(\Index\Tabmenus\View::render($request, 'report', 'eleave'));
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // แสดงฟอร์ม
            $div->appendChild(\Eleave\Approve\View::create()->render($index, $login));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
