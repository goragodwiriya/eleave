<?php
/**
 * @filesource modules/eleave/models/statistics.php
 */

namespace Eleave\Statistics;

use Eleave\Fiscalyear\Controller as FiscalyearController;
use Eleave\Helper\Controller as Helper;
use Eleave\Request\Model as RequestModel;
use Kotchasan\Database\Sql;
use Kotchasan\Date;

class Model extends \Kotchasan\Model
{
    /**
     * Soft color palette for personal balance bars.
     *
     * @var array
     */
    protected const LEAVE_COLORS = [
        '#4f7cff',
        '#00a991',
        '#f59e0b',
        '#ef6a5b',
        '#7c5cff',
        '#0ea5e9',
        '#84cc16',
        '#ec4899'
    ];

    /**
     * Safe fallback period weights for summary calculations.
     *
     * @var array
     */
    protected const DEFAULT_PERIOD_WEIGHTS = [
        0 => 1.0,
        1 => 0.5,
        2 => 0.5
    ];

    /**
     * Build the personal leave balance payload for the selected fiscal year.
     *
     * @param object $user
     * @param int $fiscalYear
     *
     * @return array
     */
    public static function getStatistics($user, $fiscalYear): array
    {
        $currentFiscalYear = (int) FiscalyearController::get();
        $fiscalYear = (int) $fiscalYear;
        if ($fiscalYear <= 0) {
            $fiscalYear = $currentFiscalYear;
        }

        $range = FiscalyearController::toDate($fiscalYear);
        $rows = self::buildLeaveRows($user->id, (string) $range['from'], (string) $range['to']);

        return [
            'member_id' => $user->id,
            'name' => $user->name,
            'year' => Date::format(strtotime($fiscalYear.'-01-01'), 'Y'),
            'current_fiscal_year' => (string) $currentFiscalYear,
            'range' => [
                'from' => (string) $range['from'],
                'to' => (string) $range['to'],
                'text' => self::formatDateRange((string) $range['from'], (string) $range['to'])
            ],
            'options' => [
                'year' => self::getYearOptions($user->id, $currentFiscalYear)
            ],
            'summary' => self::buildSummary($rows, $fiscalYear),
            'rows' => $rows
        ];
    }

    /**
     * Build fiscal year select options.
     *
     * @param int $memberId // ID ของสมาชิกที่ต้องการดึงข้อมูลปีงบประมาณ
     * @param int $currentFiscalYear // ปีงบประมาณปัจจุบัน
     *
     * @return array
     */
    public static function getYearOptions(int $memberId, int $currentFiscalYear): array
    {
        $rows = static::createQuery()
            ->select(Sql::MIN('start_date', 'from'), Sql::MAX('end_date', 'to'))
            ->from('leave_items')
            ->where(['member_id', $memberId])
            ->first();

        if (!$rows || empty($rows->from)) {
            $from = $currentFiscalYear;
            $to = $currentFiscalYear;
        } else {
            $fromFiscalYear = FiscalyearController::dateToFiscalyear((string) $rows->from);
            $toDate = !empty($rows->to) ? (string) $rows->to : (string) $rows->from;
            $toFiscalYear = FiscalyearController::dateToFiscalyear($toDate);
            $from = (int) ($fromFiscalYear['fiscal_year'] ?? $currentFiscalYear);
            $to = max($currentFiscalYear, (int) ($toFiscalYear['fiscal_year'] ?? $currentFiscalYear));
        }

        $options = [];
        for ($i = $from; $i <= $to; $i++) {
            $options[] = [
                'value' => (string) $i,
                'text' => Date::format(strtotime($i.'-01-01'), 'Y')
            ];
        }

        return $options;
    }

    /**
     * Build personal leave rows for each active leave type.
     *
     * @param int    $memberId
     * @param string $from
     * @param string $to
     *
     * @return array
     */
    protected static function buildLeaveRows(int $memberId, string $from, string $to): array
    {
        $leaveTypes = RequestModel::getLeaveTypes(true);
        $usageMap = self::getUsageMap($memberId, $from, $to);
        $rows = [];

        foreach ($leaveTypes as $index => $leaveType) {
            $leaveId = (int) ($leaveType['id'] ?? 0);
            $quotaDays = !empty($leaveType['num_days']) ? (float) $leaveType['num_days'] : null;
            $approvedDays = (float) ($usageMap[$leaveId]['approved_days'] ?? 0.0);
            $pendingDays = (float) ($usageMap[$leaveId]['pending_days'] ?? 0.0);
            $usedDays = $approvedDays + $pendingDays;
            $remainingDays = $quotaDays === null ? null : $quotaDays - $usedDays;
            $remainingDisplayDays = $remainingDays === null ? null : max(0, $remainingDays);
            $overageDays = $remainingDays !== null && $remainingDays < 0 ? abs($remainingDays) : 0.0;

            $rows[] = [
                'leave_id' => $leaveId,
                'leave_topic' => (string) ($leaveType['topic'] ?? ''),
                'leave_detail' => trim((string) ($leaveType['detail'] ?? '')),
                'color' => self::LEAVE_COLORS[$index % count(self::LEAVE_COLORS)],
                'has_quota' => $quotaDays !== null,
                'quota_days' => $quotaDays,
                'quota_text' => self::formatQuotaValue($quotaDays),
                'used_days' => $usedDays,
                'used_text' => Helper::formatDays($usedDays),
                'approved_days' => $approvedDays,
                'approved_text' => Helper::formatDays($approvedDays),
                'pending_days' => $pendingDays,
                'pending_text' => Helper::formatDays($pendingDays),
                'remaining_days' => $remainingDisplayDays,
                'remaining_text' => self::formatQuotaValue($remainingDisplayDays),
                'is_over_quota' => $overageDays > 0,
                'overage_days' => $overageDays,
                'overage_text' => Helper::formatDays($overageDays)
            ];
        }

        return $rows;
    }

