@extends('backend.layouts.app')

@section('title'){{ __('messages.veemag_print') }}@endsection

@section('content')
@php
    $currency = strtoupper((string) GetcurrentCurrency() ?: 'USD');
    $symbol = $currency === 'USD' ? '$' : $currency . ' ';
    $defaultPrice = (float) ($settings['veemag_print_price'] ?: 20);
@endphp

<div class="card-main mb-4 p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">{{ __('messages.veemag_print') }}</h3>
    </div>

    <div class="alert alert-info">
        {{ __('messages.veemag_print_intro') }}
    </div>

    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    @endif

    <form method="post" action="{{ route('backend.veemag-print.settings') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label small">{{ __('messages.veemag_print_default_price') }} ({{ $currency }})</label>
                <input class="form-control" name="veemag_print_price" type="number" step="0.01" min="0"
                    value="{{ old('veemag_print_price', $settings['veemag_print_price']) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small">{{ __('messages.veemag_print_ship_standard') }}</label>
                <input class="form-control" name="veemag_print_ship_standard" type="number" step="0.01" min="0"
                    value="{{ old('veemag_print_ship_standard', $settings['veemag_print_ship_standard']) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small">{{ __('messages.veemag_print_ship_express') }}</label>
                <input class="form-control" name="veemag_print_ship_express" type="number" step="0.01" min="0"
                    value="{{ old('veemag_print_ship_express', $settings['veemag_print_ship_express']) }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small">{{ __('messages.veemag_print_countries') }}</label>
                <input class="form-control" name="veemag_print_ship_countries"
                    value="{{ old('veemag_print_ship_countries', $settings['veemag_print_ship_countries']) }}"
                    placeholder="US, CA, GB, AU">
            </div>
        </div>
        <button class="btn btn-primary mt-3" type="submit">
            <i class="ph ph-floppy-disk"></i> {{ __('messages.save') }}
        </button>
    </form>
</div>

<div class="card-main mb-4 p-4">
    <h5 class="mb-3">{{ __('messages.veemag_print_issues') }}</h5>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>{{ __('messages.title') }}</th>
                    <th>{{ __('messages.veemag_print_publication') }}</th>
                    <th style="width:11rem;">{{ __('messages.veemag_print_price_col') }}</th>
                    <th style="width:9rem;">{{ __('messages.status') }}</th>
                    <th style="width:11rem;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($issues as $issue)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $issue->title }}</div>
                            <div class="text-muted small">{{ $issue->issue_label }}</div>
                        </td>
                        <td class="text-muted">{{ $issue->publication->title ?? '—' }}</td>
                        <td>
                            <form method="post" action="{{ route('backend.veemag-print.price', $issue->id) }}"
                                class="d-flex gap-1">
                                @csrf
                                <input class="form-control form-control-sm" name="print_price" type="number"
                                    step="0.01" min="0" value="{{ $issue->print_price }}"
                                    placeholder="{{ number_format($defaultPrice, 2) }}">
                                <button class="btn btn-sm btn-outline-secondary" type="submit"
                                    title="{{ __('messages.save') }}"><i class="ph ph-check"></i></button>
                            </form>
                        </td>
                        <td>
                            @if($issue->print_enabled)
                                <span class="badge text-bg-success">{{ __('messages.veemag_print_enabled') }}</span>
                            @else
                                <span class="badge text-bg-secondary">{{ __('messages.veemag_print_disabled') }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <form method="post" action="{{ route('backend.veemag-print.toggle', $issue->id) }}">
                                @csrf
                                <button class="btn btn-sm {{ $issue->print_enabled ? 'btn-outline-danger' : 'btn-outline-primary' }}"
                                    type="submit">
                                    <i class="ph ph-printer"></i>
                                    {{ $issue->print_enabled ? __('messages.veemag_print_turn_off') : __('messages.veemag_print_turn_on') }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">{{ __('messages.no_record') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card-main p-4">
    <h5 class="mb-3">{{ __('messages.veemag_print_orders') }}</h5>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ __('messages.veemag_print_issue') }}</th>
                    <th>{{ __('messages.veemag_print_email') }}</th>
                    <th>{{ __('messages.veemag_print_amount') }}</th>
                    <th>{{ __('messages.status') }}</th>
                    <th>{{ __('messages.created_at') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td class="text-muted">{{ $order->id }}</td>
                        <td>{{ $order->issue->title ?? '—' }}</td>
                        <td class="text-muted">{{ $order->email }}</td>
                        <td>{{ $symbol }}{{ number_format((float) $order->amount, 2) }}</td>
                        <td>
                            @php
                                $map = ['paid' => 'success', 'pending' => 'warning', 'failed' => 'danger', 'cancelled' => 'secondary'];
                                $tone = $map[$order->status] ?? 'secondary';
                            @endphp
                            <span class="badge text-bg-{{ $tone }}">{{ ucfirst($order->status) }}</span>
                        </td>
                        <td class="text-muted small">{{ $order->created_at?->format('d M Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">{{ __('messages.no_record') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
