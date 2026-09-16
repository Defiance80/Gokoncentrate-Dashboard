<?php

namespace Modules\PublisherStudio\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\PublisherStudio\Models\PublisherSubmission;

class StudioController extends Controller
{
    public function dashboard()
    {
        $publisher = Auth::guard('publisher')->user();

        $base = PublisherSubmission::where('publisher_id', $publisher->id);

        $counts = [
            'total'     => (clone $base)->count(),
            'draft'     => (clone $base)->where('status', 'draft')->count(),
            'submitted' => (clone $base)->whereIn('status', ['submitted', 'changes_requested'])->count(),
            'approved'  => (clone $base)->where('status', 'approved')->count(),
            'rejected'  => (clone $base)->where('status', 'rejected')->count(),
        ];

        $recent = (clone $base)->latest()->limit(6)->get();

        return view('publisherstudio::dashboard.index', compact('publisher', 'counts', 'recent'));
    }
}
