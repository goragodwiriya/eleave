<?php
/**
 * @filesource modules/eleave/views/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Settings;

use Kotchasan\Html;
use Kotchasan\Http\UploadedFile;
use Kotchasan\Language;
use Kotchasan\Text;

/**
 * module=eleave-settings
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ฟอร์มตั้งค่า
     *
     * @return string
     */
    public function render()
    {
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/eleave/model/settings/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-config',
            'title' => '{LNG_Module settings}'
        ));
        // eleave_fiscal_year
        $fieldset->add('select', array(
            'id' => 'eleave_fiscal_year',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'item',
            'label' => '{LNG_Fiscal year}',
            'comment' => '{LNG_Set the start date of the fiscal year, the 1st day of the selected month}',
            'options' => Language::get('MONTH_LONG'),
            'value' => self::$cfg->eleave_fiscal_year
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-upload',
            'title' => '{LNG_Upload}'
        ));
        // eleave_file_typies
        $fieldset->add('text', array(
            'id' => 'eleave_file_typies',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Type of file uploads}',
            'comment' => '{LNG_Specify the file extension that allows uploading. English lowercase letters and numbers 2-4 characters to separate each type with a comma (,) and without spaces. eg zip,rar,doc,docx}',
            'value' => isset(self::$cfg->eleave_file_typies) ? implode(',', self::$cfg->eleave_file_typies) : 'doc,ppt,pptx,docx,rar,zip,jpg,pdf'
        ));
        // อ่านการตั้งค่าขนาดของไฟลอัปโหลด
        $upload_max = UploadedFile::getUploadSize(true);
        // eleave_upload_size
        $sizes = [];
        foreach (array(1, 2, 4, 6, 8, 16, 32, 64, 128, 256, 512, 1024, 2048) as $i) {
            $a = $i * 1048576;
            if ($a <= $upload_max) {
                $sizes[$a] = Text::formatFileSize($a);
            }
        }
        if (!isset($sizes[$upload_max])) {
            $sizes[$upload_max] = Text::formatFileSize($upload_max);
        }
        $fieldset->add('select', array(
            'id' => 'eleave_upload_size',
            'labelClass' => 'g-input icon-upload',
            'itemClass' => 'item',
            'label' => '{LNG_Size of the file upload}',
            'comment' => '{LNG_The size of the files can be uploaded. (Should not exceed the value of the Server :upload_max_filesize.)}',
            'options' => $sizes,
            'value' => isset(self::$cfg->eleave_upload_size) ? self::$cfg->eleave_upload_size : ':upload_max_filesize'
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        \Gcms\Controller::$view->setContentsAfter(array(
            '/:upload_max_filesize/' => Text::formatFileSize($upload_max)
        ));
        // คืนค่า HTML
        return $form->render();
    }
}
