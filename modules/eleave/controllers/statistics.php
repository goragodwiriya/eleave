<?php
/**
 * @filesource modules/eleave/controllers/statistics.php
 */

namespace Eleave\Statistics;

use Eleave\Fiscalyear\Controller as FiscalyearController;
use Eleave\Helper\Controller as Helper;
use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

class Controller extends ApiController
{
    /**
     * Resolve the target member for statistics requests.
     * Admin and approvers may inspect another member via member_id.
     *
     * @param Request $request
     * @param object  $login
     *
     * @return int
     */
    protected function resolveRequestedMemberId(Request $request, $login): int
    {
        if (Helper::canApproveRequests($login) || ApiController::hasPermission($login, ['can_manage_eleave'])) {
            $requestedMemberId = $request->get('member_id', $login->id)->toInt();

            return $requestedMemberId > 0 ? $requestedMemberId : (int) $login->id;
        }

        return (int) $login->id;
    }

    /**
     * Get personal leave statistics for ApiComponent.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function index(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $currentFiscalYear = (int) FiscalyearController::get();
            $fiscalYear = $request->get('year', $currentFiscalYear)->toInt();
            $memberId = $this->resolveRequestedMemberId($request, $login);

            $user = \Index\Profile\Model::view($memberId);
            if (!$user) {
                return $this->errorResponse('User not found', 404);
            }

            return $this->successResponse(
                Model::getStatistics($user, $fiscalYear),
                'Leave statistics retrieved'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Re-render the balance bars for requestApi-driven year changes.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function render(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $currentFiscalYear = (int) FiscalyearController::get();
            $fiscalYear = $request->get('year', $currentFiscalYear)->toInt();
            $memberId = $this->resolveRequestedMemberId($request, $login);

            $user = \Index\Profile\Model::view($memberId);
            if (!$user) {
                return $this->errorResponse('User not found', 404);
            }

            return $this->successResponse(
                Model::getStatistics($user, $fiscalYear),
                'Leave statistics retrieved'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
