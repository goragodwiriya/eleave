<?php
/**
 * @filesource modules/eleave/controllers/setup.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Setup;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=eleave-setup
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * รายการประเภทการลา
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::trans('{LNG_List of} {LNG_Leave type}');
        // เลือกเมนู
        $this->menu = 'eleave';
        // สามารถจัดการโมดูลได้
        if (Login::checkPermission(Login::isMember(), 'can_manage_eleave')) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-verfied">{LNG_Settings}</span></li>');
            $ul->appendChild('<li><span>{LNG_E-Leave}</span></li>');
            $ul->appendChild('<li><span>{LNG_Leave type}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-list">'.$this->title.'</h2>'
            ));
            // menu
            $section->appendChild(\Index\Tabmenus\View::render($request, 'settings', 'eleave'));
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // ตาราง
            $div->appendChild(\Eleave\Setup\View::create()->render($request));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
