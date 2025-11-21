<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponse
{
    /**
     * Return a successful JSON response.
     *
     * @param  mixed  $data
     */
    protected function successResponse($data = null, ?string $message = null, int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a paginated JSON response.
     *
     * @param  LengthAwarePaginator|AnonymousResourceCollection  $paginator
     */
    protected function paginatedResponse($paginator, ?string $message = null): JsonResponse
    {
        $data = $paginator->items();
        $pagination = [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];

        $response = [
            'success' => true,
            'data' => $data,
            'pagination' => $pagination,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        return response()->json($response, 200);
    }

    /**
     * Return an error JSON response.
     *
     * @param  mixed  $errors
     */
    protected function errorResponse(string $message, int $statusCode = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }
}
