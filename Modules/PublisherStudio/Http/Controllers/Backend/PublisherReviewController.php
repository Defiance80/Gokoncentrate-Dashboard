<?php

namespace Modules\PublisherStudio\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\VeeMagIssue;
use App\Models\VeeMagPublication;
use App\Models\VeeMagSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\PublisherStudio\Models\Publisher;
use Modules\PublisherStudio\Models\PublisherSubmission;
use Yajra\DataTables\DataTables;

class PublisherReviewController extends Controller
{
    public function index(Request $request)
    {
        $module_action = 'List';
        $filter = ['status' => $request->get('status'), 'type' => $request->get('type')];
        return view('publisherstudio::backend.review.index', compact('module_action', 'filter'));
    }

    public function index_data(DataTables $datatable, Request $request)
    {
        $query = PublisherSubmission::query()->with('publisher')->latest();

        if ($request->filled('filter.column_status')) {
            $query->where('status', $request->input('filter.column_status'));
        }
        if ($request->filled('filter.column_type')) {
            $query->where('type', $request->input('filter.column_type'));
        }

        return $datatable->eloquent($query)
            ->addColumn('publisher', fn ($d) => optional($d->publisher)->name ?? '-')
            ->addColumn('type_label', fn ($d) => $d->type_label)
            ->editColumn('status', fn ($d) => '<span class="badge bg-' . $d->status_color . '">' . e($d->status_label) . '</span>')
            ->addColumn('action', fn ($d) => view('publisherstudio::backend.review.action', ['data' => $d])->render())
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function show(PublisherSubmission $submission)
    {
        $submission->load('publisher', 'reviewer');
        return view('publisherstudio::backend.review.show', compact('submission'));
    }

    public function approve(Request $request, PublisherSubmission $submission)
    {
        if ($submission->status === 'approved') {
            return back()->with('status', 'This submission is already approved.');
        }

        DB::beginTransaction();
        try {
            if ($submission->type === 'veemag') {
                $issue = $this->publishVeeMag($submission);
                $submission->published_ref_type = VeeMagIssue::class;
                $submission->published_ref_id = $issue->id;
            }
            // podcast / short_film: approved and payload preserved. A dedicated
            // content home for these types is a later increment; the submission
            // is retained with its media so it can be published when that lands.

            $submission->status = 'approved';
            $submission->reviewed_by = Auth::id();
            $submission->reviewed_at = now();
            if ($request->filled('review_notes')) {
                $submission->review_notes = $request->input('review_notes');
            }
            $submission->save();

            // Promote the publisher account to approved on first accepted work.
            $publisher = Publisher::find($submission->publisher_id);
            if ($publisher && $publisher->status === 'pending') {
                $publisher->status = 'approved';
                $publisher->approved_at = now();
                $publisher->approved_by = Auth::id();
                $publisher->save();
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Approval failed: ' . $e->getMessage());
        }

        $msg = $submission->type === 'veemag'
            ? 'Approved and published to the VeeMags library.'
            : 'Approved. (' . $submission->type_label . ' publishing home is a later increment; media saved.)';

        return redirect()->route('backend.publisher-submissions.index')->with('status', $msg);
    }

    public function reject(Request $request, PublisherSubmission $submission)
    {
        $request->validate(['review_notes' => ['required', 'string', 'max:2000']]);

        $submission->status = $request->input('decision') === 'changes'
            ? 'changes_requested'
            : 'rejected';
        $submission->review_notes = $request->input('review_notes');
        $submission->reviewed_by = Auth::id();
        $submission->reviewed_at = now();
        $submission->save();

        return redirect()->route('backend.publisher-submissions.index')
            ->with('status', 'Feedback sent to the publisher.');
    }

    /** Materialise an approved VeeMag submission into the live viewer models. */
    private function publishVeeMag(PublisherSubmission $submission): VeeMagIssue
    {
        $publisher = Publisher::find($submission->publisher_id);

        $publication = VeeMagPublication::firstOrCreate(
            ['slug' => 'publisher-' . $submission->publisher_id],
            [
                'owner_id'    => $submission->publisher_id,
                'title'       => $publisher->company ?: ($publisher->name ?? 'Publisher'),
                'description' => 'Publications by ' . ($publisher->name ?? 'a GoKoncentrate publisher') . '.',
                'category'    => $submission->category,
                'status'      => 1,
            ]
        );

        $nextIssueNumber = (int) VeeMagIssue::where('publication_id', $publication->id)->max('issue_number') + 1;

        $issue = VeeMagIssue::create([
            'publication_id' => $publication->id,
            'volume'         => 1,
            'issue_number'   => $nextIssueNumber,
            'title'          => $submission->title,
            'slug'           => $this->uniqueIssueSlug($submission->slug ?: Str::slug($submission->title)),
            'description'    => $submission->synopsis,
            'release_date'   => now()->toDateString(),
            'cover_url'      => $submission->cover_image_url,
            'hero_url'       => $submission->cover_image_url,
            'hero_type'      => 'image',
            'status'         => 'published',
            'visibility'     => 'public',
        ]);

        $items = data_get($submission->payload, 'items', []);
        $order = 1;
        foreach ($items as $item) {
            $url = trim((string) ($item['media_url'] ?? ''));
            [$provider] = $this->detectProvider($url);
            VeeMagSection::create([
                'issue_id'          => $issue->id,
                'type'              => $order === 1 ? 'opening' : 'video_article',
                'title'             => $item['title'] ?? ('Section ' . $order),
                'order_index'       => $order,
                'video_upload_type' => $url ? $provider : null,
                'video_url_input'   => $url ?: null,
                'status'            => 1,
            ]);
            $order++;
        }

        return $issue;
    }

    private function uniqueIssueSlug(string $base): string
    {
        $base = $base ?: Str::random(8);
        $slug = $base;
        $i = 2;
        while (VeeMagIssue::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    /** Returns [upload_type] matching the platform's video_upload_type values. */
    private function detectProvider(string $url): array
    {
        if ($url === '') {
            return ['Embedded'];
        }
        if (preg_match('~youtu~i', $url)) {
            return ['YouTube'];
        }
        if (preg_match('~vimeo~i', $url)) {
            return ['Vimeo'];
        }
        return ['Embedded'];
    }
}
