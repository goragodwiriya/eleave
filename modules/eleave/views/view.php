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

use Kotchasan\Language;

/**
 * รายละเอียดใบลา: ข้อมูลของหน้าต่าง (modalData · แม่แบบ eleave/leave-view.html) และเนื้ออีเมล (render)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Eleave\Helper\Controller
{
    /**
     * เนื้ออีเมลรายละเอียดใบลา — ค่าที่ผู้ใช้กรอกถูก escape (รวม {} ที่ Language::trans() จะอ่านเป็นคีย์ภาษา)
     *
     * @param array $index
     * @param bool $email
     *
     * @return string
     */
    public static function render($index, $email = false)
    {
        $esc = function ($text) {
            return strtr(htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'), ['{' => '&#123;', '}' => '&#125;']);
        };
        $content = [];
        if ($email) {
            $content[] = '<header>';
            $content[] = '<h4>{LNG_Details of} {LNG_Leave Request}</h4>';
            $content[] = '</header>';
        }
        $content[] = '<table class="fullwidth">';
        $content[] = '<tr><td class="item"><span class="icon-customer">{LNG_Name}</span></td><td class="item"> : </td><td class="item">'.$esc($index['name']).'</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-verfied">{LNG_Leave type}</span></td><td class="item"> : </td><td class="item">'.$esc($index['leave_type']).'</td></tr>';
        $category = \Gcms\Category::init();
        $content[] = '<tr><td class="item"><span class="icon-group">{LNG_Department}</span></td><td class="item"> : </td><td class="item">'.$esc($category->get('department', $index['department'])).'</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-file">{LNG_Detail}/{LNG_Reasons for leave}</span></td><td class="item"> : </td><td class="item">'.nl2br($esc($index['detail'])).'</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-calendar">{LNG_Leave dates}</span></td><td class="item"> : </td><td class="item">'.$esc(self::formatLeaveDate($index)).'</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-event">{LNG_Number of leave days}</span></td><td class="item"> : </td><td class="item">'.$esc($index['days']).' {LNG_days}</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-file">{LNG_Contact During Leave}</span></td><td class="item"> : </td><td class="item">'.nl2br($esc($index['communication'])).'</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-star0">{LNG_Status}</span></td><td class="item"> : </td><td class="item">'.self::showStatus($index['status'], !$email).'</td></tr>';
        if (!empty($index['reason'])) {
            $content[] = '<tr><td class="item"><span class="icon-comments">{LNG_Reason}</span></td><td class="item"> : </td><td class="item">'.nl2br($esc($index['reason'])).'</td></tr>';
        }
        if ($email) {
            $content[] = '<tr><td class="item">Url</td><td class="item"> : </td><td class="item"><a href="'.WEB_URL.'">'.WEB_URL.'</a></td></tr>';
        }
        $content[] = '</table>';

        return implode("\n", $content);
    }

    /**
     * ข้อมูลของหน้าต่างรายละเอียดใบลา (templates/eleave/leave-view.html) — ข้อมูลล้วน ไม่มี HTML
     * วันที่ลาเป็นค่าดิบ (หน้าเว็บจัดรูปตามภาษา) · ช่วงของวัน (เช้า/บ่าย) เป็นป้ายจาก LEAVE_PERIOD
     *
     * @param array $index แถวของใบลา + name · leave_type
     *
     * @return array
     */
    public static function modalData(array $index): array
    {
        $periods = Language::get('LEAVE_PERIOD');
        $periods = is_array($periods) ? $periods : [];
        $department = (string) ($index['department'] ?? '');

        return [
            'id' => (int) $index['id'],
            'name' => (string) ($index['name'] ?? ''),
            'leave_type' => (string) ($index['leave_type'] ?? ''),
            'department_name' => $department === '' ? '' : (string) \Gcms\Category::init()->get('department', $department),
            'detail' => (string) ($index['detail'] ?? ''),
            'start_date' => (string) ($index['start_date'] ?? ''),
            'end_date' => (string) ($index['end_date'] ?? ''),
            'same_day' => ($index['start_date'] ?? '') === ($index['end_date'] ?? ''),
            'start_period_text' => (string) ($periods[(int) ($index['start_period'] ?? -1)] ?? ''),
            'end_period_text' => (string) ($periods[(int) ($index['end_period'] ?? -1)] ?? ''),
            'days' => (float) ($index['days'] ?? 0),
            'communication' => (string) ($index['communication'] ?? ''),
            'status' => (int) $index['status'],
            'status_text' => self::showStatus($index['status'], false),
            'reason' => (string) ($index['reason'] ?? ''),
            'attachments' => array_map(function ($item) {
                return ['url' => $item['url'], 'name' => $item['name']];
            }, \Download\Index\Controller::getAttachments((int) $index['id'], 'eleave', self::$cfg->eleave_file_types))
        ];
    }

    /**
     * หน้าต่างรายละเอียดใบลา — แม่แบบ eleave/leave-view.html อ่านข้อมูลจาก data ของคำตอบ (modalData())
     *
     * @return array
     */
    public static function buildModalAction(): array
    {
        return [
            'type' => 'modal',
            'action' => 'show',
            'template' => 'eleave/leave-view.html',
            'dataKey' => 'data',
            'title' => '{LNG_Details of} {LNG_Leave Request}',
            'titleClass' => 'icon-calendar'
        ];
    }
}
