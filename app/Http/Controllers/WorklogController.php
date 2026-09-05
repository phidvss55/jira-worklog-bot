<?php

namespace App\Http\Controllers;

use App\Application\Worklog\LogWorkCommand;
use App\Application\Worklog\LogWorkHandler;
use App\Http\Requests\StoreWorklogRequest;
use App\Services\Jira\JiraClientException;
use App\Support\DurationParser;
use App\Support\WorklogDateParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class WorklogController extends Controller
{
    public function __invoke(
        StoreWorklogRequest $request,
        DurationParser $durationParser,
        WorklogDateParser $dateParser,
        LogWorkHandler $handler,
    ): JsonResponse {
        /** @var array{ticket: string, duration: string, date?: string|null, time?: string|null} $input */
        $input = $request->validated();

        $command = new LogWorkCommand(
            ticket: Str::upper($input['ticket']),
            duration: $input['duration'],
            durationSeconds: $durationParser->parse($input['duration']),
            started: $dateParser->parse($input['date'] ?? null, $input['time'] ?? null),
        );

        try {
            $result = $handler->handle($command);
        } catch (JiraClientException) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to log work. Jira rejected the worklog.',
            ], 502);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
