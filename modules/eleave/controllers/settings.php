<?php
/**
 * @filesource modules/eleave/controllers/settings.php
 */

namespace Eleave\Settings;

use Gcms\Api as ApiController;
use Gcms\Config;
use Kotchasan\Http\Request;
use Kotchasan\Http\UploadedFile;
use Kotchasan\Language;
use Kotchasan\Text;

class Controller extends ApiController
{
    /**
     * Get module settings.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function get(Request $request)
    {
        try {
            // Validate request method (GET request doesn't need CSRF token)
            ApiController::validateMethod($request, 'GET');

            // Read user from token (Bearer /X-Access-Token param)
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            // Permission check
            if (!ApiController::hasPermission($login, ['can_manage_eleave', 'can_config'])) {
                return $this->errorResponse('Forbidden', 403);
            }

            // อ่านการตั้งค่าขนาดของไฟลอัปโหลด
            $upload_max = UploadedFile::getUploadSize(true);
            // eleave_upload_size
            $sizes = [];
            foreach ([1, 2, 4, 6, 8, 16, 32, 64, 128, 256, 512, 1024, 2048] as $i) {
                $a = $i * 1048576;
                if ($a <= $upload_max) {
                    $sizes[$a] = ['value' => $a, 'text' => Text::formatFileSize($a)];
                }
            }
            if (!isset($sizes[$upload_max])) {
                $sizes[$upload_max] = ['value' => $upload_max, 'text' => Text::formatFileSize($upload_max)];
            }

            return $this->successResponse([
                'data' => (object) [
                    'eleave_fiscal_year' => self::$cfg->eleave_fiscal_year ?? 1,
                    'eleave_approve_level' => self::$cfg->eleave_approve_level === 1 ? count(self::$cfg->eleave_approve_status) : 0,
                    'eleave_approve_status' => self::$cfg->eleave_approve_status,
                    'eleave_approve_department' => self::$cfg->eleave_approve_department,
                    'eleave_file_types' => self::$cfg->eleave_file_types ?? ['jpg', 'jpeg', 'png', 'pdf'],
                    'eleave_upload_size' => self::$cfg->eleave_upload_size ?? $upload_max,
                    'upload_max_filesize' => Text::formatFileSize($upload_max)
                ],
                'options' => (object) [
                    'fiscal_years' => \Gcms\Controller::arrayToOptions(Language::get('MONTH_LONG')),
                    'status' => \Gcms\Controller::getUserStatusOptions(),
                    'department' => \Gcms\Category::init()->toOptions('department'),
                    'upload_sizes' => $sizes
                ]
            ], 'Leave settings loaded');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Save module settings.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function save(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            ApiController::validateCsrfToken($request);

            // Authentication check (required)
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            // Permission check
            if (!ApiController::canModify($login, ['can_manage_eleave', 'can_config'])) {
                return $this->errorResponse('Permission required', 403);
            }

            $typies = [];
            foreach ($request->post('eleave_file_types', [])->filter('a-zA-Z0-9') as $typ) {
                $ext = strtolower($typ);
                if (preg_match('/^[a-z0-9]{2,4}$/', $ext)) {
                    $typies[$ext] = $ext;
                }
            }

            if (empty($typies)) {
                return $this->formErrorResponse([
                    'eleave_file_types' => 'Please fill in'
                ]);
            }

            // Load config
            $config = Config::load(ROOT_PATH.'settings/config.php');

            $config->eleave_fiscal_year = $request->post('eleave_fiscal_year')->toInt();
            $config->eleave_approve_level = $request->post('eleave_approve_level')->toInt();
            $config->eleave_approve_status = $request->post('eleave_approve_status', [])->toInt();
            $config->eleave_approve_department = $request->post('eleave_approve_department', [])->toInt();
            $config->eleave_upload_size = $request->post('eleave_upload_size')->toInt();
            $config->eleave_file_types = array_values($typies);

            if (Config::save($config, ROOT_PATH.'settings/config.php')) {
                // Log
                \Index\Log\Model::add(0, 'eleave', 'Save', 'Save Leave Settings', $login->id);

                // Reload page
                return $this->redirectResponse('reload', 'Saved successfully', 200, 1000);
            }
        } catch (\Kotchasan\ApiException $e) {
            // Keep original HTTP code (e.g. 403 CSRF, 405 method)
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 400);
        }
        // Error save settings
        return $this->errorResponse('Failed to save settings', 500);
    }
}
