<?php
/**
 * @filesource modules/eleave/controllers/leavetypes.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Eleave\Leavetypes;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Http\Response;

/**
 * API Leave types Controller
 *
 * Handles leave types management endpoints
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Table
{
    /**
     * Allowed sort columns
     *
     * @var array
     */
    protected $allowedSortColumns = [
        'id'
    ];

    /**
     * Check authorization for user management
     * Only admins can access, demo mode is blocked
     *
     * @param Request $request
     * @param object  $login
     *
     * @return mixed
     */
    protected function checkAuthorization(Request $request, $login)
    {
        if (!ApiController::hasPermission($login, ['can_manage_eleave', 'can_config'])) {
            return $this->errorResponse('Permission required', 403);
        }

        return true;
    }

    /**
     * Query data to send to DataTable
     *
     * @param array $params
     * @param object $login
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    protected function toDataTable($params, $login = null)
    {
        return Model::toDataTable($params);
    }

    /**
     * Handle edit action (redirect to leave form)
     *
     * @param Request $request
     * @param object $login
     *
     * @return array
     */
    protected function handleEditAction(Request $request, $login)
    {
        if (!ApiController::hasPermission($login, ['can_manage_eleave', 'can_config'])) {
            return $this->errorResponse('Forbidden', 403);
        }

        $id = $request->post('id')->toInt();

        $leaveType = \Eleave\Leavetype\Model::get($id);
        if ($leaveType === null) {
            return $this->errorResponse('No data available', 404);
        }

        return $this->successResponse([
            'data' => (array) $leaveType,
            'actions' => [
                'type' => 'modal',
                'template' => 'eleave/leave-type.html',
                'title' => '{LNG_Edit} {LNG_Leave Type}'
            ]
        ], 'Leave type details retrieved');
    }

    /**
     * Handle delete action
     *
     * @param Request $request
     * @param object $login
     *
     * @return array
     */
    protected function handleDeleteAction(Request $request, $login)
    {
        if (!ApiController::canModify($login, ['can_manage_eleave', 'can_config'])) {
            return $this->errorResponse('Forbidden', 403);
        }

        $ids = $request->request('ids', [])->toInt();
        $removeCount = Model::remove($ids);

        if (empty($removeCount)) {
            return $this->errorResponse('Delete action failed', 400);
        }

        \Index\Log\Model::add(0, 'eleave', 'Delete', 'Delete Leave type ID(s) : '.implode(', ', $ids), $login->id);

        return $this->redirectResponse('reload', 'Deleted '.$removeCount.' leave type(s) successfully');
    }

    /**
     * Handle active action
     *
     * @param Request $request
     * @param object $login
     *
     * @return Response
     */
    protected function handleActiveAction(Request $request, $login)
    {
        if (!ApiController::canModify($login, ['can_manage_eleave', 'can_config'])) {
            return $this->errorResponse('Failed to process request', 403);
        }

        $db = \Kotchasan\DB::create();

        // Get selected leave IDs
        $id = $request->post('id')->toInt();
        $leave = $db->first('leave', ['id', $id]);
        if (!$leave) {
            return $this->errorResponse('leave not found', 404);
        }

        $active = $leave->is_active == 1 ? 0 : 1;
        $db->update('leave', ['id', $id], ['is_active' => $active]);

        // Log the action
        $msg = $active ? 'Activated leave: '.$leave->name : 'Deactivated leave: '.$leave->name;
        \Index\Log\Model::add($leave->id, 'eleave', 'Save', $msg, $login->id);

        // Redirect to the same page with a success message
        return $this->redirectResponse('reload', $msg, 200, 0, 'table');
    }
}
