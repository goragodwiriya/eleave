<?php
/**
 * @filesource modules/eleave/models/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Settings;

use Gcms\Login;
use Kotchasan\Config;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=eleave-settings
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * รับค่าจากฟอร์ม (settings.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // session, token, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if (Login::notDemoMode($login) && Login::checkPermission($login, 'can_config')) {
                try {
                    // รับค่าจากการ POST
                    $typies = [];
                    foreach (explode(',', strtolower($request->post('eleave_file_typies')->filter('a-zA-Z0-9,'))) as $typ) {
                        if ($typ != '') {
                            $typies[$typ] = $typ;
                        }
                    }
                    // โหลด config
                    $config = Config::load(ROOT_PATH.'settings/config.php');
                    $config->eleave_fiscal_year = max(1, min(12, $request->post('eleave_fiscal_year')->toInt()));
                    $config->eleave_file_typies = array_keys($typies);
                    $config->eleave_upload_size = $request->post('eleave_upload_size')->toInt();
                    if (empty($config->eleave_file_typies)) {
                        // คืนค่า input ที่ error
                        $ret['ret_eleave_file_typies'] = 'this';
                    }
                    if (empty($ret)) {
                        // save config
                        if (Config::save($config, ROOT_PATH.'settings/config.php')) {
                            // log
                            \Index\Log\Model::add(0, 'eleave', 'Save', '{LNG_Module settings} {LNG_E-Leave}', $login['id']);
                            // คืนค่า
                            $ret['alert'] = Language::get('Saved successfully');
                            $ret['location'] = 'reload';
                            // เคลียร์
                            $request->removeToken();
                        } else {
                            // ไม่สามารถบันทึก config ได้
                            $ret['alert'] = Language::replace('File %s cannot be created or is read-only.', 'settings/config.php');
                        }
                    }
                } catch (\Kotchasan\InputItemException $e) {
                    $ret['alert'] = $e->getMessage();
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
