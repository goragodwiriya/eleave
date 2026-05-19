<?php
/**
 * @filesource modules/eleave/models/balance.php
 */

namespace Eleave\Balance;

use Eleave\Request\Model as RequestModel;

class Model extends \Kotchasan\Model
{
    /**
     * Build balance report rows.
     *
     * @param object $login
     * @param array $filters
     *
     * @return array
     */
    public static function getReport($login, array $filters): array
    {
        $year = !empty($filters['year']) ? (int) $filters['year'] : (int) date('Y');
        $canViewAll = RequestModel::canApproveRequests($login);
        $memberId = $canViewAll ? (int) ($filters['member_id'] ?? 0) : (int) $login->id;
        $departmentId = $canViewAll ? trim((string) ($filters['department'] ?? '')) : '';
        $leaveId = (int) ($filters['leave_id'] ?? 0);

        $members = self::getMembers($login, $canViewAll, $memberId, $departmentId);
        $leaveTypes = $leaveId > 0
            ? array_values(array_filter(RequestModel::getLeaveTypes(false, $leaveId), function ($item) use ($leaveId) {
            return (int) $item['id'] === $leaveId;
        }))
            : RequestModel::getLeaveTypes(true);

        if (empty($members) || empty($leaveTypes)) {
            return [
                'rows' => [],
                'summary' => [
                    'year' => $year,
                    'employee_count' => count($members),
                    'leave_type_count' => count($leaveTypes),
                    'row_count' => 0,
                    'negative_count' => 0
                ],
                'scope_note' => self::buildScopeNote($year, $canViewAll, $members, $departmentId)
            ];
        }

        $memberIds = array_map(function ($member) {
            return (int) $member['id'];
        }, $members);
        $leaveIds = array_map(function ($leaveType) {
            return (int) $leaveType['id'];
        }, $leaveTypes);
        $usageMap = self::getUsageMap($memberIds, $leaveIds, $year);

        $rows = [];
        $negativeCount = 0;
        foreach ($members as $member) {
            foreach ($leaveTypes as $leaveType) {
                $rawQuotaDays = (float) $leaveType['num_days'];
                $quotaDays = $rawQuotaDays > 0 ? $rawQuotaDays : null;
                $hasQuota = $quotaDays !== null;
                $usage = $usageMap[(int) $member['id']][(int) $leaveType['id']] ?? [
                    'approved_days' => 0.0,
                    'pending_days' => 0.0
                ];
                $approvedDays = (float) ($usage['approved_days'] ?? 0.0);
                $pendingDays = (float) ($usage['pending_days'] ?? 0.0);
                $remainingDays = $hasQuota ? $quotaDays - $approvedDays - $pendingDays : null;
                if ($remainingDays !== null && $remainingDays < -0.00001) {
                    ++$negativeCount;
                }

                $rows[] = [
                    'member_id' => (int) $member['id'],
                    'member_name' => $member['name'],
                    'department_name' => $member['department_name'],
                    'leave_id' => (int) $leaveType['id'],
                    'leave_topic' => $leaveType['topic'],
                    'year' => $year,
                    'quota_days' => $quotaDays,
                    'quota_text' => self::formatQuotaDays($quotaDays),
                    'approved_days' => $approvedDays,
                    'approved_text' => RequestModel::formatDays($approvedDays),
                    'pending_days' => $pendingDays,
                    'pending_text' => RequestModel::formatDays($pendingDays),
                    'remaining_days' => $remainingDays,
                    'remaining_text' => self::formatQuotaDays($remainingDays),
                    'remaining_color' => $remainingDays !== null && $remainingDays < 0 ? 'var(--status2, #b91c1c)' : 'inherit'
                ];
            }
        }

        usort($rows, function ($first, $second) {
            $memberCompare = strcmp((string) $first['member_name'], (string) $second['member_name']);
            if ($memberCompare !== 0) {
                return $memberCompare;
            }

            return strcmp((string) $first['leave_topic'], (string) $second['leave_topic']);
        });

        return [
            'rows' => $rows,
            'summary' => [
                'year' => $year,
                'employee_count' => count($members),
                'leave_type_count' => count($leaveTypes),
                'row_count' => count($rows),
                'negative_count' => $negativeCount
            ],
            'scope_note' => self::buildScopeNote($year, $canViewAll, $members, $departmentId)
        ];
    }

    /**
     * Member select options for report filters.
     *
     * @return array
     */
    public static function getMemberOptions(): array
    {
        $options = [];
        foreach (self::getMembersDirectory() as $member) {
            $text = $member['name'];
            if ($member['department_name'] !== '') {
                $text .= ' - '.$member['department_name'];
            }

            $options[] = [
                'value' => (string) $member['id'],
                'text' => $text
            ];
        }

        return $options;
    }

