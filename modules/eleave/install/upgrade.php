<?php
/**
 * modules/eleave/install/upgrade.php — พาฐานเดิมมาถึงสคีมาของโมดูล eleave
 *
 * install/upgrade_core.php เรียกไฟล์นี้ให้เอง ตัวแปรที่ใช้ได้คือชุดเดียวกับที่
 * upgrade_core ใช้ : $db, $db_config, $prefix, $content, $config
 *
 * ⚠️ ก่อนมีไฟล์นี้ **ไม่มีอะไรแตะตารางของโมดูลนี้เลย** install/upgrade2.php
 * ของโปรเจ็คเป็นแม่แบบเปล่า ๆ ที่เรียกแต่ upgrade_core ส่วนสองตารางของโมดูล
 * ถูกประกาศไว้ใน install/database.sql เท่านั้น ไซต์ที่ติดตั้งใหม่จึงได้สคีมาถูก
 * แต่ไซต์ที่กดปรับรุ่นได้สคีมาเก่าค้างไว้
 *
 * กฎเดียวกับ upgrade_core : ทุกเงื่อนไขถามว่า "ต้องแก้ไหม" ไม่ใช่ "ตอนนี้เป็นอะไร"
 */
if (!defined('ROOT_PATH')) {
    exit;
}

$_t_leave = $prefix.'_leave';
$_t_items = $prefix.'_leave_items';

foreach ([$_t_leave, $_t_items] as $_t) {
    // นิยามตารางอยู่ที่ modules/eleave/install/database.sql ที่เดียว
    if (ensureTable($db, $prefix, $_t)) {
        $content[] = '<li class="correct">eleave: สร้างตาราง '.$_t.'</li>';
    }
    // ⚠️ ต้องแปลงก่อนปรับคอลัมน์เสมอ — CONVERT TO CHARACTER SET เลื่อนชนิด TEXT
    // เป็น MEDIUMTEXT ฐานจริงของ eleave มี detail/communication เป็น mediumtext
    // อยู่แล้วเพราะเคยถูกแปลง charset มาก่อนโดยไม่ได้บังคับชนิดกลับ
    if (convertToInnoDB($db, $_t)) {
        $content[] = '<li class="correct">'.$_t.': แปลงเป็น InnoDB</li>';
    }
    if (convertToUtf8mb4($db, $_t)) {
        $content[] = '<li class="correct">'.$_t.': แปลงเป็น utf8mb4</li>';
    }
}

// =============================================================================
// leave — ประเภทการลา
// =============================================================================
foreach ([
    'topic' => ['varchar(150)', false, null, 'id'],
    'detail' => ['text', false, null, 'topic'],
    'num_days' => ['tinyint(4)', false, null, 'detail'],
    'is_active' => ['tinyint(1)', false, 1, 'num_days']
] as $_col => $_def) {
    if (ensureColumn($db, $_t_leave, $_col, $_def[0], $_def[1], $_def[2], '', $_def[3])) {
        $content[] = '<li class="correct">leave: ปรับคอลัมน์ '.$_col.'</li>';
    }
}

// =============================================================================
// leave_items — ใบลา
//
// ⚠️ approve / closed ต้องเป็น NULL ได้ ของเดิมประกาศ NOT NULL เฉย ๆ
// ค่า null ในระบบนี้แปลว่า "ยังไม่พิจารณา" ซึ่งต่างจาก 0 ที่แปลว่า "ไม่อนุมัติ"
// ถ้าบังคับเป็น NOT NULL ใบลาที่ยังรอพิจารณาจะกลายเป็นไม่อนุมัติทั้งหมด
// =============================================================================
foreach ([
    'member_id' => ['int(11)', false, null, 'id'],
    'leave_id' => ['int(11)', false, null, 'member_id'],
    'status' => ['tinyint(1)', false, null, 'leave_id'],
    'approve' => ['tinyint(1)', true, null, 'status'],
    'closed' => ['tinyint(1)', true, null, 'approve'],
    'department' => ['varchar(10)', true, null, 'closed'],
    'detail' => ['text', false, null, 'department'],
    'communication' => ['text', false, null, 'detail'],
    'start_date' => ['date', false, null, 'communication'],
    'start_period' => ['tinyint(1)', false, null, 'start_date'],
    'end_date' => ['date', false, null, 'start_period'],
    'end_period' => ['tinyint(1)', false, null, 'end_date'],
    'created_at' => ['datetime', false, null, 'end_period'],
    'days' => ['float', false, '0', 'created_at'],
    'reason' => ['varchar(255)', true, null, 'days']
] as $_col => $_def) {
    if (ensureColumn($db, $_t_items, $_col, $_def[0], $_def[1], $_def[2], '', $_def[3])) {
        $content[] = '<li class="correct">leave_items: ปรับคอลัมน์ '.$_col.'</li>';
    }
}

// =============================================================================
// ดัชนีที่ query ใช้จริง — ต้องทำหลังปรับคอลัมน์เสร็จ
// =============================================================================
if (ensureIndexes($db, $_t_items, [
    'member_id' => '`member_id`',
    'closed' => '`closed`',
    'status' => '`status`',
    'start_date' => '`start_date`'
])) {
    $content[] = '<li class="correct">leave_items: ปรับดัชนี</li>';
}

$content[] = '<li class="correct">eleave อัปเกรดสำเร็จ</li>';
