<?php
/**
 * @filesource modules/eleave/controllers/leavetype.php
 */

namespace Eleave\LeaveType;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

class Controller extends ApiController
{
    /**
     * GET /api/eleave/leavetype/get
     * Get leave type details by ID.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function get(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            // Authentication check (required)
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $id = $request->get('id')->toInt();
            $data = Model::get($id);
            if (!$data) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $response = [
                'data' => $data
            ];

            $response['actions'] = [
                [
                    'type' => 'modal',
                    'action' => 'show',
                    'template' => '/eleave/leave-type.html',
                    'title' => ($data->id > 0 ? '{LNG_Edit} {LNG_Leave Type}' : '{LNG_Create} {LNG_Leave Type}')
                ]
            ];

            return $this->successResponse($response, 'Leave type details retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST /api/eleave/leavetype/save
     * Save leave type details.
     *
     * @param Request $request
     *
     * @return Response
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
            if (!ApiController::canModify($login, ['can_manage_leave', 'can_config'])) {
                return $this->errorResponse('Permission required', 403);
            }

            $leaveType = Model::get($request->post('id', 0)->toInt());
            if ($leaveType === null) {
                return $this->errorResponse('No data available', 404);
            }

            $save = $this->parseInput($request);

            $errors = $this->validateFields($save);
            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 400);
            }

            // Save data
            $id = Model::save($leaveType->id, $save);

            \Index\Log\Model::add($id, 'eleave', 'Save', 'Saved leave type: '.$save['topic'], $login->id);

            return $this->successResponse([
                'actions' => [
                    [
                        'type' => 'notification',
                        'level' => 'success',
                        'message' => 'Saved successfully'
                    ],
                    [
                        'type' => 'redirect',
                        'url' => 'reload',
                        'target' => 'table'
                    ],
                    [
                        'type' => 'modal',
                        'action' => 'close'
                    ]
                ]
            ], 'Saved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Parse leave type input from request.
     *
     * @param Request $request
     *
     * @return array<string,mixed>
     */
    private function parseInput(Request $request): array
    {
        return [
            'topic' => $request->post('topic')->topic(),
            'detail' => $request->post('detail')->textarea(),
            'num_days' => $request->post('num_days')->toInt(),
            'is_active' => $request->post('is_active')->toBoolean()
        ];
    }

    /**
     * Validate leave type fields.
     *
     * @param array<string,mixed> $save
     */
    private function validateFields(array $save): array
    {
        $errors = [];

        if ($save['topic'] === '') {
            $errors['topic'] = 'Please fill in';
        }

        return $errors;
    }
}