<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

/**
 * A payment step that is not allowed in the request's current state (rendered as a JSON business error).
 */
class PaymentFlowException extends Exception
{
    public function __construct(string $message, private int $status = 422)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
        ], $this->status);
    }
}
