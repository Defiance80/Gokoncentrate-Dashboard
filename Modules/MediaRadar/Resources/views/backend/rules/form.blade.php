@php
    /** @var \Modules\MediaRadar\Models\MediaDiscoveryRule|null $rule */
    $rule = $rule ?? null;

    $list = static function ($value) {
        return is_array($value) ? implode(PHP_EOL, $value) : (string) $value;
    };

    $selectedProviders = old('providers', $rule?->providerSlugs() ?? ['youtube']);
    $autoApprove = old('auto_approve', $rule === null || $rule->auto_approve === null ? 'inherit' : ($rule->auto_approve ? '1' : '0'));
@endphp

{{-- Required --}}
<div class="card mb-3">
    <div class="card-header">
        <h5 class="mb-0">{{ __('mediaradar::mediaradar.required_section') }}</h5>
    </div>
    <div class="card-body">
        <div class="row gy-3">
            <div class="col-md-6">
                {{ html()->label(__('mediaradar::mediaradar.lbl_name') . '<span class="text-danger">*</span>', 'name')->class('form-label') }}
                {{ html()->text('name', old('name', $rule?->name))->class('form-control')->required() }}
                @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                {{ html()->label(__('mediaradar::mediaradar.lbl_genre') . '<span class="text-danger">*</span>', 'genre_id')->class('form-label') }}
                {{ html()->select('genre_id', $genres, old('genre_id', $rule?->genre_id))->class('form-control select2')->required() }}
                <small class="text-muted">{{ __('mediaradar::mediaradar.lbl_genre_help') }}</small>
                @error('genre_id')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label class="form-label">
                    {{ __('mediaradar::mediaradar.lbl_providers') }}<span class="text-danger">*</span>
                </label>
                <div class="d-flex gap-4">
                    @foreach ($providerOptions as $slug => $label)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="providers[]" value="{{ $slug }}"
                                id="provider_{{ $slug }}" {{ in_array($slug, (array) $selectedProviders, true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="provider_{{ $slug }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
                <small class="text-muted">{{ __('mediaradar::mediaradar.lbl_providers_help') }}</small>
                @error('providers')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                {{ html()->label(__('mediaradar::mediaradar.lbl_description'), 'description')->class('form-label') }}
                {{ html()->textarea('description', old('description', $rule?->description))->class('form-control')->rows(2) }}
            </div>
        </div>
    </div>
</div>

{{-- Optional filters --}}
<div class="card mb-3">
    <div class="card-header">
        <h5 class="mb-0">{{ __('mediaradar::mediaradar.optional_section') }}</h5>
    </div>
    <div class="card-body">
        <div class="row gy-3">
            <div class="col-md-6">
                {{ html()->label(__('mediaradar::mediaradar.lbl_search_terms'), 'search_terms')->class('form-label') }}
                {{ html()->textarea('search_terms', old('search_terms', $list($rule?->arrayValue('search_terms') ?? [])))->class('form-control')->rows(3) }}
                <small class="text-muted">{{ __('mediaradar::mediaradar.lbl_search_terms_help') }}</small>
            </div>

            <div class="col-md-6">
                {{ html()->label(__('mediaradar::mediaradar.lbl_excluded_terms'), 'excluded_terms')->class('form-label') }}
                {{ html()->textarea('excluded_terms', old('excluded_terms', $list($rule?->arrayValue('excluded_terms') ?? [])))->class('form-control')->rows(3) }}
            </div>

            <div class="col-md-6">
                {{ html()->label(__('mediaradar::mediaradar.lbl_keywords'), 'keywords')->class('form-label') }}
                {{ html()->textarea('keywords', old('keywords', $list($rule?->arrayValue('keywords') ?? [])))->class('form-control')->rows(3) }}
                <small class="text-muted">{{ __('mediaradar::mediaradar.lbl_keywords_help') }}</small>
            </div>

            <div class="col-md-6">
                {{ html()->label(__('mediaradar::mediaradar.lbl_actors'), 'actors')->class('form-label') }}
                {{ html()->textarea('actors', old('actors', $list($rule?->arrayValue('actors') ?? [])))->class('form-control')->rows(3) }}
                <small class="text-muted">{{ __('mediaradar::mediaradar.lbl_actors_help') }}</small>
            </div>

            <div class="col-md-4">
                {{ html()->label(__('mediaradar::mediaradar.lbl_content_type'), 'content_type')->class('form-label') }}
                {{ html()->select('content_type', ['' => '-'] + $contentTypes, old('content_type', $rule?->content_type))->class('form-control select2') }}
            </div>

            <div class="col-md-4">
                {{ html()->label(__('mediaradar::mediaradar.lbl_min_duration'), 'min_duration_seconds')->class('form-label') }}
                {{ html()->number('min_duration_seconds', old('min_duration_seconds', $rule?->min_duration_seconds))->class('form-control')->attribute('min', 0)->placeholder('240') }}
            </div>

            <div class="col-md-4">
                {{ html()->label(__('mediaradar::mediaradar.lbl_max_duration'), 'max_duration_seconds')->class('form-label') }}
                {{ html()->number('max_duration_seconds', old('max_duration_seconds', $rule?->max_duration_seconds))->class('form-control')->attribute('min', 0)->placeholder('2700') }}
            </div>

            <div class="col-md-3">
                {{ html()->label(__('mediaradar::mediaradar.lbl_year_from'), 'release_year_from')->class('form-label') }}
                {{ html()->number('release_year_from', old('release_year_from', $rule?->release_year_from))->class('form-control')->placeholder('2020') }}
            </div>

            <div class="col-md-3">
                {{ html()->label(__('mediaradar::mediaradar.lbl_year_to'), 'release_year_to')->class('form-label') }}
                {{ html()->number('release_year_to', old('release_year_to', $rule?->release_year_to))->class('form-control')->placeholder(date('Y')) }}
            </div>

            <div class="col-md-3">
                {{ html()->label(__('mediaradar::mediaradar.lbl_min_quality'), 'min_quality')->class('form-label') }}
                {{ html()->select('min_quality', ['' => '-'] + $qualityOptions, old('min_quality', $rule?->min_quality))->class('form-control select2') }}
            </div>

            <div class="col-md-3">
                {{ html()->label(__('mediaradar::mediaradar.lbl_published_within'), 'published_within_days')->class('form-label') }}
                {{ html()->number('published_within_days', old('published_within_days', $rule?->published_within_days))->class('form-control')->placeholder('14') }}
            </div>

            <div class="col-12">
                <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" id="quality_strict" name="quality_strict" value="1"
                        {{ old('quality_strict', $rule?->quality_strict) ? 'checked' : '' }}>
                    <label class="form-check-label" for="quality_strict">{{ __('mediaradar::mediaradar.lbl_quality_strict') }}</label>
                </div>
                <small class="text-muted">{{ __('mediaradar::mediaradar.lbl_quality_help') }}</small>
            </div>

            <div class="col-md-3">
                {{ html()->label(__('mediaradar::mediaradar.lbl_language'), 'language')->class('form-label') }}
                {{ html()->text('language', old('language', $rule?->language))->class('form-control')->placeholder('en') }}
            </div>

            <div class="col-md-3">
                {{ html()->label(__('mediaradar::mediaradar.lbl_region'), 'region')->class('form-label') }}
                {{ html()->text('region', old('region', $rule?->region))->class('form-control')->placeholder('US') }}
            </div>

            <div class="col-md-3">
                {{ html()->label(__('mediaradar::mediaradar.lbl_min_views'), 'min_view_count')->class('form-label') }}
                {{ html()->number('min_view_count', old('min_view_count', $rule?->min_view_count))->class('form-control')->attribute('min', 0) }}
            </div>

            <div class="col-md-3">
                {{ html()->label(__('mediaradar::mediaradar.lbl_minimum_score'), 'minimum_editorial_score')->class('form-label') }}
                {{ html()->number('minimum_editorial_score', old('minimum_editorial_score', $rule?->minimum_editorial_score ?? 60))->class('form-control')->attribute('min', 0)->attribute('max', 100) }}
            </div>

            <div class="col-md-6">
                {{ html()->label(__('mediaradar::mediaradar.lbl_secondary_genres'), 'secondary_genre_ids')->class('form-label') }}
                <select name="secondary_genre_ids[]" class="form-control select2" multiple>
                    @foreach ($genres as $id => $name)
                        <option value="{{ $id }}" {{ in_array($id, (array) old('secondary_genre_ids', $rule?->secondary_genre_ids ?? []), false) ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                {{ html()->label(__('mediaradar::mediaradar.lbl_creator_id'), 'preferred_creator_ids')->class('form-label') }}
                {{ html()->textarea('preferred_creator_ids', old('preferred_creator_ids', $list($rule?->arrayValue('preferred_creator_ids') ?? [])))->class('form-control')->rows(2) }}
                <small class="text-muted">{{ __('mediaradar::mediaradar.lbl_creator_id_help') }}</small>
            </div>
        </div>
    </div>
</div>

{{-- Destination and approval --}}
<div class="card mb-3">
    <div class="card-header">
        <h5 class="mb-0">{{ __('mediaradar::mediaradar.destination_section') }}</h5>
    </div>
    <div class="card-body">
        <div class="row gy-3">
            <div class="col-md-4">
                {{ html()->label(__('mediaradar::mediaradar.lbl_auto_approve'), 'auto_approve')->class('form-label') }}
                {{ html()->select('auto_approve', [
                    'inherit' => __('mediaradar::mediaradar.inherit_global'),
                    '0' => __('mediaradar::mediaradar.manual_mode'),
                    '1' => __('mediaradar::mediaradar.auto_mode'),
                ], $autoApprove)->class('form-control select2') }}
            </div>

            <div class="col-md-4">
                {{ html()->label(__('mediaradar::mediaradar.lbl_access'), 'destination_movie_access')->class('form-label') }}
                {{ html()->select('destination_movie_access', [
                    'free' => 'Free',
                    'paid' => 'Paid',
                    'pay-per-view' => 'Pay per view',
                ], old('destination_movie_access', $rule?->destination_movie_access ?? 'free'))->class('form-control select2') }}
            </div>

            <div class="col-md-4">
                {{ html()->label(__('mediaradar::mediaradar.lbl_plan'), 'destination_plan_id')->class('form-label') }}
                {{ html()->select('destination_plan_id', ['' => '-'] + $plans->toArray(), old('destination_plan_id', $rule?->destination_plan_id))->class('form-control select2') }}
            </div>

            <div class="col-12">
                <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" id="destination_is_restricted" name="destination_is_restricted" value="1"
                        {{ old('destination_is_restricted', $rule?->destination_is_restricted) ? 'checked' : '' }}>
                    <label class="form-check-label" for="destination_is_restricted">{{ __('mediaradar::mediaradar.lbl_restricted') }}</label>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Schedule --}}
<div class="card mb-3">
    <div class="card-header">
        <h5 class="mb-0">{{ __('mediaradar::mediaradar.schedule_section') }}</h5>
    </div>
    <div class="card-body">
        <div class="row gy-3">
            <div class="col-md-4">
                {{ html()->label(__('mediaradar::mediaradar.lbl_schedule_type'), 'schedule_type')->class('form-label') }}
                {{ html()->select('schedule_type', $scheduleTypes, old('schedule_type', $rule?->schedule_type ?? 'interval'))->class('form-control select2')->id('schedule_type') }}
            </div>

            <div class="col-md-4" id="interval_wrapper">
                {{ html()->label(__('mediaradar::mediaradar.lbl_interval_hours'), 'interval_hours')->class('form-label') }}
                {{ html()->number('interval_hours', old('interval_hours', $rule?->interval_hours ?? 6))->class('form-control')->attribute('min', 1) }}
            </div>

            <div class="col-md-4">
                {{ html()->label(__('mediaradar::mediaradar.lbl_priority'), 'priority')->class('form-label') }}
                {{ html()->number('priority', old('priority', $rule?->priority ?? 0))->class('form-control') }}
            </div>

            <div class="col-12">
                <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" id="enabled" name="enabled" value="1"
                        {{ old('enabled', $rule === null ? true : $rule->enabled) ? 'checked' : '' }}>
                    <label class="form-check-label" for="enabled">{{ __('mediaradar::mediaradar.enabled') }}</label>
                </div>
            </div>
        </div>
    </div>
</div>
