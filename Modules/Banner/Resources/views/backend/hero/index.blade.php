@extends('backend.layouts.app')

@section('title'){{ __('messages.hero_rotation') }}@endsection

@section('content')
<div class="card-main mb-4 p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">{{ __('messages.hero_rotation') }}</h3>
        <div class="d-flex gap-2">
            <a href="{{ route('backend.hero-rotation.rotate') }}" class="btn btn-outline-secondary btn-sm">
                <i class="ph ph-eye"></i> {{ __('messages.hero_preview_btn') }}
            </a>
            <a href="{{ route('backend.hero-rotation.rotate', ['apply' => 1]) }}" class="btn btn-primary btn-sm">
                <i class="ph ph-arrows-clockwise"></i> {{ __('messages.hero_rotate_btn') }}
            </a>
        </div>
    </div>

    <div class="alert alert-info">{{ __('messages.hero_rotation_intro') }}</div>

    @if(session('status'))<div class="alert alert-success mb-2">{{ session('status') }}</div>@endif
    @if(session('rotation_notes'))
        <div class="alert alert-secondary">
            @foreach(session('rotation_notes') as $note)<div class="small">{{ $note }}</div>@endforeach
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <form method="post" action="{{ route('backend.hero-rotation.settings') }}">
        @csrf
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">{{ __('messages.hero_min_views') }}</label>
                <input class="form-control" type="number" min="1" name="hero_rotate_min_views"
                    value="{{ old('hero_rotate_min_views', $settings['hero_rotate_min_views']) }}">
                <div class="form-text small">{{ __('messages.hero_min_views_help') }}</div>
            </div>
            <div class="col-md-3">
                <label class="form-label small">{{ __('messages.hero_min_width') }}</label>
                <input class="form-control" type="number" min="320" name="hero_rotate_min_width"
                    value="{{ old('hero_rotate_min_width', $settings['hero_rotate_min_width']) }}">
                <div class="form-text small">{{ __('messages.hero_min_width_help') }}</div>
            </div>
            <div class="col-md-3">
                <label class="d-flex align-items-center gap-2">
                    <input type="checkbox" name="hero_rotate_enabled" value="1"
                        {{ ($settings['hero_rotate_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                    {{ __('messages.hero_weekly_enabled') }}
                </label>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary" type="submit">
                    <i class="ph ph-floppy-disk"></i> {{ __('messages.save') }}
                </button>
            </div>
        </div>
    </form>
</div>

<div class="card-main mb-4 p-4">
    <h5 class="mb-3">{{ __('messages.hero_slides') }}</h5>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('messages.title') }}</th>
                    <th>{{ __('messages.type') }}</th>
                    <th>{{ __('messages.status') }}</th>
                    <th>{{ __('messages.hero_locked') }}</th>
                    <th>{{ __('messages.hero_auto') }}</th>
                    <th>{{ __('messages.hero_last_rotated') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($slides as $slide)
                    <tr>
                        <td class="text-muted">{{ $slide->id }}</td>
                        <td class="fw-semibold">{{ $slide->title ?: $slide->type_name ?: '—' }}</td>
                        <td class="text-muted">{{ $slide->type }} #{{ $slide->type_id }}</td>
                        <td>
                            <span class="badge text-bg-{{ $slide->status ? 'success' : 'secondary' }}">
                                {{ $slide->status ? __('messages.active') : __('messages.inactive') }}
                            </span>
                        </td>
                        <td>
                            <form method="post" action="{{ route('backend.hero-rotation.lock', $slide->id) }}">
                                @csrf
                                <button class="btn btn-sm {{ $slide->is_locked ? 'btn-warning' : 'btn-outline-secondary' }}"
                                    type="submit">
                                    <i class="ph {{ $slide->is_locked ? 'ph-lock' : 'ph-lock-open' }}"></i>
                                    {{ $slide->is_locked ? __('messages.hero_locked') : __('messages.hero_unlocked') }}
                                </button>
                            </form>
                        </td>
                        <td>
                            <form method="post" action="{{ route('backend.hero-rotation.auto', $slide->id) }}">
                                @csrf
                                <button class="btn btn-sm {{ $slide->auto_managed ? 'btn-outline-primary' : 'btn-outline-secondary' }}"
                                    type="submit" {{ $slide->is_locked ? 'disabled' : '' }}>
                                    {{ $slide->auto_managed ? __('messages.hero_auto_on') : __('messages.hero_auto_off') }}
                                </button>
                            </form>
                        </td>
                        <td class="text-muted small">
                            {{ $slide->last_rotated_at ? \Carbon\Carbon::parse($slide->last_rotated_at)->format('d M Y H:i') : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-muted">{{ __('messages.no_record') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card-main p-4">
    <h5 class="mb-1">{{ __('messages.hero_candidates') }}</h5>
    <p class="text-muted small mb-3">{{ __('messages.hero_candidates_help') }}</p>
    @if($candidates->isEmpty())
        <div class="alert alert-warning mb-0">{{ __('messages.hero_no_candidates') }}</div>
    @else
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>{{ __('messages.title') }}</th>
                        <th>{{ __('messages.type') }}</th>
                        <th>{{ __('messages.hero_views') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($candidates as $c)
                        <tr>
                            <td>{{ $c->name }}</td>
                            <td class="text-muted">{{ $c->type }}</td>
                            <td>{{ $c->view_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
