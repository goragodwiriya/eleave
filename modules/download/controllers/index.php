<?php
/**
 * @filesource modules/download/controllers/index.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Download\Index;

use Kotchasan\Mime;

/**
 * แสดงผลไฟล์ดาวน์โหลด
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * ลิสต์รายการไฟล์ คืนค่าเป็น HTML สำหรับแสดงผล
     * ที่เก็บไฟล์ ROOT_PATH.DATA_FOLDER.$module.'/'.$id.'/'
     *
     * @param int|string $id ID ของไฟล์
     * @param string $module ไดเร็คทอรี่เก็บไฟล์ปกติจะเป็นชื่อโมดูล
     * @param array $typies ประเภทของไฟล์ที่ต้องการ
     * @param int $owner_id มากกว่า 0 ตรวจสอบกับคน Login ถ้าตรงกันถึงจะลบได้, 0 ไม่สามารถลบได้
     * @param array $excludes ลิสต์รายการที่ไม่ต้องการแสดง (เฉพาะชื่อไฟล์ ไม่สนใจนามสกุล)
     * @param string $style thumb (default) หรือ list
     *
     * @return string
     */
    public static function init($id, $module, $typies, $owner_id = 0, $excludes = [], $style = 'thumb')
    {
        $files = [];
        \Kotchasan\File::listFiles(ROOT_PATH.DATA_FOLDER.$module.'/'.$id.'/', $files);
        sort($files);
        $elem = uniqid();
        $content = '<div class="file_'.$style.' clear" id='.$elem.'>';
        foreach ($files as $i => $item) {
            if (preg_match('/.*\/([0-9]+)(\/([a-z]+))?\/([a-z0-9]+)\.('.implode('|', $typies).')$/', $item, $match)) {
                if (empty($excludes) || !in_array($match[4], $excludes)) {
                    // รูปภาพ
                    $isImage = in_array($match[5], array('bmp', 'png', 'gif', 'jpg', 'jpeg', 'jfif', 'tiff', 'webp'));
                    $dtas = array(
                        'file' => $item,
                        'mime' => $match[5] == 'pdf' || $isImage ? Mime::get($match[5]) : 'application/octet-stream',
                        'owner_id' => $owner_id
                    );
                    $jwt = \Kotchasan\Password::encode(json_encode($dtas, JSON_UNESCAPED_UNICODE), self::$cfg->password_key);
                    $content .= '<div id="item_'.$jwt.'">';
                    if ($style == 'thumb') {
                        if ($isImage) {
                            $content .= '<a class="preview" href="'.WEB_URL.'modules/download/download.php?id='.$jwt.'" title="{LNG_Download}" target="preview" style="background-image:url('.str_replace(ROOT_PATH, WEB_URL, $item).')"></a>';
                        } else {
                            $content .= '<a class="preview file-thumb" href="'.WEB_URL.'modules/download/download.php?id='.$jwt.'" title="{LNG_Download}" target="preview"><span>'.strtoupper($match[5]).'</span></a>';
                        }
                    } else {
                        $img = WEB_URL.'skin/ext/'.(is_file(ROOT_PATH.'skin/ext/'.$match[5].'.png') ? $match[5] : 'file').'.png';
                        $content .= '<a href="'.WEB_URL.'modules/download/download.php?id='.$jwt.'" target="download" title="{LNG_Download}">';
                        $content .= '<img src="'.$img.'" alt="'.$match[5].'" alt="{LNG_Download}">';
                        $content .= '<span>{LNG_Download}</span>';
                        $content .= '</a>';
                    }
                    if ($owner_id > 0) {
                        $content .= '<a class="icon-delete" id="delete_'.$jwt.'" title="{LNG_Delete}"></a>';
                    }
                    $content .= '</div>';
                }
            }
        }
        $content .= '</div><script>initDownload("'.$elem.'")</script>';
        return $content;
    }
}
