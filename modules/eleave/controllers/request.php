<?php
/**
 * @filesource modules/eleave/controllers/request.php
 */

namespace Eleave\Request;

use Eleave\Helper\Controller as Helper;
use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Language;

class Controller extends ApiController
{
    /**
     * Get request form data.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function get(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $id = $request->get('id', 0)->toInt();
            $data = Model::get($login, $id);
            if ($id > 0 && $data === null) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $data->options = [
                'leave_id' => Helper::getLeaveTypeOptions(true, !empty($data->leave_id) ? (int) $data->leave_id : null),
                'start_period' => Helper::getPeriodOptions(),
                'end_period' => Helper::getPeriodOptions()
            ];

            return $this->successResponse($data, 'Leave request retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Get live policy and balance preview.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function policy(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            // Authentication check (required)
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }
            // รับค่า input สำหรับการคำนวณนโยบายและยอดคงเหลือ
            $leaveId = $request->get('leave_id')->toInt();
            $save = [
                'start_date' => $request->get('start_date')->date(),
                'end_date' => $request->get('end_date')->date(),
                'start_period' => $request->get('start_period')->toInt(),
                'end_period' => $request->get('end_period')->toInt()
            ];

            // คำนวณจำนวนวันลาตามวันที่เริ่มต้นและวันที่สิ้นสุด และตรวจสอบนโยบายที่เกี่ยวข้อง
            $error = Helper::calculateLeaveDays($save);

            // ดึงข้อมูลประเภทลาที่เลือกมาแสดงในส่วนของรายละเอียดประเภทลา
            $leaveType = Helper::getLeaveTypeById($leaveId);

            // สร้าง payload สำหรับการแสดงผลแบบ declarative โดยแยกส่วนของข้อมูลนโยบายและสรุปยอดคงเหลือออกจากส่วนของข้อความแจ้งเตือนนโยบาย
            $result = [
                'preview' => [
                    'leave_type_detail' => $this->buildLeaveTypeDetail($leaveType),
                    'days' => $save['days'] ?? '',
                    'days_note' => $error
                ]
            ];
            return $this->successResponse($result, 'Leave policy retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Save a leave request.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function save(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            $id = $request->post('id')->toInt();
            $record = Model::get($login, $id);
            if ($record === null) {
                return $this->errorResponse('No data available', 404);
            }
            if (!Model::canEdit($record)) {
                return $this->errorResponse('This leave request can no longer be edited', 403);
            }

            $save = [
                'leave_id' => $request->post('leave_id')->toInt(),
                'detail' => $request->post('detail')->textarea(),
                'communication' => $request->post('communication')->textarea(),
                'start_date' => $request->post('start_date')->date(),
                'end_date' => $request->post('end_date')->date(),
                'start_period' => $request->post('start_period')->toInt(),
                'end_period' => $request->post('end_period')->toInt()
            ];

            $errors = [];

            if ($save['leave_id'] <= 0) {
                $errors['leave_id'] = 'Please select';
            }

            if ($save['detail'] === '') {
                $errors['detail'] = 'Please fill in';
            }

            $error = Helper::calculateLeaveDays($save);
            if ($error !== '') {
                $errors['days_preview'] = $error;
            }

            if (empty($errors)) {
                // Database
                $db = \Kotchasan\DB::create();
                if ($record->id === 0) {
                    $save['id'] = $db->nextId('leave_items');
                } else {
                    $save['id'] = $record->id;
                }

                // อัปโหลดไฟล์แนบ
                \Download\Upload\Model::execute($errors, $request, $save['id'], 'eleave', self::$cfg->eleave_file_types, self::$cfg->eleave_upload_size);
            }

            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 400);
            }

            $workflowLevelCount = Helper::getApprovalLevelCount();
            $closedLevel = $workflowLevelCount > 0 ? $workflowLevelCount : 1;

            if ($record->id === 0) {
                // ใหม่
                $save['member_id'] = $login->id;
                $save['department'] = isset($login->metas['department'][0]) ? trim((string) $login->metas['department'][0]) : '';
                $save['created_at'] = date('Y-m-d H:i:s');
                $save['status'] = $workflowLevelCount === 0 ? 1 : 0;
                $save['approve'] = 1;
                $save['closed'] = $closedLevel;
                $db->insert('leave_items', $save);
            } else {
                // แก้ไข
                $save['approve'] = max(1, (int) ($record->approve ?? 0));
                $save['closed'] = max(1, (int) ($record->closed ?? 0), $closedLevel);
                if ((int) $record->status === 4) {
                    $save['status'] = 0;
                } else {
                    $save['status'] = (int) $record->status;
                }
                $db->update('leave_items', ['id', $save['id']], $save);
                $save['member_id'] = $record->member_id;
            }

            // log
            \Index\Log\Model::add($save['id'], 'eleave', 'Status', Language::get('LEAVE_STATUS', '', $save['status']).' ID : '.$save['id'], $login->id);

            if ($record->id === 0 || $save['status'] != $record->status) {
                $message = \Eleave\Email\Controller::sendByRequestId((int) $save['id']);
            } else {
                // ไม่ต้องส่งอีเมล
                $message = 'Saved successfully';
            }

            return $this->redirectResponse('/my-leaves', $message);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Remove an existing attachment.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function removeAttachment(Request $request)
    {
        try {
            // Validate that the request method is POST and check CSRF token
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            // Authentication check (required)
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }

            // Gets a JSON value containing file information and leave request ID.
            $json = json_decode($request->post('id')->toString());
            if (!$json || file_exists(ROOT_PATH.DATA_FOLDER.'eleave/'.$json->id.'/'.$json->file) === false) {
                return $this->errorResponse('No data available', 404);
            }

            // get the leave request record to check permissions
            $leave = Model::getRecord($login->id, $json->id);
            if (!$leave || !Model::canEdit($leave)) {
                return $this->errorResponse('This leave request can no longer be edited', 403);
            }

            @unlink(ROOT_PATH.DATA_FOLDER.'eleave/'.$json->id.'/'.$json->file);

            \Index\Log\Model::add((int) $json->id, 'eleave', 'Delete', 'Removed leave attachment: '.$json->file, $login->id);

            return $this->successResponse([], 'Attachment removed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Create display text for the selected leave type.
     *
     * @param object|null $leaveType
     *
     * @return string
     */
    protected function buildLeaveTypeDetail($leaveType): string
    {
        if (!$leaveType) {
            return '';
        }

        $parts = [(string) $leaveType->topic];
        if ((float) $leaveType->num_days > 0) {
            $parts[] = Helper::formatDays((float) $leaveType->num_days).' {LNG_days}/{LNG_year}';
        }
        if (!empty($leaveType->detail)) {
            $parts[] = trim((string) $leaveType->detail);
        }

        return implode(' • ', array_values(array_filter($parts, static function ($item) {
            return $item !== '';
        })));
    }
}