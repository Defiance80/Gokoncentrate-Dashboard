@extends('backend.layouts.app')

@section('title') {{ __('tokens::tokens.user_details_title') }} - {{ $user->first_name }} {{ $user->last_name }} @endsection

@section('content')
<div class="row">
    <!-- User Info Card -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="ph ph-user me-2"></i>{{ $user->first_name }} {{ $user->last_name }}</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-2">{{ $user->email }}</p>

                <!-- Earning Status Badge -->
                @if($user->earning_suspended)
                    <span class="badge bg-danger mb-3">{{ __('tokens::tokens.status_suspended') }}</span>
                    @if($user->earning_suspended_reason)
                        <p class="small text-muted">{{ $user->earning_suspended_reason }}</p>
                    @endif
                @else
                    <span class="badge bg-success mb-3">{{ __('tokens::tokens.status_active') }}</span>
                @endif

                <hr>

                <!-- Admin Actions -->
                <h6>{{ __('tokens::tokens.section_actions') }}</h6>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addTokensModal">
                        <i class="ph ph-plus-circle me-1"></i>{{ __('tokens::tokens.btn_add_tokens') }}
                    </button>
                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#deductTokensModal">
                        <i class="ph ph-minus-circle me-1"></i>{{ __('tokens::tokens.btn_deduct_tokens') }}
                    </button>
                    @if($isSuperAdmin)
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#setBalanceModal">
                        <i class="ph ph-equals me-1"></i>{{ __('tokens::tokens.btn_set_balance') }}
                    </button>
                    @endif

                    @if($user->earning_suspended)
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="unsuspendEarning()">
                            <i class="ph ph-play me-1"></i>{{ __('tokens::tokens.btn_unsuspend') }}
                        </button>
                    @else
                        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#suspendModal">
                            <i class="ph ph-pause me-1"></i>{{ __('tokens::tokens.btn_suspend') }}
                        </button>
                    @endif
                </div>

                <hr>

                <a href="{{ route('backend.token-users.index') }}" class="btn btn-secondary btn-sm w-100">
                    <i class="ph ph-arrow-left me-1"></i>{{ __('messages.back') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Token Stats & Ledger -->
    <div class="col-md-8">
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center">
                        <h6 class="card-title">{{ __('tokens::tokens.lbl_current_balance') }}</h6>
                        <h2 class="mb-0" id="current-balance">{{ $settings->token_label }} {{ number_format($user->token_balance_cents / 100, 2) }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h6 class="card-title">{{ __('tokens::tokens.lbl_lifetime_earned') }}</h6>
                        <h2 class="mb-0">{{ $settings->token_label }} {{ number_format($lifetimeEarned / 100, 2) }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h6 class="card-title">{{ __('tokens::tokens.lbl_lifetime_spent') }}</h6>
                        <h2 class="mb-0">{{ $settings->token_label }} {{ number_format($lifetimeSpent / 100, 2) }}</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ledger Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="ph ph-list-bullets me-2"></i>{{ __('tokens::tokens.section_ledger') }}</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('tokens::tokens.lbl_date') }}</th>
                                <th>{{ __('tokens::tokens.lbl_type') }}</th>
                                <th>{{ __('tokens::tokens.lbl_amount') }}</th>
                                <th>{{ __('tokens::tokens.lbl_source') }}</th>
                                <th>{{ __('tokens::tokens.lbl_reason') }}</th>
                                <th>{{ __('tokens::tokens.lbl_admin') }}</th>
                                <th>{{ __('tokens::tokens.lbl_balance_after') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ledger as $entry)
                            <tr>
                                <td>{{ $entry->created_at->format('Y-m-d H:i') }}</td>
                                <td>{!! $entry->type_badge !!}</td>
                                <td class="{{ $entry->amount_cents >= 0 ? 'text-success' : 'text-danger' }}">
                                    <strong>{{ $entry->formatted_amount }}</strong>
                                </td>
                                <td>{{ $entry->source ?? '-' }}</td>
                                <td>{{ $entry->reason ?? '-' }}</td>
                                <td>{{ $entry->admin ? $entry->admin->first_name . ' ' . $entry->admin->last_name : '-' }}</td>
                                <td>{{ $settings->token_label }} {{ $entry->formatted_balance_after }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">{{ __('tokens::tokens.no_transactions') }}</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center">
                    {{ $ledger->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Tokens Modal -->
<div class="modal fade" id="addTokensModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('tokens::tokens.btn_add_tokens') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addTokensForm">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('tokens::tokens.lbl_amount') }} (cents) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="amount_cents" min="1" required>
                        <small class="text-muted">100 cents = 1 {{ $settings->token_label }}</small>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('tokens::tokens.lbl_reason') }}</label>
                        <input type="text" class="form-control" name="reason" maxlength="255">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('tokens::tokens.btn_add_tokens') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Deduct Tokens Modal -->
<div class="modal fade" id="deductTokensModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('tokens::tokens.btn_deduct_tokens') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="deductTokensForm">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="ph ph-warning me-1"></i>{{ __('tokens::tokens.deduct_warning') }}
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('tokens::tokens.lbl_amount') }} (cents) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="amount_cents" min="1" max="{{ $user->token_balance_cents }}" required>
                        <small class="text-muted">{{ __('tokens::tokens.max_deduct') }}: {{ $user->token_balance_cents }} cents</small>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('tokens::tokens.lbl_reason') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="reason" maxlength="255" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-warning">{{ __('tokens::tokens.btn_deduct_tokens') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($isSuperAdmin)
