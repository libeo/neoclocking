<?php

namespace NeoClocking\Http\Controllers;

use Illuminate\Http\Request;
use NeoClocking\Exceptions\JsonHttpException;
use NeoClocking\Services\Updaters\ClientUpdater;
use NeoClocking\Services\Updaters\ProjectUpdater;
use NeoClocking\Services\Updaters\UserProjectRolesUpdater;
use NeoClocking\Services\Updaters\UserUpdater;

/**
 * Class EventListenerController
 *
 * Services can call this route to inform NéoClocking that a certain datum was updated in LDAP
 * This controller will then check LDAP and update said datum and any related data.
 */
class EventListenerController extends Controller
{
    const HTTP_STATUS_SUCCESS = 200;
    const HTTP_STATUS_BAD_REQUEST = 400;
    const HTTP_STATUS_NOT_IMPLEMENTED = 501;

    /**
     * @return string
     */
    public function parseRequest(Request $request)
    {
        $source = $request->input('source');
        $action = $request->input('action');
        $type = $request->input('type');
        $identifier = $request->input('reference');

        if (is_null($source) || is_null($action) || is_null($type) || is_null($identifier)) {
            $this->abortWithJsonHttpError(
                ['invalid_request' => "Missing Arguments"]
            );
        }

        if ($source !== 'libeodap') {
            $this->abortWithJsonHttpError(
                [
                    'not_implemented',
                    "Receiving messages from '{$source}' is not yet possible."
                ],
                self::HTTP_STATUS_NOT_IMPLEMENTED
            );
        }

        $success = $this->executeEventAction($action, $type, $identifier);

        if ($success) {
            $msg = "The action '{$action}' was successfully run on";
            $msg .= " the object of type '{$type}' identified by '{$identifier}'";
            $code = self::HTTP_STATUS_SUCCESS;
        } else {
            $msg = "The action '{$action}' failed to run successfully on";
            $msg .= " the object of type '{$type}' identified by '{$identifier}'";
            $code = self::HTTP_STATUS_BAD_REQUEST;
        }

        return response()->json(
            [
                'success' => $success,
                'code'    => $code,
                'message' => $msg,
            ],
            $code
        );
    }

    /**
     * @param string $action
     * @param string $type
     * @param string|int $identifier
     * @return bool
     * @throws \NeoClocking\Exceptions\JsonHttpException
     */
    private function executeEventAction($action, $type, $identifier)
    {
        $success = false;
        if ($action === "updated") {
            switch ($type) {
                case 'client':
                    if ($type === 'client' && !is_numeric($identifier)) {
                        $this->abortWithJsonHttpError(
                            ['client_number', 'Invalid client number; must be numeric.']
                        );
                    }
                    $success = $this->updateClient($identifier);
                    break;
                case 'project':
                    $success = $this->updateProject($identifier);
                    break;
                case 'user':
                    $success = $this->updateUser($identifier);
                    break;
                default:
                    $this->abortWithJsonHttpError(
                        ['invalid_type', "No object of type '{$type}' exists for the action '{$action}'"]
                    );
            }
        } else {
            $this->abortWithJsonHttpError(
                [
                    'not_implemented',
                    "The action '{$action}' has not yet been implemented"
                ],
                self::HTTP_STATUS_NOT_IMPLEMENTED
            );
        }
        return $success;
    }

    /**
     * @param string $username
     * @return bool
     */
    private function updateUser($username)
    {
        $userUpdater = new UserUpdater($username);
        $success = $userUpdater->update();
        if (! $success) {
            return false;
        }
        $userProjectRoleUpdater = new UserProjectRolesUpdater();
        return $userProjectRoleUpdater->updateByUser($username);
    }

    /**
     * @param string $projectNumber
     * @return bool
     */
    private function updateProject($projectNumber)
    {
        $projectUpdater = new ProjectUpdater($projectNumber);
        $success = $projectUpdater->update();
        if (! $success) {
            return false;
        }
        $userProjectRoleUpdater = new UserProjectRolesUpdater();
        return $userProjectRoleUpdater->updateByProject($projectNumber);
    }

    /**
     * @param integer $clientNumber
     * @return bool
     */
    private function updateClient($clientNumber)
    {
        $clientUpdater = new ClientUpdater($clientNumber);
        return $clientUpdater->update();
    }

    /**
     * @param array $errors Error data
     * @param int $errorCode HTTP error code
     * @throws JsonHttpException
     */
    private function abortWithJsonHttpError($errors, $errorCode = self::HTTP_STATUS_BAD_REQUEST)
    {
        throw new JsonHttpException($errors, $errorCode);
    }
}
