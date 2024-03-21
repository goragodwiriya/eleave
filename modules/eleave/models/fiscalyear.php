<?php
/**
 * @filesource modules/eleave/models/fiscalyear.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Fiscalyear;

/**
 * ฟังก์ชั่นอ่านค่าปีงบประมาณ
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * คืนค่าปีงบประมาณปัจจุบัน
     *
     * @return int
     */
    public static function get()
    {
        if (self::$cfg->eleave_fiscal_year == 1) {
            return date('Y');
        } else {
            if (date('m') >= (int) self::$cfg->eleave_fiscal_year) {
                return date('Y') + 1;
            } else {
                return date('Y');
            }
        }
    }

    /**
     * คืนค่าวันที่เริ่มต้นและสิ้นสุดของปีงบประมาณ
     *
     * @param int $fiscal_year ปีงบประมาณที่ต้องการ ถ้าไม่ระบุใช้ปีงบประมาณปัจจุบัน
     *
     * @return array
     */
    public static function toDate($fiscal_year = 0)
    {
        if (empty($fiscal_year)) {
            $fiscal_year = self::get();
        }
        if (self::$cfg->eleave_fiscal_year == 1) {
            $form = $fiscal_year.sprintf('-%02d-01', self::$cfg->eleave_fiscal_year);
        } else {
            $form = ($fiscal_year - 1).sprintf('-%02d-01', self::$cfg->eleave_fiscal_year);
        }
        return array(
            'fiscal_year' => $fiscal_year,
            'from' => $form,
            'to' => date('Y-m-d', strtotime('+12 months -1 day '.$form))
        );
    }

    /**
     * คืนค่าวันที่เริ่มต้นและสิ้นสุดของปีงบประมาณ
     * จากวันที่
     *
     * @param string $date
     *
     * @return array
     */
    public static function dateToFiscalyear($date)
    {
        if (preg_match('/^([0-9]{4,4})/', $date, $match)) {
            $fiscal_year = (int) $match[1];
            $from = $fiscal_year.sprintf('-%02d-01', self::$cfg->eleave_fiscal_year);
            if (self::$cfg->eleave_fiscal_year > 1) {
                if ($date < $from) {
                    $fiscal_year--;
                    $from = $fiscal_year.sprintf('-%02d-01', self::$cfg->eleave_fiscal_year);
                }
            }
            return array(
                'fiscal_year' => $fiscal_year,
                'from' => $from,
                'to' => date('Y-m-d', strtotime('+12 months -1 day '.$from))
            );
        }
    }
}
