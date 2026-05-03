<?php
/**
 * @filesource modules/eleave/models/approvals.php
 */

namespace Eleave\Approvals;

use Eleave\Helper\Controller as Helper;
use Kotchasan\Database\Sql;
use Kotchasan\File;

class Model extends \Kotchasan\Model
{
    /**
     * Query my leave requests.
     *
     * @param array $params
     * @param object $login
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable(array $params, $login)
    {
        $where = [];
        $approvalScope = null;

        if (isset($params['status']) && $params['status'] !== '') {
            $where[] = ['LI.status', (int) $params['status']];
        }
        if (!empty($params['leave_id'])) {
            $where[] = ['LI.leave_id', (int) $params['leave_id']];
        }
        if (!empty($params['department'])) {
            $where[] = ['LI.department', $params['department']];
        }

        if ($login && !\Gcms\Api::isAdmin($login)) {
            $approvalSteps = Helper::getApprovalSteps();
            if (!empty($approvalSteps)) {
                $loginDepartment = isset($login->metas['department'][0]) ? trim((string) $login->metas['department'][0]) : '';
                $q = [];
                foreach ($approvalSteps as $approve => $step) {
                    if ((int) $login->status !== (int) $step['status']) {
                        continue;
                    }

                    $approveCondition = $approve === 1
                        ? '(`LI`.`approve` = 1 OR `LI`.`approve` = 0 OR `LI`.`approve` IS NULL)'
                        : '(`LI`.`approve` = '.$approve.')';
                    if ($step['department'] === '') {
                        if ($loginDepartment !== '') {
                            $q[] = '('.$approveCondition.' AND `LI`.`department` = '.Sql::create((int) $loginDepartment).')';
                        }
                    } elseif ($step['department'] === $loginDepartment) {
                        $q[] = '('.$approveCondition.')';
                    }
                }

                if (!empty($q)) {
                    $approvalScope = Sql::create('('.implode(' OR ', $q).')');
                } else {
                    $where[] = ['LI.id', 0];
                }
            }
        }

        $query = static::createQuery()
            ->select(
                'LI.id',
                'LI.member_id',
                'LI.leave_id',
                'LI.reason',
                'LI.detail',
                'LI.communication',
                'LI.department',
                'LI.start_date',
                'LI.start_period',
                'LI.end_date',
                'LI.end_period',
                'LI.days',
                'LI.status',
                'LI.approve',
                'LI.closed',
                'LI.created_at',
                'U.name member_name',
                'L.topic leave_topic',
                'C.topic department_name'
            )
            ->from('leave_items LI')
            ->join('user U', ['U.id', 'LI.member_id'], 'LEFT')
            ->join('leave L', ['L.id', 'LI.leave_id'], 'LEFT')
            ->join('category C', [
                ['C.category_id', 'LI.department'],
                ['C.type', 'department']
            ], 'LEFT')
            ->where($where);

        if ($approvalScope !== null) {
            $query->where($approvalScope);
        }

        if (!empty($params['search'])) {
            $search = '%'.$params['search'].'%';
            $query->where([
                ['U.name', 'LIKE', $search],
                ['L.topic', 'LIKE', $search],
                ['LI.reason', 'LIKE', $search],
                ['LI.detail', 'LIKE', $search],
                ['C.topic', 'LIKE', $search]
            ], 'OR');
        }

        return $query;
    }

    /**
     * Get footer summary for the current filtered dataset.
     *
     * @param array $params
     * @param object $login
     *
     * @return array
     */
    public static function getSummary(array $params, $login): array
    {
        $result = static::createQuery()
            ->selectRaw('COALESCE(SUM(days), 0) AS total_days')
            ->from([self::toDataTable($params, $login)->copy(), 'Q'])
            ->first();

        $totalDays = isset($result->total_days) ? (float) $result->total_days : 0.0;

        return [
            'total_days' => $totalDays,
            'total_days_text' => Helper::formatDays($totalDays)
        ];
    }

    /**
     * Remove pending leave requests.
     *
     * @param array $ids
     *
     * @return int Number of deleted records
     */
    public static function remove(array $ids)
    {
        // Remove attachments
        foreach ($ids as $id) {
            File::removeDirectory(ROOT_PATH.DATA_FOLDER.'eleave/'.$id.'/');
        }

        // Remove records
        \Kotchasan\DB::create()->delete('leave_items', ['id', $ids], 0);

        return true;
    }
}