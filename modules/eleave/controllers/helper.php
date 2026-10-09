<?php
/**
 * @filesource modules/eleave/controllers/helper.php
 */

namespace Eleave\Helper;

use Gcms\Api as ApiController;
use Kotchasan\Date;
use Kotchasan\Language;

class Controller extends \Kotchasan\KBase
{
    public const STATUS_PENDING_REVIEW = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_REJECTED = 2;
    public const STATUS_CANCELLED_BY_REQUESTER = 3;
    public const STATUS_RETURNED_FOR_EDIT = 5;

    /**
     * Get active approval steps keyed by step number.
     * `approve_level` is the source of truth for how many steps are enabled.
     *
     * @return array<int, array{status:int, department:string}>
     */
    public static function getApprovalSteps(): array
    {
        $levelCount = max(0, (int) (self::$cfg->eleave_approve_level ?? 0));
        if ($levelCount === 0) {
            return [];
        }

        $statuses = (array) (self::$cfg->eleave_approve_status ?? []);
        $departments = (array) (self::$cfg->eleave_approve_department ?? []);
        $steps = [];
        for ($level = 1; $level <= $levelCount; $level++) {
            if (!array_key_exists($level, $statuses)) {
                break;
            }
            $steps[$level] = [
                'status' => (int) $statuses[$level],
                'department' => isset($departments[$level]) ? (string) $departments[$level] : ''
            ];
        }

        return $steps;
    }

    /**
     * Get the number of active approval steps.
     *
     * @return int
     */
    public static function getApprovalLevelCount(): int
    {
        return count(self::getApprovalSteps());
    }

    /**
     * Get configuration for a single approval step.
     *
     * @param int $step
     *
     * @return array{status:int, department:string}|null
     */
    public static function getApprovalStepConfig(int $step): ?array
    {
        $steps = self::getApprovalSteps();

        return $steps[$step] ?? null;
    }

    /**
     * Get the next configured approval step after the current one.
     *
     * @param int $currentStep
     *
     * @return int
     */
    public static function getNextApprovalStep(int $currentStep): int
    {
        $steps = array_keys(self::getApprovalSteps());
        $index = array_search($currentStep, $steps, true);

        if ($index === false || !isset($steps[$index + 1])) {
            return 0;
        }

        return (int) $steps[$index + 1];
    }

    /**
     * Determine the approver level available to this login.
     * -1 means admin approval access, 0 means no approval access.
     *
     * @param object|null $login
     *
     * @return int
     */
    public static function getApproveLevel($login): int
    {
        if (!$login) {
            return 0;
        }
        $steps = self::getApprovalSteps();
        if (empty($steps)) {
            return 0;
        }
        if (ApiController::isAdmin($login)) {
            return -1;
        }

        $loginDepartment = isset($login->metas['department'][0]) ? trim((string) $login->metas['department'][0]) : '';

        foreach ($steps as $level => $step) {
            if ((int) $step['status'] !== (int) $login->status) {
                continue;
            }
            $department = $step['department'];
            if ($department === '' || $department === $loginDepartment) {
                return (int) $level;
            }
        }

        return 0;
    }

    /**
     * Check if user can approve the current step of a request.
     *
     * @param object|null $login
     * @param object|array $request
     *
     * @return bool
     */
    public static function canApproveStep($login, $request): bool
    {
        if (!$login) {
            return false;
        }
        $steps = self::getApprovalSteps();
        if (empty($steps)) {
            return false;
        }

        if (ApiController::isAdmin($login)) {
            return true;
        }

        $approve = is_array($request) ? ($request['approve'] ?? 1) : ($request->approve ?? 1);
        $department = is_array($request) ? ($request['department'] ?? '') : ($request->department ?? '');
        $step = $steps[$approve] ?? null;
        $loginDepartment = isset($login->metas['department'][0]) ? trim((string) $login->metas['department'][0]) : '';
        if ($step !== null && (int) $login->status === (int) $step['status']) {
            if ($step['department'] === '') {
                return (string) $department === $loginDepartment;
            }

            return $step['department'] === $loginDepartment;
        }

        return false;
    }

    /**
     * Permission helper for approval workflow.
     *
     * @param object|null $login
     *
     * @return bool
     */
    public static function canApproveRequests($login): bool
    {
        return self::getApproveLevel($login) !== 0;
    }

    /**
     * Check whether the login should be allowed into approval pages.
     *
     * Approval-area access should follow the configured step rules: the login
     * must match a configured approver status and, when configured, department.
     *
     * @param object|null $login
     *
     * @return bool
     */
    public static function canAccessApprovalArea($login): bool
    {
        if (!$login) {
            return false;
        }

        $steps = self::getApprovalSteps();
        if (empty($steps)) {
            return false;
        }

        if (ApiController::isAdmin($login)) {
            return true;
        }

        return self::canApproveRequests($login);
    }

