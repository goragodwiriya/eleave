<?php
/**
 * @filesource modules/eleave/views/statistics.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Statistics;

use Kotchasan\Html;
use Kotchasan\Http\Request;

/**
 * module=eleave-statistics
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * แสดงสรุปรายการลาประจำปีงบประมาณ
     *
     * @param Request $request
     * @param array $params
     *
     * @return string
     */
    public function render(Request $request, $params)
    {
        $years = array(
            $params['year'] => $params['year'] + $params['year_offset']
        );
        for ($y = 2022; $y <= $params['fiscal_year']; $y++) {
            $years[$y] = $y + $params['year_offset'];
        }
        ksort($years);
        // form
        $form = Html::create('form', array(
            'class' => 'table_nav',
            'method' => 'get',
            'action' => 'index.php',
            'ajax' => false,
            'token' => false
        ));
        $div = $form->add('div');
        $fieldset = $div->add('fieldset');
        $fieldset->add('select', array(
            'id' => 'year',
            'label' => '{LNG_Fiscal year}',
            'options' => $years,
            'value' => $params['year']
        ));
        $div->add('fieldset', array(
            'class' => 'go',
            'innerHTML' => '<button type="submit" class="button go">{LNG_Go}</button>'
        ));
        $div->add('hidden', array(
            'id' => 'module',
            'value' => 'eleave-statistics'
        ));
        $div->add('hidden', array(
            'id' => 'id',
            'value' => $params['member_id']
        ));
        $div->add('a', array(
            'class' => 'float_button',
            'href' => 'index.php?module=eleave-leave',
            'title' => '{LNG_Add} {LNG_Request for leave}',
            'innerHTML' => '<span class=icon-new><span>'
        ));
        $content = '<section class="setup_frm padding-left-right-bottom">';
        $content .= $form->render();
        $content .= '<table class="fullwidth horiz-table border">';
        $content .= '<thead><tr><th>{LNG_Leave type}</th><th class=center>{LNG_Number of leave days}</th></tr></thead>';
        $content .= '<tbody>';
        $max_level = 0;
        $items = \Eleave\Statistics\Model::execute($params);
        foreach ($items as $i => $item) {
            $max_level = max($max_level, (float) $item->days);
        }
        $max_level = $max_level == 0 ? 10 : $max_level;
        foreach ($items as $i => $item) {
            $content .= '<tr>';
            $content .= '<th>'.$item->topic.'</th>';
            $num_days = (float) $item->num_days;
            $days = (float) $item->days;
            $lable = $days.' {LNG_days}';
            if ($num_days > 0) {
                $lable .= ' ({LNG_Remain} '.($num_days - $days).' {LNG_days})';
            }
            $content .= '<td class=chart><span class="item bg'.($i % 9).'"><span class="bar" style="width:'.((100 * (float) $item->days) / $max_level).'%;">&nbsp;</span><span class=label>'.$lable.'</span></span></td>';
            $content .= '</tr>';
        }
        $content .= '</tbody>';
        $content .= '</table>';
        $content .= '</section>';
        // คืนค่า HTML
        return $content;
    }
}
