<?php
/**
 * @filesource modules/eleave/views/view.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\View;

use Kotchasan\Date;
use Kotchasan\Language;

/**
 * แสดงรายละเอียดของเอกสาร (modal)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงฟอร์ม Modal สำหรับแสดงรายละเอียด
     * และส่งไปในอีเมล
     *
     * @param object $index
     * @param bool $email true บอกว่าเป็นอีเมล
     *
     * @return string
     */
    public function render($index, $email = false)
    {
        $content = [];
        $content[] = '<article class=modal_detail><header><h3 class=icon-file>{LNG_Details of} {LNG_Request for leave}</h3></header>';
        $content[] = '<table class="fullwidth">';
        $content[] = '<tr><td class="item"><span class="icon-customer">{LNG_Name}</span></td><td class="item"> : </td><td class="item">'.$index['name'].'</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-verfied">{LNG_Leave type}</span></td><td class="item"> : </td><td class="item">'.$index['leave_type'].'</td></tr>';
        $category = \Eleave\Category\Model::init();
        foreach ($category->items() as $k => $label) {
            $content[] = '<tr><td class="item"><span class="icon-group">'.$label.'</span></td><td class="item"> : </td><td class="item">'.$category->get($k, $index[$k]).'</td></tr>';
        }
        $content[] = '<tr><td class="item"><span class="icon-file">{LNG_Detail}/{LNG_Reasons for leave}</span></td><td class="item"> : </td><td class="item">'.nl2br($index['detail']).'</td></tr>';
        $row = '<tr><td class="item"><span class="icon-calendar">{LNG_Date of leave}</span></td><td class="item"> : </td><td class="item">';
        $leave_period = Language::get('LEAVE_PERIOD');
        if ($index['start_date'] == $index['end_date']) {
            $row .= Date::format($index['start_date'], 'd M Y').' '.$leave_period[$index['start_period']];
        } else {
            $row .= Date::format($index['start_date'], 'd M Y').' '.$leave_period[$index['start_period']].' - '.Date::format($index['end_date'], 'd M Y').' '.$leave_period[$index['end_period']];
        }
        $content[] = $row.'</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-event">{LNG_Number of leave days}</span></td><td class="item"> : </td><td class="item">'.$index['days'].' {LNG_days}</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-file">{LNG_Communication}</span></td><td class="item"> : </td><td class="item">'.nl2br($index['communication']).'</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-star0">{LNG_Status}</span></td><td class="item"> : </td><td class="item">'.self::showStatus(Language::get('LEAVE_STATUS'), $index['status'], !$email).'</td></tr>';
        if (!empty($index['reason'])) {
            $content[] = '<tr><td class="item"><span class="icon-comments">{LNG_Reason}</span></td><td class="item"> : </td><td class="item">'.$index['reason'].'</td></tr>';
        }
        if ($email) {
            $url = WEB_URL.'index.php?module=eleave-%MODULE%&amp;id='.$index['id'];
            $content[] = '<tr><td class="item">Url</td><td class="item"> : </td><td class="item"><a href="'.$url.'">'.$url.'</a></td></tr>';
        } else {
            $content[] = '<tr><td class="item"><span class="icon-download">{LNG_Attached file}</span></td><td class="item"> : </td><td class="item">'.\Download\Index\Controller::init($index['id'], 'eleave', self::$cfg->eleave_file_typies).'</td></tr>';
        }
        $content[] = '</table></article>';
        // คืนค่า HTML
        return implode("\n", $content);
    }
}
