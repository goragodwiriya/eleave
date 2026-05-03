<?php
/**
 * @filesource modules/eleave/models/request.php
 */

namespace Eleave\Request;

use Eleave\Helper\Controller as Helper;
use Kotchasan\Language;
use Kotchasan\Text;

class Model extends \Kotchasan\Model
{
    protected const DEFAULT_PERIOD_WEIGHTS = [
        0 => 1.0,
        1 => 0.5,
        2 => 0.5
    ];

    /**
     * Permission helper for approval workflow.
     *
     * @param object|null $login
     *
     * @return bool
     */
    public static function canApproveRequests($login): bool
    {
        return Helper::canApproveRequests($login);
    }

    /**
     * Get leave types.
     *
     * @param bool $isActiveOnly
     * @param int|null $includeId
     *
     * @return array
     */
    public static function getLeaveTypes(bool $isActiveOnly = true, ?int $includeId = null): array
    {
        return Helper::getLeaveTypes($isActiveOnly, $includeId);
    }

    /**
     * Get leave type options.
     *
     * @param bool $isActiveOnly
     * @param int|null $includeId
     *
     * @return array
     */
    public static function getLeaveTypeOptions(bool $isActiveOnly = true, ?int $includeId = null): array
    {
        return Helper::getLeaveTypeOptions($isActiveOnly, $includeId);
    }

    /**
     * Get leave status options.
     *
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return Helper::getStatusOptions();
    }

    /**
     * Format leave days for display.
     *
     * @param float $days
     *
     * @return string
     */
    public static function formatDays(float $days): string
    {
        return Helper::formatDays($days);
    }

    /**
     * Get leave status label.
     *
     * @param int $status
     *
     * @return string
     */
    public static function getStatusText(int $status): string
    {
        return Helper::showStatus($status, false);
    }

    /**
     * Get leave period label.
     *
     * @param int $period
     *
     * @return string
     */
    public static function getPeriodText(int $period): string
    {
        $periods = Language::get('LEAVE_PERIOD');

        return isset($periods[$period]) ? (string) $periods[$period] : '';
    }

    /**
     * Department options for filters.
     *
     * @return array
     */
    public static function getDepartmentOptions(): array
    {
        $rows = static::createQuery()
            ->select('category_id', 'topic')
            ->from('category')
            ->where([
                ['type', 'department']
            ])
            ->orderBy('topic')
            ->fetchAll();

        $options = [];
        foreach ($rows as $row) {
            $options[] = [
                'value' => (string) $row->category_id,
                'text' => (string) $row->topic
            ];
        }

        return $options;
    }

    /**
     * Get department name by id.
     *
     * @param string $departmentId
     *
     * @return string
     */
    public static function getDepartmentNameById(string $departmentId): string
    {
        return (string) \Gcms\Category::init()->get('department', $departmentId);
    }

    /**
     * Get request data for form.
     *
     * @param object $login
     * @param int $id
     *
     * @return object|null
     */
    public static function get($login, int $id = 0)
    {
        $leaveTypes = \Eleave\Helper\Controller::getLeaveTypeOptions();
        if ($id === 0) {
            $department = isset($login->metas['department'][0]) ? trim((string) $login->metas['department'][0]) : '';
            $closedLevel = Helper::getApprovalLevelCount();
            $first = reset($leaveTypes);
            $record = (object) [
                'id' => 0,
                'leave_id' => (string) ($first['value'] ?? ''),
                'member_id' => (int) $login->id,
                'member_name' => (string) $login->name,
                'department' => $department,
                'start_date' => '',
                'end_date' => '',
                'start_period' => '0',
                'end_period' => '0',
                'detail' => '',
                'communication' => '',
                'status' => $closedLevel === 0 ? 1 : 0,
                'closed' => $closedLevel > 0 ? $closedLevel : 1,
                'approve' => 1,
                'eleave' => [],
                'canEdit' => true
            ];
        } else {
            $record = static::createQuery()
                ->select('LI.*', 'U.name member_name', 'G.topic department_name')
                ->from('leave_items LI')
                ->join('user U', ['U.id', 'LI.member_id'], 'LEFT')
                ->join('category G', [['G.category_id', 'LI.department'], ['G.type', 'department']], 'LEFT')
                ->where([
                    ['LI.id', $id],
                    ['LI.member_id', $login->id]
                ])
                ->first();

            if (!$record) {
                return null;
            }

            $record->eleave = \Download\Index\Controller::getAttachments($record->id, 'eleave', self::$cfg->eleave_file_types);
            $record->canEdit = self::canEdit($record);
        }

        $record->department_name = \Gcms\Category::init()->get('department', (string) ($record->department ?? ''));
        $record->file_types = empty(self::$cfg->eleave_file_types) ? '' : '.'.implode(', .', self::$cfg->eleave_file_types);
        $record->max_size = Text::formatFileSize(self::$cfg->eleave_upload_size);
        $record->options = [
            'leave_id' => $leaveTypes,
            'start_period' => \Eleave\Helper\Controller::getPeriodOptions(),
            'end_period' => \Eleave\Helper\Controller::getPeriodOptions()
        ];

        return $record;
    }

