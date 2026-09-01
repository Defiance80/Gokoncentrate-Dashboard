<?php

namespace Modules\Magazine\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Modules\Magazine\Models\MagazineIssue;
use Modules\Magazine\Models\MagazinePrintEvent;

class MagazinePrintController extends Controller
{
    /**
     * Redirect to MagCloud product URL and log click_print event.
     * GET /r/magazine/{id}/print
     */
    public function redirect(Request $request, int $id): Response
    {
        $issue = MagazineIssue::whereNull('deleted_at')
            ->where('id', $id)
            ->with('printConfig')
            ->first();

        if (! $issue || ! $issue->printConfig || ! $issue->printConfig->print_enabled) {
            abort(404, 'Print not available for this issue.');
        }

        $url = $issue->printConfig->magcloud_product_url;
        if (empty($url) || ! \Illuminate\Support\Str::startsWith(strtolower($url), 'https://')) {
            Log::warning('Magazine print redirect: invalid or non-HTTPS URL for issue ' . $id);
            abort(404, 'Print link is not configured.');
        }

        MagazinePrintEvent::create([
            'user_id' => $request->user()?->id,
            'issue_id' => $issue->id,
            'event' => 'click_print',
            'session_id' => $request->header('X-Session-Id') ?: $request->query('session_id'),
            'device' => $request->userAgent(),
            'referrer' => $request->header('Referer'),
        ]);

        return redirect()->away($url, 302);
    }

    /**
     * Store a print-related event (e.g. return_from_magcloud, copied_link).
     * POST /api/magazine-issues/{id}/print/events
     */
    public function storeEvent(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'event' => 'required|string|in:click_print,return_from_magcloud,copied_link',
            'session_id' => 'nullable|string|max:255',
        ]);

        $issue = MagazineIssue::whereNull('deleted_at')->where('id', $id)->first();
        if (! $issue) {
            return response()->json(['status' => false, 'message' => 'Issue not found'], 404);
        }

        MagazinePrintEvent::create([
            'user_id' => $request->user()?->id,
            'issue_id' => $issue->id,
            'event' => $validated['event'],
            'session_id' => $validated['session_id'] ?? null,
            'device' => $request->userAgent(),
            'referrer' => $request->header('Referer'),
        ]);

        return response()->json(['status' => true, 'message' => 'Event recorded']);
    }
}
