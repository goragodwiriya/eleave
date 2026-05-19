<?php
/**
 * @filesource modules/eleave/models/leavetypes.php
 */

namespace Eleave\LeaveTypes;

class Model extends \Kotchasan\Model
{
    /**
     * Query data to send to DataTable
     *
     * @param array $params
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable($params)
    {
        return static::createQuery()
            ->select('id', 'topic', 'num_days', 'is_active')
            ->from('leave');
    }

    /**
     * Delete leave type
     *
     * @param int|array $ids
     *
     * @return int
     */
    public static function remove($ids)
    {
        $db = \Kotchasan\DB::create();

        return $db->delete('leave', ['id', $ids], 0);
    }
}