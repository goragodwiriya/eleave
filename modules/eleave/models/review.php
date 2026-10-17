<?php
/**
 * @filesource modules/eleave/models/review.php
 */

namespace Eleave\Review;

use Eleave\Helper\Controller as Helper;
use Eleave\Request\Model as RequestModel;
use Kotchasan\Date;

class Model extends \Kotchasan\Model
{
    /**
     * Get a leave request for approver review.
     *
     * @param int $id
     *
     * @return object|null
     */
    public static function get(int $id)
    {
        if ($id <= 0) {
            return null;
        }

        return static::createQuery()
            ->select(
                'LI.*',
                'U.name member_name',
                'U.username member_username',
                'L.topic leave_topic',
                'L.detail leave_detail',
                'L.num_days leave_num_days',
                'C.topic department_name'
            )
            ->from('leave_items LI')
            ->join('user U', ['U.id', 'LI.member_id'], 'LEFT')
            ->join('leave L', ['L.id', 'LI.leave_id'], 'LEFT')
            ->join('category C', [
                ['C.category_id', 'LI.department'],
                ['C.type', 'department']
            ], 'LEFT')
            ->where(['LI.id', $id])
            ->first();
    }

    /**
     * Check if an approver can still process the request.
     *
     * @param object|null $row
     *
     * @return bool
     */
    public static function canProcess($row): bool
    {
        if (!$row) {
            return false;
        }

        return (int) $row->status === 0;
    }

    /**
     * Build the compact balance summary for the review page.
     *
     * @param object|null $row
     *
     * @return array
     */
    public static function getBalanceSummary($row): array
    {
        if (!$row) {
            return [
                'years' => [],
                'errors' => ['No data available']
            ];
        }

        $leaveType = Helper::getLeaveTypeById((int) $row->leave_id);
        $requestedByYear = RequestModel::splitLeaveDaysByYear(
            (string) $row->start_date,
            (int) $row->start_period,
            (string) $row->end_date,
            (int) $row->end_period
        );
        $errors = [];

        if ($leaveType === null) {
            $errors[] = 'Leave type is not available';
        }
        if (empty($requestedByYear)) {
            $errors[] = 'Unable to calculate leave balance';
        }

        $quotaDays = $leaveType !== null && (float) $leaveType->num_days > 0 ? (float) $leaveType->num_days : null;
        $usageByYear = self::getUsageByYear(
            (int) $row->member_id,
            (int) $row->leave_id,
            array_keys($requestedByYear),
            (int) $row->id
        );

        ksort($requestedByYear);
        $years = [];
        foreach ($requestedByYear as $year => $requestedDays) {
            $requestedDays = (float) $requestedDays;
            $approvedDays = (float) ($usageByYear[$year]['approved_days'] ?? 0.0);
            $pendingDays = (float) ($usageByYear[$year]['pending_days'] ?? 0.0);
            $usedDays = $approvedDays + $pendingDays + $requestedDays;
            $remainingDays = $quotaDays === null ? null : $quotaDays - $usedDays;
            $canRequest = $quotaDays === null || $remainingDays >= -0.00001;
            $blockReason = '';
            if (!$canRequest) {
                $blockReason = '{LNG_Insufficient leave balance for year} '.Date::format($year.'-01-01', 'Y');
                $errors[] = $blockReason;
            }

            $years[] = [
                'year' => Date::format($year.'-01-01', 'Y'),
                'quota_days' => $quotaDays,
                'quota_text' => self::formatQuotaValue($quotaDays),
                'requested_days' => $requestedDays,
                'requested_text' => RequestModel::formatDays($requestedDays),
                'used_days' => $usedDays,
                'remaining_days' => $remainingDays,
                'remaining_text' => self::formatQuotaValue($remainingDays),
                'can_request' => $canRequest,
                'block_reason' => $blockReason
            ];
        }

        return [
            'years' => $years,
            'errors' => array_values(array_unique(array_filter($errors)))
        ];
    }

    /**
     * Extract blocking messages from the review balance summary.
     *
     * @param array $balanceSummary
     *
     * @return array
     */
    public static function getBalanceErrors(array $balanceSummary): array
    {
        $errors = isset($balanceSummary['errors']) && is_array($balanceSummary['errors']) ? $balanceSummary['errors'] : [];
        if (!empty($balanceSummary['years']) && is_array($balanceSummary['years'])) {
            foreach ($balanceSummary['years'] as $year) {
                if (!empty($year['block_reason'])) {
                    $errors[] = (string) $year['block_reason'];
                }
            }
        }

        return array_values(array_unique(array_filter($errors)));
    }

    /**
     * Update request status after a review decision.
     *
     * @param int $id
     * @param int $status
     * @param int|null $approve
     * @param int|null $closed
     *
     * @return void
     */
    public static function updateStatus(int $id, int $status, ?int $approve, ?int $closed = 1): void
    {
        \Kotchasan\DB::create()->update('leave_items', ['id', $id], [
            'status' => $status,
            'approve' => $approve,
            'closed' => $closed
        ]);
    }

    /**
     * Get current usage totals per year for a member and leave type.
     *
     * @param int $memberId
     * @param int $leaveId
     * @param array $years
     * @param int $excludeRequestId
     *
     * @return array
     */
    protected static function getUsageByYear(int $memberId, int $leaveId, array $years, int $excludeRequestId = 0): array
    {
        $years = array_values(array_unique(array_map('intval', $years)));
        if ($memberId <= 0 || $leaveId <= 0 || empty($years)) {
            return [];
        }

        $where = [
            ['member_id', $memberId],
            ['leave_id', $leaveId],
            ['status', 'IN', [0, 1]]
        ];
        if ($excludeRequestId > 0) {
            $where[] = ['id', '!=', $excludeRequestId];
        }

        $rows = static::createQuery()
            ->select('id', 'status', 'start_date', 'start_period', 'end_date', 'end_period')
            ->from('leave_items')
            ->where($where)
            ->fetchAll();

        $usage = [];
        $allowedYears = array_flip($years);
        foreach ($rows as $row) {
            $split = RequestModel::splitLeaveDaysByYear(
                (string) $row->start_date,
                (int) $row->start_period,
                (string) $row->end_date,
                (int) $row->end_period
            );
            foreach ($split as $year => $days) {
                if (!isset($allowedYears[(int) $year])) {
                    continue;
                }
                if (!isset($usage[(int) $year])) {
                    $usage[(int) $year] = [
                        'approved_days' => 0.0,
                        'pending_days' => 0.0
                    ];
                }
                $key = (int) $row->status === 1 ? 'approved_days' : 'pending_days';
                $usage[(int) $year][$key] += (float) $days;
            }
        }

        return $usage;
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
        return $days === null ? 'Unlimited' : RequestModel::formatDays($days);
    }
}