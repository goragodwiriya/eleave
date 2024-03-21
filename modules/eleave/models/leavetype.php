<?php
/**
 * @filesource eleave/models/leavetype.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Leavetype;

/**
 * คลาสสำหรับอ่านประเภทการลา
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model
{
    /**
     * @var array
     */
    private $datas = [];
    /**
     * @var array
     */
    private $num_days = [];

    /**
     * อ่านรายชื่อการลา
     *
     * @return static
     */
    public static function init()
    {
        $obj = new static;
        // Query
        $query = \Kotchasan\Model::createQuery()
            ->select('id', 'topic', 'num_days')
            ->from('leave')
            ->where(array('published', 1))
            ->order('topic')
            ->cacheOn();
        foreach ($query->execute() as $item) {
            $obj->datas[$item->id] = $item->topic;
            $obj->num_days[$item->id] = $item->num_days;
        }
        return $obj;
    }

    /**
     * ลิสต์รายชื่อการลา
     * สำหรับใส่ลงใน select
     *
     * @return array
     */
    public function toSelect()
    {
        return empty($this->datas) ? [] : $this->datas;
    }

    /**
     * อ่านรายชื่อการลาจาก $id
     * ไม่พบ คืนค่าว่าง
     *
     * @param int $id
     *
     * @return string
     */
    public function get($id)
    {
        return empty($this->datas[$id]) ? '' : $this->datas[$id];
    }

    /**
     * อ่านค่าจำนวนวันลาจาก $id
     * ไม่พบ คืนค่า null
     *
     * @param int $id
     *
     * @return int|null
     */
    public function numDays($id)
    {
        return isset($this->num_days[$id]) ? $this->num_days[$id] : null;
    }
}
