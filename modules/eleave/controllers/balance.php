<?php
/**
 * @filesource modules/eleave/controllers/balance.php
 */

namespace Eleave\Balance;

use Eleave\Request\Model as RequestModel;
use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

class Controller extends ApiController
{
    /**
     * Get leave balance report data.
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

            $year = $request->get('year', date('Y'))->toInt();
            if ($year <= 0) {
                $year = (int) date('Y');
            }

            $canViewAll = RequestModel::canApproveRequests($login);
            $filters = [
                'year' => $year,
                'leave_id' => $request->get('leave_id')->toInt(),
                'member_id' => $request->get('member_id')->toInt(),
                'department' => $request->get('department')->toString()
            ];
            if (!$canViewAll) {
                $filters['member_id'] = (int) $login->id;
                $filters['department'] = '';
            }

            $report = Model::getReport($login, $filters);

            return $this->successResponse([
                'year' => (string) $filters['year'],
                'leave_id' => $filters['leave_id'] > 0 ? (string) $filters['leave_id'] : '',
                'member_id' => $canViewAll && $filters['member_id'] > 0 ? (string) $filters['member_id'] : '',
                'department' => $canViewAll ? (string) $filters['department'] : '',
                'can_view_all' => $canViewAll,
                'options' => [
                    'year' => Model::getYearOptions($filters['year']),
                    'leave_id' => array_merge([
                        ['value' => '', 'text' => 'All leave types']
                    ], RequestModel::getLeaveTypeOptions(false)),
                    'member_id' => $canViewAll ? array_merge([
                        ['value' => '', 'text' => 'All employees']
                    ], Model::getMemberOptions()) : [],
                    'department' => $canViewAll ? array_merge([
                        ['value' => '', 'text' => 'All departments']
                    ], RequestModel::getDepartmentOptions()) : []
                ],
                'summary' => $report['summary'],
                'scope_note' => $report['scope_note'],
                'rows' => $report['rows']
            ], 'Leave balance report retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}