    /**
     * Get a request record owned by a member.
     *
     * @param int $memberId
     * @param int $id
     *
     * @return object|null
     */
    public static function getRecord(int $memberId, int $id)
    {
        return static::createQuery()
            ->select()
            ->from('leave_items')
            ->where([
                ['id', $id],
                ['member_id', $memberId]
            ])
            ->first();
    }

    /**
     * Save leave request.
     *
     * @param int $id
     * @param array $save
     *
     * @return int
     */
    public static function saveRequest(int $id, array $save): int
    {
        $db = \Kotchasan\DB::create();
        if ($id > 0) {
            $db->update('leave_items', ['id', $id], $save);
            return $id;
        }

        $save['created_at'] = date('Y-m-d H:i:s');
        return (int) $db->insert('leave_items', $save);
    }

    /**
     * Check if request can be edited.
     *
     * @param object $row
     *
     * @return bool
     */
    public static function canEdit($row): bool
    {
        if (!$row) {
            return false;
        }

        return in_array((int) $row->status, [0, 4], true);
    }

    /**
     * Check if member can cancel the request.
     *
     * @param object|null $row
     *
     * @return bool
     */
    public static function canCancel($row): bool
    {
        if (!$row) {
            return false;
        }

        return in_array((int) $row->status, [0, 4], true);
    }

    /**
     * Split leave days across calendar years for shared leave calculations.
     *
     * @param string $startDate
     * @param int $startPeriod
     * @param string $endDate
     * @param int $endPeriod
     *
     * @return array
     */
    public static function splitLeaveDaysByYear(string $startDate, int $startPeriod, string $endDate, int $endPeriod): array
    {
        $startWeight = self::getPeriodWeight($startPeriod);
        $endWeight = self::getPeriodWeight($endPeriod);
        if ($startWeight === null || $endWeight === null) {
            return [];
        }

        $start = \DateTimeImmutable::createFromFormat('Y-m-d', $startDate);
        $end = \DateTimeImmutable::createFromFormat('Y-m-d', $endDate);
        if (!$start || !$end || $start > $end) {
            return [];
        }

        if ($startDate === $endDate) {
            return [
                (int) $start->format('Y') => $startPeriod === $endPeriod ? $startWeight : 1.0
            ];
        }

        $result = [];
        $cursor = $start;
        while ($cursor <= $end) {
            $date = $cursor->format('Y-m-d');
            if ($date === $startDate) {
                $value = $startWeight;
            } elseif ($date === $endDate) {
                $value = $endWeight;
            } else {
                $value = 1.0;
            }

            $year = (int) $cursor->format('Y');
            $result[$year] = ($result[$year] ?? 0.0) + $value;
            $cursor = $cursor->modify('+1 day');
        }

        return $result;
    }

    /**
     * Update the request status for member actions.
     *
     * @param int $id
     * @param array $fields
     *
     * @return void
     */
    public static function updateRequestStatus(int $id, array $fields): void
    {
        \Kotchasan\DB::create()->update('leave_items', ['id', $id], $fields);
    }

    /**
     * Get a period weight with a safe fallback for summary calculations.
     *
     * @param int $period
     *
     * @return float|null
     */
    protected static function getPeriodWeight(int $period): ?float
    {
        $weight = Helper::getLeavePeriodWeight($period);
        if ($weight !== null) {
            return $weight;
        }

        return isset(self::DEFAULT_PERIOD_WEIGHTS[$period]) ? self::DEFAULT_PERIOD_WEIGHTS[$period] : null;
    }
}