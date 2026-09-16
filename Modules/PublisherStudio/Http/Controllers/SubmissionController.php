<?php

namespace Modules\PublisherStudio\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\PublisherStudio\Models\PublisherSubmission;

class SubmissionController extends Controller
{
    private function publisherId(): int
    {
        return (int) Auth::guard('publisher')->id();
    }

    public function index()
    {
        $submissions = PublisherSubmission::where('publisher_id', $this->publisherId())
            ->latest()
            ->paginate(15);

        return view('publisherstudio::submissions.index', compact('submissions'));
    }

    public function create(Request $request)
    {
        $type = $request->get('type', 'veemag');
        if (! array_key_exists($type, PublisherSubmission::TYPES)) {
            $type = 'veemag';
        }
        $submission = new PublisherSubmission(['type' => $type]);

        return view('publisherstudio::submissions.edit', [
            'submission' => $submission,
            'mode'       => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $submission = new PublisherSubmission();
        $submission->publisher_id = $this->publisherId();
        $this->fill($submission, $data, $request);
        $submission->status = $request->input('action') === 'submit' ? 'submitted' : 'draft';
        if ($submission->status === 'submitted') {
            $submission->submitted_at = now();
        }
        $submission->save();

        return redirect()->route('studio.submissions.index')
            ->with('status', $submission->status === 'submitted'
                ? 'Publication submitted for review.'
                : 'Draft saved.');
    }

    public function edit(PublisherSubmission $submission)
    {
        $this->authorizeOwner($submission);

        return view('publisherstudio::submissions.edit', [
            'submission' => $submission,
            'mode'       => 'edit',
        ]);
    }

    public function update(Request $request, PublisherSubmission $submission)
    {
        $this->authorizeOwner($submission);
        abort_unless($submission->isEditableByPublisher(), 403, 'This publication can no longer be edited.');

        $data = $this->validated($request);
        $this->fill($submission, $data, $request);

        if ($request->input('action') === 'submit') {
            $submission->status = 'submitted';
            $submission->submitted_at = now();
        }
        $submission->save();

        return redirect()->route('studio.submissions.index')
            ->with('status', $submission->status === 'submitted'
                ? 'Publication submitted for review.'
                : 'Changes saved.');
    }

    public function destroy(PublisherSubmission $submission)
    {
        $this->authorizeOwner($submission);
        abort_unless($submission->isEditableByPublisher(), 403);
        $submission->delete();

        return redirect()->route('studio.submissions.index')->with('status', 'Publication removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'type'            => ['required', 'in:' . implode(',', array_keys(PublisherSubmission::TYPES))],
            'title'           => ['required', 'string', 'max:180'],
            'category'        => ['nullable', 'string', 'max:120'],
            'synopsis'        => ['nullable', 'string', 'max:5000'],
            'cover_image_url' => ['nullable', 'string', 'max:2048'],
            'item_title'      => ['nullable', 'array'],
            'item_title.*'    => ['nullable', 'string', 'max:200'],
            'item_url'        => ['nullable', 'array'],
            'item_url.*'      => ['nullable', 'string', 'max:2048'],
            'item_kind'       => ['nullable', 'array'],
            'item_kind.*'     => ['nullable', 'string', 'max:60'],
            'notes'           => ['nullable', 'string', 'max:5000'],
        ]);
    }

    private function fill(PublisherSubmission $submission, array $data, Request $request): void
    {
        $submission->type = $data['type'];
        $submission->title = $data['title'];
        $submission->slug = Str::slug($data['title']) ?: Str::random(8);
        $submission->category = $data['category'] ?? null;
        $submission->synopsis = $data['synopsis'] ?? null;
        $submission->cover_image_url = $data['cover_image_url'] ?? null;

        $items = [];
        $titles = $request->input('item_title', []);
        $urls   = $request->input('item_url', []);
        $kinds  = $request->input('item_kind', []);
        foreach ($titles as $i => $t) {
            $t = trim((string) $t);
            $u = trim((string) ($urls[$i] ?? ''));
            if ($t === '' && $u === '') {
                continue;
            }
            $items[] = [
                'title'     => $t,
                'media_url' => $u,
                'kind'      => trim((string) ($kinds[$i] ?? '')),
            ];
        }

        $submission->payload = [
            'items' => array_values($items),
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function authorizeOwner(PublisherSubmission $submission): void
    {
        abort_unless($submission->publisher_id === $this->publisherId(), 403);
    }
}