    /**
     * Build year filter options.
     *
     * @param int $selectedYear
     *
     * @return array
     */
    public static function getYearOptions(int $selectedYear): array
    {
        $currentYear = (int) date('Y');
        $years = [
            $currentYear - 1 => true,
            $currentYear => true,
            $currentYear + 1 => true,
            $selectedYear => true
        ];

        $leaveRows = static::createQuery()
            ->select('start_date', 'end_date')
            ->from('leave_items')
            ->execute()
            ->fetchAll();

        foreach ($leaveRows as $row) {
            if (!empty($row->start_date)) {
                $years[(int) substr((string) $row->start_date, 0, 4)] = true;
            }
            if (!empty($row->end_date)) {
                $years[(int) substr((string) $row->end_date, 0, 4)] = true;
            }
        }

        $options = [];
        $availableYears = array_keys($years);
        sort($availableYears);
        foreach ($availableYears as $year) {
            if ($year > 0) {
                $options[] = [
                    'value' => (string) $year,
                    'text' => (string) $year
                ];
            }
        }

        return $options;
    }

    /**
     * Get members available for the report.
     *
     * @param object $login
     * @param bool $canViewAll
     * @param int $memberId
     * @param string $departmentId
     *
     * @return array
     */
    protected static function getMembers($login, bool $canViewAll, int $memberId = 0, string $departmentId = ''): array
    {
        $members = self::getMembersDirectory();
        $result = [];
        foreach ($members as $member) {
            if (!$canViewAll && (int) $member['id'] !== (int) $login->id) {
                continue;
            }
            if ($memberId > 0 && (int) $member['id'] !== $memberId) {
                continue;
            }
            if ($departmentId !== '' && (string) $member['department_id'] !== $departmentId) {
                continue;
            }

            $result[] = $member;
        }

        return $result;
    }

    /**
     * Build a directory of members with department names.
     *
     * @return array
     */
    protected static function getMembersDirectory(): array
    {
        $rows = static::createQuery()
            ->select(
                'U.id',
                'U.name',
                'U.department department_id',
                'C.topic department_name'
            )
            ->from('user U')
            ->join('category C', [['C.category_id', 'U.department'], ['C.type', 'department']], 'LEFT')
            ->orderBy('U.name')
            ->fetchAll();

        $members = [];
        foreach ($rows as $row) {
            $id = (int) $row->id;
            if (isset($members[$id])) {
                continue;
            }

            $members[$id] = [
                'id' => $id,
                'name' => (string) $row->name,
                'department_id' => (string) ($row->department_id ?? ''),
                'department_name' => (string) ($row->department_name ?? '')
            ];
        }

        return array_values($members);
    }

    /**
     * Build leave usage map for a single year.
     *
     * @param array $memberIds
     * @param array $leaveIds
     * @param int $year
     *
     * @return array
     */
    protected static function getUsageMap(array $memberIds, array $leaveIds, int $year): array
    {
        $memberIds = array_values(array_unique(array_map('intval', array_filter($memberIds))));
        $leaveIds = array_values(array_unique(array_map('intval', array_filter($leaveIds))));
        if ($year <= 0 || empty($memberIds) || empty($leaveIds)) {
            return [];
        }

        $rows = static::createQuery()
            ->select('member_id', 'leave_id', 'status', 'start_date', 'start_period', 'end_date', 'end_period')
            ->from('leave_items')
            ->where([
                ['member_id', 'IN', $memberIds],
                ['leave_id', 'IN', $leaveIds],
                ['status', 'IN', [0, 1]]
            ])
            ->execute()
            ->fetchAll();

        $usage = [];
        foreach ($rows as $row) {
            $split = RequestModel::splitLeaveDaysByYear(
                (string) $row->start_date,
                (int) $row->start_period,
                (string) $row->end_date,
                (int) $row->end_period
            );
            if (!isset($split[$year])) {
                continue;
            }

            $memberId = (int) $row->member_id;
            $leaveId = (int) $row->leave_id;
            if (!isset($usage[$memberId][$leaveId])) {
                $usage[$memberId][$leaveId] = [
                    'approved_days' => 0.0,
                    'pending_days' => 0.0
                ];
            }

            $key = (int) $row->status === 1 ? 'approved_days' : 'pending_days';
            $usage[$memberId][$leaveId][$key] += (float) $split[$year];
        }

        return $usage;
    }

    /**
     * Build report scope note.
     *
     * @param int $year
     * @param bool $canViewAll
     * @param array $members
     * @param string $departmentId
     *
     * @return string
     */
    protected static function buildScopeNote(int $year, bool $canViewAll, array $members, string $departmentId): string
    {
        if (!$canViewAll) {
            return 'Showing your leave balance for '.$year;
        }

        if (count($members) === 1) {
            return 'Showing leave balance for '.$members[0]['name'].' in '.$year;
        }

        if ($departmentId !== '') {
            $departmentName = RequestModel::getDepartmentNameById($departmentId);
            return 'Showing leave balance for department '.$departmentName.' in '.$year;
        }

        return 'Showing leave balance for all employees in '.$year;
    }

    /**
     * Format quota text with unlimited fallback.
     *
     * @param float|null $days
     *
     * @return string
     */
    protected static function formatQuotaDays(?float $days): string
    {
        return $days === null ? 'Unlimited' : RequestModel::formatDays($days);
    }
}