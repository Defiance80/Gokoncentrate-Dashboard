@extends('backend.layouts.app')
@section('title')Applicant: {{ $publisher->name }} @endsection

@php
    $p = $publisher->profile ?? [];
    $fmtLabels = ['veemag'=>'VeeMags','podcast'=>'Podcasts','short_film'=>'Short Films','music_video'=>'Music Videos'];
    $statusColor = ['approved'=>'success','pending'=>'warning','suspended'=>'danger'][$publisher->status] ?? 'secondary';
@endphp

@section('content')
<div class="row">
  <div class="col-lg-8">
    <div class="card-main mb-4 p-4">
      <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
          <span class="badge bg-{{ $statusColor }} mb-2">{{ ucfirst($publisher->status) }}</span>
          <h3 class="mb-1">{{ $publisher->company ?: $publisher->name }}</h3>
          <div class="text-muted">{{ $publisher->name }} · {{ $publisher->content_focus ?: '—' }}</div>
        </div>
        <a href="{{ route('backend.publishers.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ph ph-arrow-left"></i> Back</a>
      </div>

      <div class="row g-3">
        <div class="col-md-6"><small class="text-muted d-block">Email</small><a href="mailto:{{ $publisher->email }}">{{ $publisher->email }}</a></div>
        <div class="col-md-6"><small class="text-muted d-block">Phone</small>{{ $publisher->phone ?: '—' }}</div>
        <div class="col-md-6"><small class="text-muted d-block">Website / portfolio</small>@if($publisher->website)<a href="{{ $publisher->website }}" target="_blank" rel="noopener">{{ $publisher->website }}</a>@else — @endif</div>
        <div class="col-md-6"><small class="text-muted d-block">Publishing cadence</small>{{ $p['cadence'] ?? '—' }}</div>
        <div class="col-md-6"><small class="text-muted d-block">Audience size</small>{{ $p['audience_size'] ?? '—' }}</div>
        <div class="col-md-6"><small class="text-muted d-block">Primary market / region</small>{{ $p['region'] ?? '—' }}</div>
      </div>

      <hr>
      <small class="text-muted d-block mb-2">Plans to publish</small>
      <div class="mb-3">
        @forelse(($p['formats'] ?? []) as $f)
          <span class="badge bg-primary-subtle text-primary me-1">{{ $fmtLabels[$f] ?? $f }}</span>
        @empty — @endforelse
      </div>

      <div class="row g-3">
        <div class="col-md-4"><small class="text-muted d-block">Instagram</small>{{ data_get($p,'social.instagram') ?: '—' }}</div>
        <div class="col-md-4"><small class="text-muted d-block">YouTube</small>{{ data_get($p,'social.youtube') ?: '—' }}</div>
        <div class="col-md-4"><small class="text-muted d-block">TikTok</small>{{ data_get($p,'social.tiktok') ?: '—' }}</div>
      </div>

      <hr>
      <div class="mb-3">
        <small class="text-muted d-block">Has existing content</small>
        {{ ($p['has_content'] ?? false) ? 'Yes' : 'No' }}
        @if(!empty($p['sample_url'])) · <a href="{{ $p['sample_url'] }}" target="_blank" rel="noopener">View sample</a>@endif
      </div>
      @if(!empty($p['experience']))
        <div class="mb-3"><small class="text-muted d-block">Relevant experience</small><p class="mb-0">{{ $p['experience'] }}</p></div>
      @endif
      @if(!empty($p['pitch']))
        <div class="mb-0"><small class="text-muted d-block">Pitch</small><p class="mb-0">{{ $p['pitch'] }}</p></div>
      @endif
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card-main mb-4 p-4">
      <h5 class="mb-3">Qualify applicant</h5>
      <p class="mb-1"><small class="text-muted">Submissions</small><br><strong>{{ $publisher->submissions_count }}</strong></p>
      <p class="mb-1"><small class="text-muted">Applied</small><br>{{ data_get($p,'applied_at') ?: $publisher->created_at }}</p>
      @if($publisher->approved_at)<p class="mb-3"><small class="text-muted">Approved</small><br>{{ $publisher->approved_at }}</p>@endif

      <hr>
      @if($publisher->status !== 'approved')
        <form method="post" action="{{ route('backend.publishers.approve', $publisher->id) }}" class="mb-2">@csrf
          <button class="btn btn-success w-100"><i class="ph ph-check-circle"></i> Approve publisher</button>
        </form>
      @endif
      @if($publisher->status !== 'suspended')
        <form method="post" action="{{ route('backend.publishers.suspend', $publisher->id) }}" class="mb-2" onsubmit="return confirm('Suspend this publisher? They will not be able to sign in.')">@csrf
          <button class="btn btn-outline-danger w-100"><i class="ph ph-prohibit"></i> Suspend</button>
        </form>
      @else
        <form method="post" action="{{ route('backend.publishers.reactivate', $publisher->id) }}" class="mb-2">@csrf
          <button class="btn btn-outline-secondary w-100"><i class="ph ph-arrow-counter-clockwise"></i> Reactivate</button>
        </form>
      @endif

      @if($publisher->submissions_count > 0)
        <a href="{{ route('backend.publisher-submissions.index') }}" class="btn btn-link w-100 mt-2">View their submissions &rarr;</a>
      @endif
    </div>
  </div>
</div>
@endsection
