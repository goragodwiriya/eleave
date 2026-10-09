<?php
/**
 * @filesource modules/eleave/controllers/myleaves.php
 */

namespace Eleave\Myleaves;

use Eleave\Helper\Controller as Helper;
use Eleave\Request\Model as RequestModel;
use Kotchasan\Http\Request;

class Controller extends \Gcms\Table
{
    /**
     * Allowed sort columns.
     *
     * @var array
     */
    protected $allowedSortColumns = ['id', 'leave_topic', 'reason', 'start_date', 'end_date', 'days', 'status', 'created_at'];

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
            'to' => $request->get('to')->date()
        ];
    }

    /**
     * Query
     *
     * @param array $params
     * @param object $login
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
            'leave_id' => \Eleave\Helper\Controller::getLeaveTypeOptions(false)
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
     * Edit action.
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleEditAction(Request $request, $login)
    {
        $id = $request->post('id')->toInt();
        $record = RequestModel::getRecord($login->id, $id);
        if ($record === null) {
            return $this->errorResponse('No data available', 404);
        }
        if (!RequestModel::canEdit($record)) {
            return $this->errorResponse('This leave request can no longer be edited', 403);
        }

        return $this->redirectResponse('/leave-request?id='.$id);
    }

    /**
     * View action.
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleViewAction(Request $request, $login)
    {
        $id = $request->post('id')->toInt();
        $record = RequestModel::get($login, $id);
        if ($record === null) {
            return $this->errorResponse('No data available', 404);
        }

        $leaveType = Helper::getLeaveTypeById((int) $record->leave_id);
        $data = (array) $record;
        $data['name'] = $record->member_name ?? $login->name;
        $data['leave_type'] = $leaveType ? $leaveType->topic : '';

        return $this->successResponse([
            'data' => $data,
            'actions' => [
                \Eleave\View\View::buildModalAction($data)
            ]
        ], 'Leave request details retrieved');
    }

    /**
     * Cancel action.
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleCancelAction(Request $request, $login)
    {
        $id = $request->post('id')->toInt();
        $record = RequestModel::getRecord($login->id, $id);
        if ($record === null) {
            return $this->errorResponse('No data available', 404);
        }
        if (!RequestModel::canCancel($record)) {
            return $this->errorResponse('This request can no longer be cancelled', 400);
        }

        RequestModel::updateRequestStatus($id, [
            'status' => 3,
            'approve' => 0,
            'closed' => 1
        ]);
        \Index\Log\Model::add($id, 'eleave', 'Status', 'Cancelled leave request: '.$id, $login->id);

        $message = \Eleave\Email\Controller::sendByRequestId($id);

        return $this->redirectResponse('reload', $message, 200, 0, 'table');
    }
}