    /**
     * Aggregate approved and pending leave days inside the selected fiscal range.
     *
     * @param int    $memberId
     * @param string $from
     * @param string $to
     *
     * @return array
     */
    protected static function getUsageMap(int $memberId, string $from, string $to): array
    {
        if ($memberId <= 0) {
            return [];
        }

        $rows = static::createQuery()
            ->select('leave_id', 'status', 'start_date', 'start_period', 'end_date', 'end_period')
            ->from('leave_items')
            ->where([
                ['member_id', $memberId],
                ['status', 'IN', [0, 1]],
                ['start_date', '<=', $to],
                ['end_date', '>=', $from]
            ])
            ->execute()
            ->fetchAll();

        $usage = [];
        foreach ($rows as $row) {
            $leaveId = (int) $row->leave_id;
            if (!isset($usage[$leaveId])) {
                $usage[$leaveId] = [
                    'approved_days' => 0.0,
                    'pending_days' => 0.0
                ];
            }

            $days = self::calculateDaysWithinRange(
                (string) $row->start_date,
                (int) $row->start_period,
                (string) ($row->end_date ?: $row->start_date),
                (int) $row->end_period,
                $from,
                $to
            );
            if ($days <= 0) {
                continue;
            }

            $key = (int) $row->status === 1 ? 'approved_days' : 'pending_days';
            $usage[$leaveId][$key] += $days;
        }

        return $usage;
    }

    /**
     * Calculate leave days that overlap with the current fiscal date range.
     *
     * @param string $startDate
     * @param int    $startPeriod
     * @param string $endDate
     * @param int    $endPeriod
     * @param string $rangeStartDate
     * @param string $rangeEndDate
     *
     * @return float
     */
    protected static function calculateDaysWithinRange(string $startDate, int $startPeriod, string $endDate, int $endPeriod, string $rangeStartDate, string $rangeEndDate): float
    {
        $startWeight = self::resolvePeriodWeight($startPeriod);
        $endWeight = self::resolvePeriodWeight($endPeriod);
        if ($startWeight === null || $endWeight === null) {
            return 0.0;
        }

        $start = \DateTimeImmutable::createFromFormat('Y-m-d', $startDate);
        $end = \DateTimeImmutable::createFromFormat('Y-m-d', $endDate);
        $rangeStart = \DateTimeImmutable::createFromFormat('Y-m-d', $rangeStartDate);
        $rangeEnd = \DateTimeImmutable::createFromFormat('Y-m-d', $rangeEndDate);
        if (!$start || !$end || !$rangeStart || !$rangeEnd || $start > $end || $rangeStart > $rangeEnd) {
            return 0.0;
        }

        if ($end < $rangeStart || $start > $rangeEnd) {
            return 0.0;
        }

        $effectiveStart = $start > $rangeStart ? $start : $rangeStart;
        $effectiveEnd = $end < $rangeEnd ? $end : $rangeEnd;
        $days = 0.0;
        $cursor = $effectiveStart;

        while ($cursor <= $effectiveEnd) {
            $date = $cursor->format('Y-m-d');
            if ($startDate === $endDate && $date === $startDate) {
                $days += $startPeriod === $endPeriod ? $startWeight : 1.0;
            } elseif ($date === $startDate) {
                $days += $startWeight;
            } elseif ($date === $endDate) {
                $days += $endWeight;
            } else {
                $days += 1.0;
            }
            $cursor = $cursor->modify('+1 day');
        }

        return $days;
    }

    /**
     * Build a compact summary block.
     *
     * @param array $rows
     * @param int   $fiscalYear
     *
     * @return array
     */
    protected static function buildSummary(array $rows, int $fiscalYear): array
    {
        $usedDays = 0.0;
        $pendingDays = 0.0;
        $overQuotaCount = 0;

        foreach ($rows as $row) {
            $usedDays += (float) ($row['used_days'] ?? 0.0);
            $pendingDays += (float) ($row['pending_days'] ?? 0.0);
            if (!empty($row['is_over_quota'])) {
                ++$overQuotaCount;
            }
        }

        return [
            'year' => $fiscalYear,
            'leave_type_count' => count($rows),
            'used_days' => $usedDays,
            'used_text' => Helper::formatDays($usedDays),
            'pending_days' => $pendingDays,
            'pending_text' => Helper::formatDays($pendingDays),
            'over_quota_count' => $overQuotaCount
        ];
    }

    /**
     * Format the current fiscal year range for display.
     *
     * @param string $from
     * @param string $to
     *
     * @return string
     */
    protected static function formatDateRange(string $from, string $to): string
    {
        return Date::format($from, 'd M Y').' - '.Date::format($to, 'd M Y');
    }

    /**
     * Format quota text with unlimited fallback.
     *
     * @param float|null $days
     *
     * @return string
     */
    protected static function formatQuotaValue(?float $days): string
    {
        return $days === null ? 'Unlimited' : Helper::formatDays($days);
    }

    /**
     * Get a period weight with a safe fallback for summary calculations.
     *
     * @param int $period
     *
     * @return float|null
     */
    protected static function resolvePeriodWeight(int $period): ?float
    {
        $weight = Helper::getLeavePeriodWeight($period);
        if ($weight !== null) {
            return $weight;
        }

        return self::DEFAULT_PERIOD_WEIGHTS[$period] ?? null;
    }
}
