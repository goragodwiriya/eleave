<?php
/**
 * @filesource modules/eleave/views/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Index;

use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=eleave
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * @var object
     */
    private $leavetype;
    /**
     * @var mixed
     */
    private $leave_period;
    /**
     * @var int
     */
    private $days = 0;

    /**
     * แสดงรายการลา
     *
     * @param Request $request
     * @param array $params
     *
     * @return string
     */
    public function render(Request $request, $params)
    {
        // Leave type
        $this->leavetype = \Eleave\Leavetype\Model::init();
        $this->leave_period = Language::get('LEAVE_PERIOD');
        // URL สำหรับส่งให้ตาราง
        $uri = $request->createUriWithGlobals(WEB_URL.'index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* Model */
            'model' => \Eleave\Index\Model::toDataTable($params),
            /* รายการต่อหน้า */
            'perPage' => $request->cookie('eleaveIndex_perPage', 30)->toInt(),
            /* เรียงลำดับ */
            'sort' => $request->cookie('eleaveIndex_sort', 'start_date DESC')->toString(),
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* ฟังก์ชั่นแสดงผล Footer */
            'onCreateFooter' => array($this, 'onCreateFooter'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id', 'start_period', 'end_date', 'end_period', 'status'),
            /* ตัวเลือกการแสดงผลที่ส่วนหัว */
            'filters' => array(
                array(
                    'name' => 'from',
                    'type' => 'date',
                    'text' => '{LNG_from}',
                    'value' => $params['from']
                ),
                array(
                    'name' => 'to',
                    'type' => 'date',
                    'text' => '{LNG_to}',
                    'value' => $params['to']
                ),
                array(
                    'name' => 'leave_id',
                    'options' => array(0 => '{LNG_all items}') + $this->leavetype->toSelect(),
                    'text' => '{LNG_Leave type}',
                    'value' => $params['leave_id']
                )
            ),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/eleave/model/index/action',
            'actionCallback' => 'dataTableActionCallback',
            'actions' => array(
                array(
                    'id' => 'action',
                    'class' => 'ok',
                    'text' => '{LNG_With selected}',
                    'options' => array(
                        'delete' => '{LNG_Delete}'
                    )
                )
            ),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'create_date' => array(
                    'text' => '{LNG_Transaction date}',
                    'sort' => 'create_date'
                ),
                'leave_id' => array(
                    'text' => '{LNG_Leave type}',
                    'sort' => 'leave_id'
                ),
                'start_date' => array(
                    'text' => '{LNG_Date of leave}',
                    'sort' => 'start_date'
                ),
                'days' => array(
                    'text' => '{LNG_days}',
                    'class' => 'center'
                ),
                'reason' => array(
                    'text' => '{LNG_Reason}'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'days' => array(
                    'class' => 'center'
                )
            ),
            /* ฟังก์ชั่นตรวจสอบการแสดงผลปุ่มในแถว */
            'onCreateButton' => array($this, 'onCreateButton'),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                'detail' => array(
                    'class' => 'icon-info button orange',
                    'id' => ':id',
                    'text' => '{LNG_Detail}'
                ),
                'edit' => array(
                    'class' => 'icon-edit button green',
                    'href' => $uri->createBackUri(array('module' => 'eleave-leave', 'id' => ':id')),
                    'text' => '{LNG_Edit}'
                )
            ),
            /* ปุ่มเพิ่ม */
            'addNew' => array(
                'class' => 'float_button icon-new',
                'href' => 'index.php?module=eleave-leave',
                'title' => '{LNG_Add} {LNG_Request for leave}'
            )
        ));
        // save cookie
        setcookie('eleaveIndex_perPage', $table->perPage, time() + 2592000, '/', HOST, HTTPS, true);
        setcookie('eleaveIndex_sort', $table->sort, time() + 2592000, '/', HOST, HTTPS, true);
        // คืนค่า HTML
        return $table->render();
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว
     *
     * @param array $item
     *
     * @return array
     */
    public function onRow($item, $o, $prop)
    {
        $this->days += $item['days'];
        $item['create_date'] = Date::format($item['create_date'], 'd M Y');
        $item['leave_id'] = $this->leavetype->get($item['leave_id']);
        if ($item['start_date'] == $item['end_date']) {
            $item['start_date'] = Date::format($item['start_date'], 'd M Y').' '.$this->leave_period[$item['start_period']];
        } else {
            $item['start_date'] = Date::format($item['start_date'], 'd M Y').' '.$this->leave_period[$item['start_period']].' - '.Date::format($item['end_date'], 'd M Y').' '.$this->leave_period[$item['end_period']];
        }
        return $item;
    }

    /**
     * ฟังก์ชั่นสร้างแถวของ footer
     *
     * @return string
     */
    public function onCreateFooter()
    {
        return '<tr><td></td><td class=check-column><a class="checkall icon-uncheck" title="{LNG_Select all}"></a></td><td class=right colspan=2{LNG_Total}</td><td class=center>'.$this->days.'</td><td></td></tr>';
    }

    /**
     * ฟังกชั่นตรวจสอบว่าสามารถสร้างปุ่มได้หรือไม่
     *
     * @param array $item
     *
     * @return array
     */
    public function onCreateButton($btn, $attributes, $item)
    {
        if ($btn == 'edit') {
            return $item['status'] == 0 ? $attributes : false;
        } else {
            return $attributes;
        }
    }
}