<!-- Set Balance Modal -->
<div class="modal fade" id="setBalanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('tokens::tokens.btn_set_balance') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="setBalanceForm">
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="ph ph-warning me-1"></i>{{ __('tokens::tokens.set_balance_warning') }}
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('tokens::tokens.lbl_new_balance') }} (cents) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="amount_cents" min="0" value="{{ $user->token_balance_cents }}" required>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('tokens::tokens.lbl_reason') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="reason" maxlength="255" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('tokens::tokens.btn_set_balance') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Suspend Modal -->
<div class="modal fade" id="suspendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('tokens::tokens.btn_suspend') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="suspendForm">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('tokens::tokens.lbl_reason') }}</label>
                        <input type="text" class="form-control" name="reason" maxlength="255">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('tokens::tokens.btn_suspend') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('after-scripts')
<script>
const userId = {{ $user->id }};
const csrfToken = '{{ csrf_token() }}';

function handleFormSubmit(formId, url, modalId) {
    const form = document.getElementById(formId);
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);

        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                bootstrap.Modal.getInstance(document.getElementById(modalId)).hide();
                Swal.fire({
                    title: '{{ __("messages.success") }}',
                    text: data.message,
                    icon: 'success',
                    showConfirmButton: false,
                    timer: 2000
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    title: '{{ __("messages.error") }}',
                    text: data.message,
                    icon: 'error'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: '{{ __("messages.error") }}',
                text: '{{ __("messages.something_wrong") }}',
                icon: 'error'
            });
        });
    });
}

function unsuspendEarning() {
    Swal.fire({
        title: '{{ __("tokens::tokens.confirm_unsuspend") }}',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '{{ __("messages.yes") }}',
        cancelButtonText: '{{ __("messages.cancel") }}'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('{{ route("backend.token-users.unsuspend_earning", $user->id) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    Swal.fire({
                        title: '{{ __("messages.success") }}',
                        text: data.message,
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 2000
                    }).then(() => {
                        location.reload();
                    });
                }
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    handleFormSubmit('addTokensForm', '{{ route("backend.token-users.add_tokens", $user->id) }}', 'addTokensModal');
    handleFormSubmit('deductTokensForm', '{{ route("backend.token-users.deduct_tokens", $user->id) }}', 'deductTokensModal');
    @if($isSuperAdmin)
    handleFormSubmit('setBalanceForm', '{{ route("backend.token-users.set_balance", $user->id) }}', 'setBalanceModal');
    @endif
    handleFormSubmit('suspendForm', '{{ route("backend.token-users.suspend_earning", $user->id) }}', 'suspendModal');
});
</script>
@endpush
