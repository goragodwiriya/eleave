<?php
/**
 * @filesource modules/eleave/views/view.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\View;

use Kotchasan\Language;

/**
 * Show document details (modal)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Eleave\Helper\Controller
{
    /**
     * Render booking details for modal/email style output.
     *
     * @param array $index
     * @param bool $email
     *
     * @return string
     */
    public static function render($index, $email = false)
    {
        $content = [];
        if ($email) {
            $content[] = '<header>';
            $content[] = '<h4>{LNG_Details of} {LNG_Leave Request}</h4>';
            $content[] = '</header>';
        }
        $content[] = '<table class="fullwidth">';
        $content[] = '<tr><td class="item"><span class="icon-customer">{LNG_Name}</span></td><td class="item"> : </td><td class="item">'.$index['name'].'</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-verfied">{LNG_Leave type}</span></td><td class="item"> : </td><td class="item">'.$index['leave_type'].'</td></tr>';
        $category = \Gcms\Category::init();
        $content[] = '<tr><td class="item"><span class="icon-group">{LNG_Department}</span></td><td class="item"> : </td><td class="item">'.$category->get('department', $index['department']).'</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-file">{LNG_Detail}/{LNG_Reasons for leave}</span></td><td class="item"> : </td><td class="item">'.nl2br($index['detail']).'</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-calendar">{LNG_Leave dates}</span></td><td class="item"> : </td><td class="item">'.self::formatLeaveDate($index).'</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-event">{LNG_Number of leave days}</span></td><td class="item"> : </td><td class="item">'.$index['days'].' {LNG_days}</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-file">{LNG_Contact During Leave}</span></td><td class="item"> : </td><td class="item">'.nl2br($index['communication']).'</td></tr>';
        $content[] = '<tr><td class="item"><span class="icon-star0">{LNG_Status}</span></td><td class="item"> : </td><td class="item">'.self::showStatus($index['status'], !$email).'</td></tr>';
        if (!empty($index['reason'])) {
            $content[] = '<tr><td class="item"><span class="icon-comments">{LNG_Reason}</span></td><td class="item"> : </td><td class="item">'.$index['reason'].'</td></tr>';
        }
        if ($email) {
            $content[] = '<tr><td class="item">Url</td><td class="item"> : </td><td class="item"><a href="'.WEB_URL.'">'.WEB_URL.'</a></td></tr>';
        } else {
            $content[] = '<tr><td class="item"><span class="icon-download">{LNG_Attachments}</span></td><td class="item"> : </td><td class="item">'.\Download\Index\Controller::init($index['id'], 'eleave', self::$cfg->eleave_file_types, 0, [], 'list').'</td></tr>';
        }
        $content[] = '</table>';
        // Restore HTML
        return implode("\n", $content);
    }

    /**
     * Build the shared modal action payload for booking details.
     *
     * @param array $index
     *
     * @return array
     */
    public static function buildModalAction(array $index): array
    {
        return [
            'type' => 'modal',
            'action' => 'show',
            'html' => Language::trans(static::render($index)),
            'title' => '{LNG_Details of} {LNG_Leave Request}',
            'titleClass' => 'icon-calendar'
        ];
    }
}
