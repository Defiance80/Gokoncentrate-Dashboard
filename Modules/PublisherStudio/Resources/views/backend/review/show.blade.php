@extends('backend.layouts.app')

@section('title')Review: {{ $submission->title }}@endsection

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card-main mb-4 p-4">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <span class="badge bg-{{ $submission->status_color }} mb-2">{{ $submission->status_label }}</span>
          <h3 class="mb-1">{{ $submission->title }}</h3>
          <div class="text-muted">{{ $submission->type_label }} @if($submission->category) · {{ $submission->category }} @endif</div>
        </div>
        <a href="{{ route('backend.publisher-submissions.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ph ph-arrow-left"></i> Back</a>
      </div>

      @if($submission->cover_image_url)
        <img src="{{ $submission->cover_image_url }}" alt="cover" style="max-height:220px;border-radius:12px" class="mb-3">
      @endif

      @if($submission->synopsis)<p>{{ $submission->synopsis }}</p>@endif

      <h5 class="mt-4">Segments</h5>
      @php $items = $submission->payload['items'] ?? []; @endphp
      @if(empty($items))
        <p class="text-muted">No segments provided.</p>
      @else
        <ol class="ps-3">
          @foreach($items as $it)
            <li class="mb-2">
              <b>{{ $it['title'] ?? 'Untitled' }}</b>
              @if(!empty($it['kind']))<span class="text-muted"> — {{ $it['kind'] }}</span>@endif
              @if(!empty($it['media_url']))<br><a href="{{ $it['media_url'] }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($it['media_url'],80) }}</a>@endif
            </li>
          @endforeach
        </ol>
      @endif

      @if(!empty($submission->payload['notes']))
        <div class="alert alert-info mt-3"><b>Publisher notes:</b> {{ $submission->payload['notes'] }}</div>
      @endif
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card-main mb-4 p-4">
      <h5 class="mb-3">Publisher</h5>
      <p class="mb-1"><b>{{ optional($submission->publisher)->name }}</b></p>
      @if(optional($submission->publisher)->company)<p class="mb-1 text-muted">{{ $submission->publisher->company }}</p>@endif
      <p class="mb-1 text-muted">{{ optional($submission->publisher)->email }}</p>
      <p class="mb-0"><span class="badge bg-{{ optional($submission->publisher)->status==='approved'?'success':'secondary' }}">{{ ucfirst(optional($submission->publisher)->status ?? '') }}</span></p>
    </div>

    @if(in_array($submission->status, ['submitted','changes_requested']))
    <div class="card-main mb-4 p-4">
      <h5 class="mb-3">Decision</h5>

      <form method="post" action="{{ route('backend.publisher-submissions.approve', $submission->id) }}" class="mb-3">
        @csrf
        <div class="mb-2">
          <label class="form-label small text-muted">Note (optional)</label>
          <textarea name="review_notes" class="form-control" rows="2"></textarea>
        </div>
        <button class="btn btn-success w-100" type="submit"><i class="ph ph-check-circle"></i> Approve &amp; publish</button>
        @if($submission->type==='veemag')<small class="text-muted d-block mt-1">Publishes into the VeeMags library.</small>@else<small class="text-muted d-block mt-1">{{ $submission->type_label }} home is a later increment; media is saved.</small>@endif
      </form>

      <form method="post" action="{{ route('backend.publisher-submissions.reject', $submission->id) }}">
        @csrf
        <div class="mb-2">
          <label class="form-label small text-muted">Reason / requested changes</label>
          <textarea name="review_notes" class="form-control" rows="2" required></textarea>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-warning w-100" type="submit" name="decision" value="changes">Request changes</button>
          <button class="btn btn-danger w-100" type="submit" name="decision" value="reject">Reject</button>
        </div>
      </form>
    </div>
    @else
    <div class="card-main mb-4 p-4">
      <h5 class="mb-2">Status</h5>
      <p class="mb-1"><span class="badge bg-{{ $submission->status_color }}">{{ $submission->status_label }}</span></p>
      @if($submission->reviewed_at)<p class="text-muted small mb-1">Reviewed {{ $submission->reviewed_at->diffForHumans() }}</p>@endif
      @if($submission->review_notes)<p class="small mb-0"><b>Note:</b> {{ $submission->review_notes }}</p>@endif
      @if($submission->status==='approved' && $submission->published_ref_id && $submission->type==='veemag')
        <hr><a href="{{ url('/veemag') }}" class="btn btn-outline-primary btn-sm w-100" target="_blank">View in VeeMags library</a>
      @endif
    </div>
    @endif
  </div>
</div>
@endsection
