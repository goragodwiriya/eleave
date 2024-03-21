<?php
/**
 * @filesource modules/eleave/controllers/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Settings;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=eleave-settings
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ตั้งค่าโมดูล
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // ข้อความ title bar
        $this->title = Language::trans('{LNG_Module settings} {LNG_E-Leave}');
        // เลือกเมนู
        $this->menu = 'settings';
        // สามารถตั้งค่าระบบได้
        if (Login::checkPermission(Login::isMember(), 'can_config')) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-settings">{LNG_Settings}</span></li>');
            $ul->appendChild('<li><span>{LNG_E-Leave}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-verfied">'.$this->title.'</h2>'
            ));
            // menu
            $section->appendChild(\Index\Tabmenus\View::render($request, 'settings', 'eleave'));
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // แสดงฟอร์ม
            $div->appendChild(\Eleave\Settings\View::create()->render());
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
