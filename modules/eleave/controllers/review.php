<?php
/**
 * @filesource modules/eleave/controllers/review.php
 */

namespace Eleave\Review;

use Eleave\Helper\Controller as Helper;
use Gcms\Api as ApiController;
use Kotchasan\Date;
use Kotchasan\Http\Request;
use Kotchasan\Language;

class Controller extends ApiController
{
    /**
     * Get approval review data.
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
            if (!Helper::canApproveRequests($login)) {
                return $this->errorResponse('Forbidden', 403);
            }

            $row = Model::get($request->get('id')->toInt());
            if ($row === null) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $balanceSummary = Model::getBalanceSummary($row);
            $balanceErrors = Model::getBalanceErrors($balanceSummary);
            $canProcess = Model::canProcess($row);

            return $this->successResponse([
                'id' => (int) $row->id,
                'member_name' => $row->member_name,
                'member_username' => $row->member_username,
                'department_name' => $row->department_name,
                'leave_topic' => $row->leave_topic,
                'leave_detail' => $row->leave_detail,
                'leave_num_days' => Helper::formatDays($row->leave_num_days),
                'reason' => $row->reason,
                'detail' => $row->detail,
                'communication' => $row->communication,
                'start_date' => Date::format($row->start_date, 'd M Y').' '.Language::get('LEAVE_PERIOD', '', $row->start_period),
                'end_date' => Date::format($row->end_date, 'd M Y').' '.Language::get('LEAVE_PERIOD', '', $row->end_period),
                'days' => Helper::formatDays($row->days),
                'created_at' => Date::format($row->created_at, 'd M Y H:i'),
                'status_text' => Helper::showStatus($row->status, false),
                'canProcess' => $canProcess,
                'canApproveAction' => $canProcess && Helper::canApproveStep($login, $row) && empty($balanceErrors),
                'attachments' => \Download\Index\Controller::getAttachments($row->id, 'eleave', self::$cfg->eleave_file_types),
                'balance_summary' => $balanceSummary,
                'balance_error' => $balanceErrors[0] ?? '',
                'approval_reason' => ''
            ], 'Leave review retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Approve a leave request.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function approve(Request $request)
    {
        return $this->processDecision($request, Helper::STATUS_APPROVED, 'Approved leave request');
    }

    /**
     * Reject a leave request.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function reject(Request $request)
    {
        return $this->processDecision($request, Helper::STATUS_REJECTED, 'Rejected leave request', true);
    }

    /**
     * Return a request for correction.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function returnRequest(Request $request)
    {
        return $this->processDecision($request, Helper::STATUS_RETURNED_FOR_EDIT, 'Returned leave request for correction', true);
    }

    /**
     * Shared decision handler.
     *
     * @param Request $request
     * @param int $status
     * @param string $logTopic
     * @param bool $requireReason
     *
     * @return \Kotchasan\Http\Response
     */
    protected function processDecision(Request $request, int $status, string $logTopic, bool $requireReason = false)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }
            if (!Helper::canApproveRequests($login)) {
                return $this->errorResponse('Permission required', 403);
            }

            $id = $request->post('id')->toInt();
            $approvalReason = trim($request->post('approval_reason')->textarea());
            $row = Model::get($id);
            if ($row === null) {
                return $this->errorResponse('No data available', 404);
            }
            if (!Model::canProcess($row)) {
                return $this->errorResponse('This request has already been processed', 400);
            }
            if (!Helper::canApproveStep($login, $row)) {
                return $this->errorResponse('You are not allowed to approve this step', 403);
            }

            if ($requireReason && $approvalReason === '') {
                return $this->errorResponse('Rejection reason is required', 400);
            }

            $currentApprove = max(1, (int) ($row->approve ?? 0));
            $closedLevel = max(1, (int) ($row->closed ?? 0), Helper::getApprovalLevelCount());
            $finalStatus = $status;
            $approve = $currentApprove;

            if ($status === Helper::STATUS_APPROVED) {
                $balanceSummary = Model::getBalanceSummary($row);
                $balanceErrors = Model::getBalanceErrors($balanceSummary);
                if (!empty($balanceErrors)) {
                    return $this->errorResponse($balanceErrors[0], 400);
                }

                if ($currentApprove >= $closedLevel || \Gcms\Api::isAdmin($login)) {
                    // Final approval
                    $approve = $closedLevel;
                } else {
                    // Next step approval
                    $finalStatus = Helper::STATUS_PENDING_REVIEW;
                    $nextApprove = Helper::getNextApprovalStep($currentApprove);
                    $approve = $nextApprove > 0 ? $nextApprove : $closedLevel;
                }
            }

            Model::updateStatus($id, $finalStatus, $approve, $closedLevel);

            \Index\Log\Model::add($id, 'eleave', 'Status', $logTopic.': '.$id, $login->id, $approvalReason, [
                'status' => $finalStatus,
                'approve' => $approve
            ]);

            $message = \Eleave\Email\Controller::sendByRequestId($id, $approvalReason);

            return $this->redirectResponse('/leave-approvals', $message);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
