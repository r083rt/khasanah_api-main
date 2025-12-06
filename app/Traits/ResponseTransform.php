<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Custom Response
 */
trait ResponseTransform
{

    /**
     * response
     *
     * @param mixed $responseMessage
     * @param string $responseStatus
     * @param integer $responseCode
     * @param array $responseHeader
     * @param array $additionals
     * @return JsonResponse
     */
    public function response($responseMessage, string $responseStatus = 'success', int $responseCode = 200, array $responseHeader = [], array $additionals = []): JsonResponse
    {
        $response['status'] = $responseStatus;

        if ($responseStatus != 'success') {
            $responseCode = $responseCode == 200 ? 422 : $responseCode;
            $response['message']    = $responseCode == 422 ? 'Data tidak valid' : $responseMessage;
            if (is_array($responseMessage)) {
                $response['errors']     = [
                    'message'   => [$responseMessage]
                ];
            } else {
                $response['errors']     = [
                    'message'   => [
                        [
                            'error' => [$responseMessage]
                        ]
                    ]
                ];
            }
        } else {
            $response['data']   = $responseMessage;
        }

        if (!empty($additionals)) {
            $response = array_merge($response, $additionals);
        }

        return response()->json($response, $responseCode, $responseHeader);
    }
}
