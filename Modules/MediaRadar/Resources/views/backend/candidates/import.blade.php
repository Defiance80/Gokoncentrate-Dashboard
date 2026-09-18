@extends('backend.layouts.app')

@section('title')
    {{ __('mediaradar::mediaradar.import') }}
@endsection

@section('content')
    <div class="card-main mb-5">
        <x-backend.section-header>
            <h4 class="mb-0">{{ __('mediaradar::mediaradar.import_heading') }}</h4>
        </x-backend.section-header>

        <div class="card-body">
            <p class="text-secondary mb-4" style="max-width:60ch">
                {{ __('mediaradar::mediaradar.import_intro') }}
            </p>

            <form method="POST" action="{{ route('backend.media-radar-candidates.import.store') }}" id="km-import-form">
                @csrf

                {{-- URL + fetch --}}
                <div class="row g-3 align-items-end mb-4">
                    <div class="col-md-9">
                        <label for="km-import-url" class="form-label">{{ __('mediaradar::mediaradar.import_url_label') }}</label>
                        <input type="text" name="url" id="km-import-url" class="form-control"
                            placeholder="https://youtu.be/…  {{ __('mediaradar::mediaradar.or') }}  https://vimeo.com/…"
                            value="{{ old('url') }}" required>
                        <span class="text-danger small">@error('url'){{ $message }}@enderror</span>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-secondary w-100" id="km-fetch-btn">
                            {{ __('mediaradar::mediaradar.import_fetch') }}
                        </button>
                    </div>
                </div>

                {{-- Metadata preview (populated by the fetch button) --}}
                <div id="km-preview" class="d-none mb-4">
                    <div class="d-flex flex-wrap gap-4 p-3" style="border:1px solid var(--bs-border-color);border-radius:.5rem">
                        <img id="km-preview-thumb" src="" alt="" width="240"
                            style="border-radius:.375rem;object-fit:cover;max-width:100%">
                        <div style="flex:1;min-width:16rem">
                            <span class="badge bg-primary text-uppercase mb-2" id="km-preview-provider"></span>
                            <h5 id="km-preview-title" class="mb-1"></h5>
                            <p class="text-secondary small mb-2" id="km-preview-creator"></p>
                            <p class="text-secondary small mb-0" id="km-preview-desc"></p>
                            <p class="text-warning small mb-0 mt-2 d-none" id="km-preview-warn"></p>
                        </div>
                    </div>
                </div>

                {{-- Destination: which section of the media stand it appears in --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="km-section" class="form-label">Category (where it appears) <span class="text-danger">*</span></label>
                        <select name="target_section" id="km-section" class="form-control" style="width:100%" required>
                            @php $sections = ['short_film' => 'Short Films', 'tvshow' => 'TV Shows', 'podcast' => 'Media Series (Podcast)', 'music' => 'Music']; @endphp
                            @foreach ($sections as $val => $label)
                                <option value="{{ $val }}" @selected(old('target_section', 'short_film') == $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="text-secondary small">The GoKoncentrate section this video is published into. (VeeMags are built as multi-section issues in Publisher Studio; Music arrives with the Music section.)</span>
                        <span class="text-danger small">@error('target_section'){{ $message }}@enderror</span>
                    </div>
                </div>

                {{-- Music sub-category: only relevant when Category = Music --}}
                <div class="row g-3 mb-4" id="km-music-wrap" style="display:none;">
                    <div class="col-md-6">
                        <label for="km-music-genre" class="form-label">Music sub-category <span class="text-danger">*</span></label>
                        <select name="music_genre_id" id="km-music-genre" class="form-control select2" style="width:100%">
                            <option value="">Choose a style…</option>
                            @foreach ($musicGenres as $id => $name)
                                <option value="{{ $id }}" @selected(old('music_genre_id') == $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        <span class="text-secondary small">Hip Hop, R&amp;B, Rock &amp; Roll, Jazz, Country, Soul, Funk, Reggae, Pop, EDM…</span>
                        <span class="text-danger small">@error('music_genre_id'){{ $message }}@enderror</span>
                    </div>
                </div>

                {{-- Genre (type) + sub-genres (hidden for Music) --}}
                <div class="row g-3 mb-4" id="km-genre-wrap">
                    <div class="col-md-6">
                        <label for="km-genre" class="form-label">Genre (type) <span class="text-danger">*</span></label>
                        <select name="genre_id" id="km-genre" class="form-control select2" style="width:100%" required>
                            <option value="">{{ __('mediaradar::mediaradar.import_genre_placeholder') }}</option>
                            @foreach ($primaryGenres as $id => $name)
                                <option value="{{ $id }}" @selected(old('genre_id') == $id)>{{ $name }}</option>
                            @endforeach
                        </select>
                        <span class="text-secondary small">Drama, Documentary, Action, Horror, Sci-Fi, Romance, Thriller, Suspense.</span>
                        <span class="text-danger small">@error('genre_id'){{ $message }}@enderror</span>
                    </div>
                    <div class="col-md-6">
                        <label for="km-secondary" class="form-label">Sub-genres (optional)</label>
                        <select name="secondary_genre_ids[]" id="km-secondary" class="form-control select2" multiple style="width:100%">
                            @foreach ($subGenres as $id => $name)
                                <option value="{{ $id }}" @selected(collect(old('secondary_genre_ids'))->contains($id))>{{ $name }}</option>
                            @endforeach
                        </select>
                        <span class="text-secondary small">Interview, Lifecast, Cipher Session, etc.</span>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label for="km-title" class="form-label">{{ __('mediaradar::mediaradar.import_title_label') }}</label>
                        <input type="text" name="editorial_title" id="km-title" class="form-control"
                            placeholder="{{ __('mediaradar::mediaradar.import_title_placeholder') }}" value="{{ old('editorial_title') }}">
                        <span class="text-secondary small">{{ __('mediaradar::mediaradar.import_title_help') }}</span>
                    </div>
                </div>

                <div class="d-flex gap-3">
                    <button type="submit" class="btn btn-primary">{{ __('mediaradar::mediaradar.import_submit') }}</button>
                    <a href="{{ route('backend.media-radar-candidates.index') }}" class="btn btn-outline-secondary">{{ __('messages.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            // Show the Music sub-category (and hide the type genre) when Category = Music.
            (function () {
                const section = document.getElementById('km-section');
                const musicWrap = document.getElementById('km-music-wrap');
                const genreWrap = document.getElementById('km-genre-wrap');
                function sync() {
                    const isMusic = section && section.value === 'music';
                    if (musicWrap) musicWrap.style.display = isMusic ? '' : 'none';
                    if (genreWrap) genreWrap.style.display = isMusic ? 'none' : '';
                }
                if (section) { section.addEventListener('change', sync); sync(); }
            })();
        </script>
        <script>
            (function () {
                const btn = document.getElementById('km-fetch-btn');
                const urlInput = document.getElementById('km-import-url');
                const box = document.getElementById('km-preview');
                const titleInput = document.getElementById('km-title');

                function fmt(sec) {
                    if (!sec) return '';
                    const m = Math.floor(sec / 60), s = sec % 60;
                    return ' · ' + m + 'm ' + (s < 10 ? '0' : '') + s + 's';
                }

                btn.addEventListener('click', async function () {
                    const url = urlInput.value.trim();
                    if (!url) { urlInput.focus(); return; }
                    btn.disabled = true;
                    btn.textContent = @json(__('mediaradar::mediaradar.import_fetching'));

                    try {
                        const res = await fetch(@json(route('backend.media-radar-candidates.import.preview')), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ url })
                        });
                        const json = await res.json();

                        if (!json.status) {
                            box.classList.remove('d-none');
                            document.getElementById('km-preview-title').textContent = json.message || 'Could not fetch this video.';
                            document.getElementById('km-preview-desc').textContent = '';
                            document.getElementById('km-preview-creator').textContent = '';
                            document.getElementById('km-preview-provider').textContent = 'error';
                            document.getElementById('km-preview-thumb').src = '';
                            return;
                        }

                        const d = json.data;
                        box.classList.remove('d-none');
                        document.getElementById('km-preview-provider').textContent = d.provider;
                        document.getElementById('km-preview-title').textContent = d.title || '(untitled)';
                        document.getElementById('km-preview-creator').textContent = (d.creator || '') + fmt(d.duration);
                        document.getElementById('km-preview-desc').textContent = d.description || '';
                        document.getElementById('km-preview-thumb').src = d.thumbnail || '';
                        const warn = document.getElementById('km-preview-warn');
                        if (d.embeddable === false) {
                            warn.classList.remove('d-none');
                            warn.textContent = @json(__('mediaradar::mediaradar.import_not_embeddable'));
                        } else {
                            warn.classList.add('d-none');
                        }
                        if (!titleInput.value && d.title) { titleInput.value = d.title; }
                    } catch (e) {
                        box.classList.remove('d-none');
                        document.getElementById('km-preview-title').textContent = 'Network error — try again.';
                    } finally {
                        btn.disabled = false;
                        btn.textContent = @json(__('mediaradar::mediaradar.import_fetch'));
                    }
                });
            })();
        </script>
    @endpush
@endsection
