<?php

namespace NeoClocking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;

abstract class Request extends FormRequest
{
    protected $forbiddenMessage;

    public function forbiddenResponse()
    {
        if ($this->forbiddenMessage === null) {
            return parent::forbiddenResponse();
        }

        $message = $this->formatResponse();

        return new JsonResponse($message, 403);
    }

    protected function formatResponse()
    {
        $data = $this->getAuthorizationData();

        return [
            'message' => vsprintf($this->forbiddenMessage, $data),
            'status' => 403,
        ];
    }

    protected function getAuthorizationData()
    {
        return [];
    }
}
