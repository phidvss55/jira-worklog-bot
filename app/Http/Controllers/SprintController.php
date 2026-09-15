<?php

namespace App\Http\Controllers;

use App\Application\Sprint\GetActiveSprintHandler;
use App\Services\Jira\JiraClientException;
use Illuminate\Http\JsonResponse;

final class SprintController extends Controller
{
    public function __invoke(GetActiveSprintHandler $handler): JsonResponse
    {
        try {
            return response()->json($handler->handle()->toArray());
        } catch (JiraClientException) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to load the active sprint from Jira.',
            ], 502);
        }
    }
}
