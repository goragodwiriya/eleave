<?php
/**
 * @filesource modules/eleave/controllers/dashboard.php
 */

namespace Eleave\Dashboard;

use Kotchasan\Http\Request;

class Controller extends \Gcms\Api
{
    /**
     * Authenticate the current dashboard request.
     *
     * @param Request $request
     *
     * @return object
     */
    protected function getLogin(Request $request)
    {
        $login = $this->authenticateRequest($request);
        if (!$login) {
            throw new \RuntimeException('Unauthorized', 401);
        }

        return $login;
    }

    /**
     * Cards endpoint for dashboard summary widgets.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function cards(Request $request)
    {
        try {
            self::validateMethod($request, 'GET');

            $login = $this->getLogin($request);

            return $this->successResponse(Model::getCards($login), 'Dashboard cards retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Graph endpoint for the dashboard department chart.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function graph(Request $request)
    {
        try {
            self::validateMethod($request, 'GET');

            $login = $this->getLogin($request);

            return $this->successResponse(Model::getGraph($login), 'Dashboard graph retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * Table endpoint for approval logs.
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function logs(Request $request)
    {
        try {
            self::validateMethod($request, 'GET');

            $login = $this->getLogin($request);

            $data = Model::toDataTable([], $login);

            return $this->successResponse([
                'data' => $data,
                'columns' => [],
                'filters' => [],
                'options' => [],
                'meta' => [
                    'page' => 1,
                    'pageSize' => 10,
                    'total' => count($data),
                    'totalPages' => 1
                ]
            ], 'Data retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}