    /**
     * Get leave type rows.
     *
     * @param bool $is_activeOnly
     * @param int|null $includeId
     *
     * @return array
     */
    public static function getLeaveTypes(bool $is_activeOnly = true, ?int $includeId = null): array
    {
        $query = \Kotchasan\Model::createQuery()
            ->select('id', 'topic', 'detail', 'num_days', 'is_active')
            ->from('leave');

        if ($is_activeOnly) {
            $query->where(['is_active', 1]);
        }

        $rows = $query->orderBy('topic')
            ->cacheOn()
            ->fetchAll();
        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id' => (int) $row->id,
                'topic' => $row->topic,
                'detail' => $row->detail,
                'num_days' => (int) $row->num_days,
                'is_active' => !empty($row->is_active)
            ];
        }

        if ($includeId !== null) {
            $exists = false;
            foreach ($result as $item) {
                if ((int) $item['id'] === $includeId) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $extra = self::getLeaveTypeById($includeId);
                if ($extra) {
                    $result[] = [
                        'id' => (int) $extra->id,
                        'topic' => $extra->topic,
                        'detail' => $extra->detail,
                        'num_days' => (int) $extra->num_days,
                        'is_active' => !empty($extra->is_active)
                    ];
                }
            }
        }

        usort($result, function ($first, $second) {
            return strcmp($first['topic'], $second['topic']);
        });

        return $result;
    }

    /**
     * Get leave type by ID.
     *
     * @param int $leaveId
     *
     * @return object|null
     */
    public static function getLeaveTypeById(int $leaveId)
    {
        if ($leaveId <= 0) {
            return null;
        }

        return \Kotchasan\Model::createQuery()
            ->select('id', 'topic', 'detail', 'num_days', 'is_active')
            ->from('leave')
            ->where(['id', $leaveId])
            ->first();
    }

    /**
     * Get leave type options.
     *
     * @param bool $is_activeOnly
     * @param int|null $includeId
     *
     * @return array
     */
    public static function getLeaveTypeOptions(bool $is_activeOnly = true, ?int $includeId = null): array
    {
        $options = [];
        foreach (self::getLeaveTypes($is_activeOnly, $includeId) as $item) {
            $options[] = [
                'value' => (string) $item['id'],
                'text' => $item['topic']
            ];
        }

        return $options;
    }

    /**
     * Period options.
     *
     * @return array
     */
    public static function getPeriodOptions(): array
    {
        return \Gcms\Controller::arrayToOptions(Language::get('LEAVE_PERIOD'));
    }

    /**
     * Status options.
     *
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return \Gcms\Controller::arrayToOptions(Language::get('LEAVE_STATUS'));
    }

    /**
     * Calculate the number of leave days based on the start and end dates and periods, and validate against policies.
     *
     * @param array $save The data to be saved, including start_date, end_date, start_period, end_period
     *
     * @return string A string of error messages if there are policy violations, or an empty string if valid
     */
    public static function calculateLeaveDays(&$save)
    {
        $errors = [];

        $startPeriod = isset($save['start_period']) ? (int) $save['start_period'] : -1;
        $endPeriod = isset($save['end_period']) ? (int) $save['end_period'] : -1;
        $startWeight = self::getLeavePeriodWeight($startPeriod);
        $endWeight = self::getLeavePeriodWeight($endPeriod);

        if (empty($save['start_date'])) {
            $errors[] = '{LNG_Start date}';
        }

        if (empty($save['end_date'])) {
            // ไม่ได้กรอกวันที่สิ้นสุดมา ใช้วันที่เดียวกันกับวันที่เริ่มต้น (ลา 1 วัน)
            $save['end_date'] = $save['start_date'];
        }

        $sameDay = !empty($save['start_date']) && $save['start_date'] === $save['end_date'];
        if ($sameDay && $startPeriod === 0) {
            $endPeriod = 0;
            $endWeight = $startWeight;
            $save['end_period'] = 0;
        }

        if ($startWeight === null) {
            $errors[] = '{LNG_Start period}';
        }
        if ($endWeight === null) {
            $errors[] = '{LNG_End period}';
        }

        if (!empty($errors)) {
            return '{LNG_Please select} '.implode(', ', $errors);
        }

        // คำนวณจำนวนวันลาตามวันที่เริ่มต้นและวันที่สิ้นสุด และตรวจสอบนโยบายที่เกี่ยวข้อง
        $diff = Date::compare($save['start_date'], $save['end_date']);
        if ($diff['days'] > 0 && $startPeriod === 1) {
            // ถ้าลาหลายวัน ไม่สามารถเลือกตัวเลือก ครึ่งวันเช้าได้
            $errors[] = '{LNG_Cannot select this option}: {LNG_Start period}';
        } elseif ($diff['days'] > 0 && $endPeriod === 2) {
            // ถ้าลาหลายวัน ไม่สามารถเลือกตัวเลือก ครึ่งวันบ่ายเป็นวันสิ้นสุดได้
            $errors[] = '{LNG_Cannot select this option}: {LNG_End period}';
        } else {
            if ($save['end_date'] < $save['start_date']) {
                // วันที่สิ้นสุด น้อยกว่าวันที่เริ่มต้น
                $errors[] = '{LNG_End date} {LNG_must be greater than or equal to the start date}';
            } elseif ($sameDay) {
                if ($startPeriod === 1 && $endPeriod === 2) {
                    $save['start_period'] = 0;
                    $save['end_period'] = 0;
                    $save['days'] = $startWeight + $endWeight;
                } elseif ($startPeriod !== $endPeriod) {
                    $errors[] = '{LNG_For leave on the same day, the start and end periods must be the same}';
                } else {
                    // ลาภายใน 1 วัน ใช้จำนวนวันลาจาก คาบการลา
                    $save['days'] = $startWeight;
                }
            } else {
                // ตรวจสอบลาข้ามปีงบประมาณ
                $end_year = date('Y', strtotime($save['end_date']));
                $start_year = date('Y', strtotime($save['start_date']));
                $check_year = max($end_year, $start_year);
                $fiscal_year = $check_year.sprintf('-%02d-01', self::$cfg->eleave_fiscal_year);
                if ($save['start_date'] < $fiscal_year && $save['end_date'] >= $fiscal_year) {
                    // ไม่สามารถเลือกวันลาข้ามปีงบประมาณได้
                    $errors[] = '{LNG_Unable to take leave across the fiscal year. If you want to take continuous leave, separate the leave form into two. within that fiscal year}';
                } else {
                    // ใช้จำนวนวันลาจากที่คำนวณ
                    $save['days'] = $diff['days'] + $startWeight + $endWeight - 1;
                }
            }
        }

        return implode(', ', $errors);
    }

    /**
     * คืนค่าน้ำหนักวันลาตามช่วงเวลา
     *
     * @param int $period
     *
     * @return float|null
     */
    public static function getLeavePeriodWeight(int $period): ?float
    {
        return isset(self::$cfg->eleave_periods[$period]) ? (float) self::$cfg->eleave_periods[$period] : null;
    }

    /**
     * Create display text for the selected leave type.
     *
     * @param object|null $leaveType
     *
     * @return string
     */
    protected function buildLeaveTypeDetail($leaveType): string
    {
        if (!$leaveType) {
            return '';
        }

        $parts = [(string) $leaveType->topic];
        if ((float) $leaveType->num_days > 0) {
            $parts[] = self::formatDays((float) $leaveType->num_days).' {LNG_days}/{LNG_year}';
        }
        if (!empty($leaveType->detail)) {
            $parts[] = trim((string) $leaveType->detail);
        }

        return implode(' • ', array_values(array_filter($parts, static function ($item) {
            return $item !== '';
        })));
    }

    /**
     * Format days for display.
     *
     * @param float $days
     *
     * @return string
     */
    public static function formatDays(float $days): string
    {
        $formatted = number_format($days, 1, '.', '');
        return rtrim(rtrim($formatted, '0'), '.');
    }

    /**
     * คืนค่า label สถานะ + สี
     *
     * @param mixed $value
     * @param bool $color true (default) คืนค่าสถานะพร้อมสี, false คืนค่าสถานะ text
     *
     * @return string
     */
    public static function showStatus($value, $color = true)
    {
        $statuses = Language::get('LEAVE_STATUS');
        if (isset($statuses[$value])) {
            return $color ? '<span class="term'.$value.'">'.$statuses[$value].'</span>' : $statuses[$value];
        }
        return '';
    }

    /**
     * คืนค่าวันลาที่จัดรูปแบบแล้ว
     *
     * @param object|array $leave
     *
     * @return string
     */
    public static function formatLeaveDate($leave)
    {
        $startDate = is_array($leave) ? ($leave['start_date'] ?? '') : ($leave->start_date ?? '');
        $endDate = is_array($leave) ? ($leave['end_date'] ?? '') : ($leave->end_date ?? '');
        $startPeriod = (int) (is_array($leave) ? ($leave['start_period'] ?? -1) : ($leave->start_period ?? -1));
        $endPeriod = (int) (is_array($leave) ? ($leave['end_period'] ?? -1) : ($leave->end_period ?? -1));

        if ($startDate === '' || $endDate === '') {
            return '';
        }

        $leave_period = Language::get('LEAVE_PERIOD');
        $startPeriodText = $leave_period[$startPeriod] ?? '';
        $endPeriodText = $leave_period[$endPeriod] ?? '';
        if ($startDate == $endDate) {
            // ลาภายใน 1 วัน แสดงวันที่เดียว พร้อมคาบการลา
            return trim(Date::format($startDate, 'd M Y').' '.$startPeriodText);
        }

        return trim(Date::format($startDate, 'd M Y').' '.$startPeriodText).' - '.trim(Date::format($endDate, 'd M Y').' '.$endPeriodText);
    }
}
