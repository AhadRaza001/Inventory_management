<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class ResponseController extends Controller
{
    public function sendResponse($result, $message = 'Success')
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $result
        ]);
    }

    public function sendError($message, $errorDetails, $code)
    {
        $response = [
            'status' => false,
            'message' => $message,
        ];
        if (!is_null($errorDetails) && App::environment('local')) {
            $response['debug'] = $errorDetails;
        }
        return response()->json($response, $code);
    }

    public function validationError($error)
    {
        return response()->json([
            'status' => false,
            'message' => 'Validation Error',
            'errors' => $error
        ], 422);
    }

    public function sendPaginatedResponse($paginatedData, string $message = 'Data fetched successfully')
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $paginatedData->items(),

            'pagination' => [
                'current_page' => $paginatedData->currentPage(),
                'last_page' => $paginatedData->lastPage(),
                'per_page' => $paginatedData->perPage(),
                'total' => $paginatedData->total(),
                'has_more_pages' => $paginatedData->hasMorePages(),
            ]
        ]);
    }
}
