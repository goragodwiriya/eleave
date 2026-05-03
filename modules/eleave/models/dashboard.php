<?php
/**
 * @filesource modules/eleave/models/dashboard.php
 */

namespace Eleave\Dashboard;

use Eleave\Approvals\Model as ApprovalsModel;
use Eleave\Fiscalyear\Controller as FiscalyearController;
use Eleave\Helper\Controller as EleaveHelper;
use Eleave\Request\Model as RequestModel;
use Gcms\Api as ApiController;
use Kotchasan\Database\Sql;
use Kotchasan\Date;
use Kotchasan\Language;

class Model extends \Kotchasan\Model
{
    /**
     * Get card and section metadata for the dashboard shell.
     *
     * @param object $login
     *
     * @return array
     */
    public static function getCards($login): array
    {
        $context = self::buildContext($login);
        $summary = self::getMySummary((int) $login->id, (string) $context['range']['from'], (string) $context['range']['to']);
        $approvalCount = $context['canApprove'] ? self::getPendingApprovalCount($login) : 0;

        return [
            'year' => (string) $context['fiscalYear'],
            'year_label' => Date::format($context['fiscalYear'].'-01-01', 'Y'),
            'department_name' => $context['departmentName'],
            'summary' => $summary,
            'returned_status_label' => Language::get('LEAVE_STATUS', '', 4),
            'show_approvals' => $context['canApprove'],
            'approval_count' => $approvalCount,
            'logs_note' => $context['canReviewAll']
                ? '{LNG_Recent approval decisions that may need follow-up}'
                : '{LNG_Recent approval decisions for your leave requests}',
            'show_department_chart' => $context['canReviewAll'],
            'links' => [
                'my_pending' => '/my-leaves?status=0',
                'my_returned' => '/my-leaves?status=4',
                'my_approved' => '/my-leaves?status=1',
                'approvals' => '/leave-approvals?status=0'
            ]
        ];
    }

    /**
     * Get department graph data for the dashboard graph request.
     *
     * @param object $login
     *
     * @return array
     */
    public static function getGraph($login): array
    {
        $context = self::buildContext($login);
        if (!$context['canReviewAll']) {
            return [
                'series' => [],
                'note' => '',
                'total_departments' => 0
            ];
        }

        return self::getDepartmentChart((string) $context['range']['from'], (string) $context['range']['to']);
    }

    /**
     * Query data to send to the dashboard approval log table.
     *
     * @param array  $params
     * @param object $login
     *
     * @return array
     */
    public static function toDataTable($params)
    {

        return \Kotchasan\Model::createQuery()
            ->select(
                'O.id',
                'O.src_id request_id',
                'O.topic',
                'O.reason',
                'O.created_at',
                'O.module',
                'O.action',
                'M.name member_name'
            )
            ->from('logs O')
            ->join('user M', ['M.id', Sql::column('O.member_id')], 'LEFT')
            ->where([
                ['O.module', 'eleave'],
                ['O.action', 'Status']
            ])
            ->orderBy('O.created_at', 'DESC')
            ->cacheOn()
            ->limit(10)
            ->fetchAll();
    }

    /**
     * Build reusable dashboard context.
     *
     * @param object $login
     *
     * @return array
     */
    protected static function buildContext($login): array
    {
        $fiscalYear = (int) FiscalyearController::get();
        $range = FiscalyearController::toDate($fiscalYear);
        $canApprove = RequestModel::canApproveRequests($login);
        $isSuperAdmin = ApiController::isSuperAdmin($login);
        $canReviewAll = $canApprove || $isSuperAdmin;

        $user = \Index\Profile\Model::view((int) $login->id);
        $departmentName = $user && !empty($user->department)
            ? RequestModel::getDepartmentNameById((string) $user->department)
            : '';

        return [
            'fiscalYear' => $fiscalYear,
            'range' => $range,
            'canApprove' => $canApprove,
            'canReviewAll' => $canReviewAll,
            'departmentName' => $departmentName
        ];
    }

