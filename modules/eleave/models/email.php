<?php
/**
 * @filesource modules/eleave/models/email.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Email;

use Kotchasan\Language;

/**
 * ส่งอีเมลและ LINE ไปยังผู้ที่เกี่ยวข้อง
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ส่งอีเมลและ LINE แจ้งการทำรายการ
     *
     * @param array $order
     *
     * @return string
     */
    public static function send($order)
    {
        $lines = [];
        $emails = [];
        $name = '';
        $mailto = '';
        $line_uid = '';
        if (self::$cfg->demo_mode) {
            // โหมดตัวอย่าง ส่งหาผู้ทำรายการและแอดมินเท่านั้น
            $where = array(
                array('id', array($order['member_id'], 1))
            );
        } else {
            // ส่งหาผู้ทำรายการและผู้ที่เกี่ยวข้อง
            $where = array(
                // ผู้ทำรายการ
                array('id', $order['member_id']),
                // แอดมิน
                array('status', 1),
                // ผู้อนุมัตื
                array('permission', 'LIKE', '%,can_approve_eleave,%')
            );
        }
        // ตรวจสอบรายชื่อผู้รับ
        $query = static::createQuery()
            ->select('id', 'username', 'name', 'line_uid')
            ->from('user')
            ->where(array('active', 1))
            ->andWhere($where, 'OR')
            ->cacheOn();
        foreach ($query->execute() as $item) {
            if ($item->id == $order['member_id']) {
                // ผู้ทำรายการ
                $name = $item->name;
                $mailto = $item->username;
                $line_uid = $item->line_uid;
                $order['name'] = $item->name;
            } else {
                // เจ้าหน้าที่
                $emails[] = $item->name.'<'.$item->username.'>';
                if ($item->line_uid != '') {
                    $lines[] = $item->line_uid;
                }
            }
        }
        // ข้อความอีเมล
        $msg = Language::trans(\Eleave\View\View::create()->render($order, true));
        // ข้อความสำหรับผู้ทำรายการ
        $user_msg = str_replace('%MODULE%', 'leave', $msg);
        // ข้อความสำหรับผู้อนุมัติ
        $admin_msg = str_replace('%MODULE%', 'approve', $msg);
        $ret = [];
        if (!empty(self::$cfg->line_api_key)) {
            // LINE Notify
            $err = \Gcms\Line::send($admin_msg);
            if ($err != '') {
                $ret[] = $err;
            }
        }
        // LINE ส่วนตัว
        if (!empty($lines)) {
            $err = \Gcms\Line::sendTo($lines, $admin_msg);
            if ($err != '') {
                $ret[] = $err;
            }
        }
        if (!empty($line_uid)) {
            $err = \Gcms\Line::sendTo($line_uid, $user_msg);
            if ($err != '') {
                $ret[] = $err;
            }
        }
        if (self::$cfg->noreply_email != '') {
            // email subject
            $subject = '['.self::$cfg->web_title.'] '.Language::get('Request for leave').' '.Language::get('LEAVE_STATUS', '', $order['status']);
            // ส่งอีเมลไปยังผู้ทำรายการเสมอ
            $err = \Kotchasan\Email::send($name.'<'.$mailto.'>', self::$cfg->noreply_email, $subject, $user_msg);
            if ($err->error()) {
                // คืนค่า error
                $ret[] = strip_tags($err->getErrorMessage());
            }
            foreach ($emails as $item) {
                // ส่งอีเมล
                $err = \Kotchasan\Email::send($item, self::$cfg->noreply_email, $subject, $admin_msg);
                if ($err->error()) {
                    // คืนค่า error
                    $ret[] = strip_tags($err->getErrorMessage());
                }
            }
        }
        if (isset($err)) {
            // ส่งอีเมลสำเร็จ หรือ error การส่งเมล
            return empty($ret) ? Language::get('Your message was sent successfully') : implode("\n", array_unique($ret));
        } else {
            // ไม่มีอีเมลต้องส่ง
            return Language::get('Saved successfully');
        }
    }
}
