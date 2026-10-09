<?php
/**
 * @filesource modules/eleave/controllers/approvals.php
 */

namespace Eleave\Approvals;

use Eleave\Helper\Controller as Helper;
use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

class Controller extends \Gcms\Table
{
    /**
     * Allowed sort columns.
     *
     * @var array
     */
    protected $allowedSortColumns = ['id', 'member_name', 'leave_topic', 'department_name', 'start_date', 'end_date', 'days', 'status', 'created_at'];

    /**
     * Ensure approver access.
     *
     * @param Request $request
     * @param object $login
     *
     * @return true|\Kotchasan\Http\Response
     */
    protected function checkAuthorization(Request $request, $login)
    {
        if (!Helper::canApproveRequests($login)) {
            return $this->errorResponse('Forbidden', 403);
        }

        return true;
    }

    /**
     * Custom table parameters.
     *
     * @param Request $request
     * @param object $login
     *
     * @return array
     */
    protected function getCustomParams(Request $request, $login): array
    {
        return [
            'status' => $request->get('status')->filter('0-9'),
            'leave_id' => $request->get('leave_id')->filter('0-9'),
            'from' => $request->get('from')->date(),
            'to' => $request->get('to')->date(),
            'department' => $request->get('department')->topic()
        ];
    }

    /**
     * Query
     *
     * @param array $params
     * @param object|null $login
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    protected function toDataTable($params, $login = null)
    {
        return Model::toDataTable($params, $login);
    }

    /**
     * Filters
     *
     * @param array $params
     * @param object|null $login
     *
     * @return array
     */
    protected function getFilters($params, $login = null)
    {
        return [
            'status' => \Eleave\Helper\Controller::getStatusOptions(),
            'leave_id' => \Eleave\Helper\Controller::getLeaveTypeOptions(false),
            'department' => \Gcms\Category::init()->toOptions('department')
        ];
    }

    /**
     * Format rows for display.
     *
     * @param array $datas
     * @param object|null $login
     *
     * @return array
     */
    protected function formatDatas(array $datas, $login = null): array
    {
        $result = [];
        foreach ($datas as $row) {
            $row->status_text = Helper::showStatus($row->status);
            $row->leave_text = Helper::formatLeaveDate($row);
            $row->days_text = Helper::formatDays((float) $row->days);
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Runtime table options.
     *
     * @param array $params
     * @param object|null $login
     *
     * @return array
     */
    protected function getOptions(array $params, $login)
    {
        $isSuperAdmin = ApiController::isSuperAdmin($login);

        return [
            '_table' => [
                'showCheckbox' => $isSuperAdmin,
                'actions' => $isSuperAdmin ? [
                    'delete' => 'Delete'
                ] : [],
                'actionButton' => $isSuperAdmin ? 'Process|btn-success' : null
            ]
        ];
    }

    /**
     * Review action.
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleReviewAction(Request $request, $login)
    {
        $id = $request->post('id')->toInt();
        if ($id <= 0 || !Helper::canApproveRequests($login)) {
            return $this->errorResponse('Permission required', 403);
        }

        return $this->redirectResponse('/leave-review?id='.$id);
    }

    /**
     * Review statistics.
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleStatisticsAction(Request $request, $login)
    {
        $id = $request->post('id')->toInt();
        $memberId = $request->post('member_id')->toInt();
        if ($memberId <= 0 && $id > 0) {
            $record = \Kotchasan\Model::createQuery()
                ->select('member_id')
                ->from('leave_items')
                ->where(['id', $id])
                ->first();
            $memberId = $record ? (int) $record->member_id : 0;
        }

        if ($memberId <= 0 || !Helper::canApproveRequests($login)) {
            return $this->errorResponse('Permission required', 403);
        }

        return $this->redirectResponse('/leave-user-statistics?member_id='.$memberId);
    }

    /**
     * Delete pending leave request(s).
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleDeleteAction(Request $request, $login)
    {
        if (!ApiController::isSuperAdmin($login)) {
            return $this->errorResponse('Permission required', 403);
        }

        $ids = $request->request('ids', [])->toInt();
        if (empty($ids)) {
            return $this->errorResponse('No data to delete', 400);
        }

        Model::remove($ids);

        // Log deletion
        \Index\Log\Model::add(0, 'eleave', 'Delete', 'Deleted leave request ID(s) : '.implode(', ', $ids), $login->id);

        // Return success response
        return $this->redirectResponse('reload', 'Deleted leave request(s) successfully', 200, 0, 'table');
    }
}