    /**
     * Summarize the current member's requests for the current fiscal year.
     *
     * @param int    $memberId
     * @param string $from
     * @param string $to
     *
     * @return array
     */
    protected static function getMySummary(int $memberId, string $from, string $to): array
    {
        $rows = static::createQuery()
            ->select('status', 'days')
            ->from('leave_items')
            ->where([
                ['member_id', $memberId],
                ['start_date', '>=', $from],
                ['start_date', '<=', $to]
            ])
            ->fetchAll();

        $summary = [
            'my_pending_requests' => 0,
            'my_returned_requests' => 0,
            'my_approved_requests' => 0,
            'my_approved_days' => 0.0,
            'my_approved_days_text' => EleaveHelper::formatDays(0.0)
        ];

        foreach ($rows as $row) {
            $status = (int) $row->status;
            if ($status === 0) {
                ++$summary['my_pending_requests'];
            } elseif ($status === 4) {
                ++$summary['my_returned_requests'];
            } elseif ($status === 1) {
                ++$summary['my_approved_requests'];
                $summary['my_approved_days'] += (float) $row->days;
            }
        }

        $summary['my_approved_days_text'] = EleaveHelper::formatDays((float) $summary['my_approved_days']);

        return $summary;
    }

    /**
     * Count requests that require approval.
     *
     * @return int
     */
    protected static function getPendingApprovalCount($login): int
    {
        $result = static::createQuery()
            ->selectRaw('COUNT(*) AS total')
            ->from([ApprovalsModel::toDataTable(['status' => 0], $login)->copy(), 'Q'])
            ->first();

        return isset($result->total) ? (int) $result->total : 0;
    }

    /**
     * Build department leave chart data for the current fiscal year.
     *
     * @param string $from
     * @param string $to
     *
     * @return array
     */
    protected static function getDepartmentChart(string $from, string $to): array
    {
        $rows = static::createQuery()
            ->select('LI.department')
            ->selectRaw('MIN(C.topic) AS department_name')
            ->selectRaw('COALESCE(SUM(CASE WHEN LI.status = 1 THEN LI.days ELSE 0 END), 0) AS approved_days')
            ->selectRaw('COALESCE(SUM(CASE WHEN LI.status = 0 THEN LI.days ELSE 0 END), 0) AS pending_days')
            ->from('leave_items LI')
            ->join('category C', [
                ['C.category_id', 'LI.department'],
                ['C.type', 'department']
            ], 'LEFT')
            ->where([
                ['LI.status', 'IN', [0, 1]],
                ['LI.start_date', '>=', $from],
                ['LI.start_date', '<=', $to]
            ])
            ->groupBy('LI.department')
            ->fetchAll();

        $departments = [];
        foreach ($rows as $row) {
            $approvedDays = round((float) $row->approved_days, 1);
            $pendingDays = round((float) $row->pending_days, 1);
            if ($approvedDays <= 0 && $pendingDays <= 0) {
                continue;
            }

            $departments[] = [
                'label' => trim((string) $row->department_name) !== '' ? (string) $row->department_name : 'Not specified',
                'approved_days' => $approvedDays,
                'pending_days' => $pendingDays,
                'total_days' => $approvedDays + $pendingDays
            ];
        }

        usort($departments, static function (array $first, array $second): int {
            return $second['total_days'] <=> $first['total_days'];
        });

        $approvedSeries = [];
        $pendingSeries = [];
        foreach ($departments as $department) {
            $approvedSeries[] = [
                'label' => $department['label'],
                'value' => $department['approved_days']
            ];
            $pendingSeries[] = [
                'label' => $department['label'],
                'value' => $department['pending_days']
            ];
        }

        $series = [];
        if (!empty(array_filter($approvedSeries, static function (array $point): bool {
            return $point['value'] > 0;
        }))) {
            $series[] = [
                'name' => Language::get('LEAVE_STATUS', '', 1),
                'data' => $approvedSeries
            ];
        }
        if (!empty(array_filter($pendingSeries, static function (array $point): bool {
            return $point['value'] > 0;
        }))) {
            $series[] = [
                'name' => Language::get('LEAVE_STATUS', '', 0),
                'data' => $pendingSeries
            ];
        }

        return [
            'series' => $series,
            'total_departments' => count($departments),
            'note' => 'Approved and pending leave days grouped by department for the current fiscal year'
        ];
    }
}