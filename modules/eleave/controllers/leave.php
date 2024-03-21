<?php
/**
 * @filesource modules/eleave/controllers/leave.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Leave;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=eleave-leave
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * แบบฟอร์มขอลา
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
        $title = '{LNG_'.(empty($index->id) ? 'Add' : 'Edit').'}';
        $this->title = Language::trans($title.' {LNG_Request for leave}');
        // เลือกเมนู
        $this->menu = 'eleave';
        // ใหม่, เจ้าของ
        if ($index && $login && ($index->id == 0 || $login['id'] == $index->member_id)) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-verfied">{LNG_E-Leave}</span></li>');
            $ul->appendChild('<li><span>{LNG_Request for leave}</span></li>');
            $ul->appendChild('<li><span>'.$title.'</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-write">'.$this->title.'</h2>'
            ));
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // แสดงฟอร์ม
            $div->appendChild(\Eleave\Leave\View::create()->render($index, $login));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
