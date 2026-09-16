@extends('backend.layouts.app')

@section('title'){{ __('publisherstudio::studio.storage_connections') }}@endsection

@section('content')
<div class="card-main mb-5 p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Storage Connections</h3>
    <a href="{{ route('backend.publisher-submissions.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ph ph-arrow-left"></i> Back to queue</a>
  </div>

  <div class="alert alert-info">
    Choose where publisher media lives once publishers go live. These settings are captured now and are <b>inactive</b> — they do not re-point current uploads until activated during publisher onboarding.
  </div>

  @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

  <form method="post" action="{{ route('backend.publisher-storage.update') }}">
    @csrf @method('PUT')

    <div class="mb-4">
      <label class="form-label">Storage driver</label>
      <div class="d-flex gap-4">
        <label class="d-flex align-items-center gap-2">
          <input type="radio" name="publisher_storage_driver" value="hostinger_local" {{ ($settings['publisher_storage_driver']??'')==='hostinger_local'?'checked':'' }}>
          Native Hostinger storage (server disk)
        </label>
        <label class="d-flex align-items-center gap-2">
          <input type="radio" name="publisher_storage_driver" value="s3" {{ ($settings['publisher_storage_driver']??'')==='s3'?'checked':'' }}>
          Amazon S3 bucket
        </label>
      </div>
    </div>

    <fieldset class="border rounded p-3">
      <legend class="float-none w-auto px-2 fs-6 text-muted">Amazon S3 credentials</legend>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label small">Access Key ID</label><input class="form-control" name="publisher_s3_key" value="{{ $settings['publisher_s3_key'] ?? '' }}"></div>
        <div class="col-md-6"><label class="form-label small">Secret Access Key</label><input class="form-control" type="password" name="publisher_s3_secret" value="{{ $settings['publisher_s3_secret'] ?? '' }}"></div>
        <div class="col-md-4"><label class="form-label small">Region</label><input class="form-control" name="publisher_s3_region" value="{{ $settings['publisher_s3_region'] ?? '' }}" placeholder="us-east-1"></div>
        <div class="col-md-4"><label class="form-label small">Bucket</label><input class="form-control" name="publisher_s3_bucket" value="{{ $settings['publisher_s3_bucket'] ?? '' }}"></div>
        <div class="col-md-4"><label class="form-label small">Endpoint (optional)</label><input class="form-control" name="publisher_s3_endpoint" value="{{ $settings['publisher_s3_endpoint'] ?? '' }}" placeholder="https://…"></div>
        <div class="col-md-12"><label class="form-label small">Public URL base (optional)</label><input class="form-control" name="publisher_s3_url" value="{{ $settings['publisher_s3_url'] ?? '' }}" placeholder="https://cdn.example.com"></div>
      </div>
    </fieldset>

    <button class="btn btn-primary mt-4" type="submit"><i class="ph ph-floppy-disk"></i> Save settings</button>
  </form>
</div>
@endsection
