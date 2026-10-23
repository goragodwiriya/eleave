<?php
/**
 * @filesource modules/eleave/models/leavetype.php
 */

namespace Eleave\LeaveType;

class Model extends \Kotchasan\Model
{
    /**
     * Get leave type by ID
     * $id = 0 returns empty leave type object for new leave type form
     *
     * @param (int) $id
     *
     * @return object|null
     */
    public static function get($id)
    {
        if ($id === 0) {
            return (object) [
                'id' => 0,
                'is_active' => 1,
                'num_days' => 0
            ];
        }

        return static::createQuery()
            ->select()
            ->from('leave')
            ->where(['id', $id])
            ->first();
    }

    /**
     * Save leave type
     *
     * @param int $id 0 = insert, > 0 = update
     * @param array $save
     *
     * @return mixed
     */
    public static function save($id, $save)
    {
        if ($id === 0) {
            return \Kotchasan\DB::create()->insert('leave', $save);
        } else {
            return \Kotchasan\DB::create()->update('leave', ['id', $id], $save);
        }
    }
}