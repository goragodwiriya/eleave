<?php
/**
 * @filesource modules/eleave/models/myleaves.php
 */

namespace Eleave\Myleaves;

use Eleave\Helper\Controller as Helper;

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
        $where = [
            ['LI.member_id', $login->id]
        ];

        if (isset($params['status']) && $params['status'] !== '') {
            $where[] = ['LI.status', (int) $params['status']];
        }
        if (!empty($params['leave_id'])) {
            $where[] = ['LI.leave_id', (int) $params['leave_id']];
        }
        if (!empty($params['from'])) {
            $where[] = ['LI.start_date', '>=', $params['from'].' 00:00:00'];
        }
        if (!empty($params['to'])) {
            $where[] = ['LI.start_date', '<=', $params['to'].' 23:59:59'];
        }

        $query = static::createQuery()
            ->select(
                'LI.id',
                'LI.leave_id',
                'L.topic leave_topic',
                'LI.reason',
                'LI.detail',
                'LI.communication',
                'LI.start_date',
                'LI.start_period',
                'LI.end_date',
                'LI.end_period',
                'LI.days',
                'LI.status',
                'LI.created_at'
            )
            ->from('leave_items LI')
            ->join('leave L', ['L.id', 'LI.leave_id'], 'LEFT')
            ->where($where);

        if (!empty($params['search'])) {
            $search = '%'.$params['search'].'%';
            $query->where([
                ['L.topic', 'LIKE', $search],
                ['LI.reason', 'LIKE', $search],
                ['LI.detail', 'LIKE', $search],
                ['LI.communication', 'LIKE', $search]
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
}