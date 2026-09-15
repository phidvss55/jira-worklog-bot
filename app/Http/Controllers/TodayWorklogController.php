<?php

namespace App\Http\Controllers;

use App\Application\Worklog\GetTodayWorklogSummaryHandler;
use App\Services\Jira\JiraClientException;
use Illuminate\Http\JsonResponse;

final class TodayWorklogController extends Controller
{
    public function __invoke(GetTodayWorklogSummaryHandler $handler): JsonResponse
    {
        try {
            return response()->json($handler->handle()->toArray());
        } catch (JiraClientException) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to load today\'s worklog summary from Jira.',
            ], 502);
        }
    }
}
