<?php

namespace Modules\Tokens\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Trait\ModuleTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Tokens\Http\Requests\TokenAdjustmentRequest;
use Modules\Tokens\Models\TokenLedger;
use Modules\Tokens\Models\TokenSetting;
use Yajra\DataTables\DataTables;

class TokenUsersController extends Controller
{
    use ModuleTrait {
        initializeModuleTrait as private traitInitializeModuleTrait;
    }

    public function __construct()
    {
        $this->module_name = 'token-users';
        $this->traitInitializeModuleTrait(
            'tokens::tokens.users_title',
            'token-users',
            'ph ph-users'
        );
    }

    /**
     * Display user list with token balances
     */
    public function index(Request $request)
    {
        $settings = TokenSetting::getInstance();
        $module_action = 'List';

        $filter = [
            'earning_status' => $request->earning_status,
        ];

        return view('tokens::backend.users.index', compact('module_action', 'settings', 'filter'));
    }

    /**
     * DataTables data source
     */
    public function index_data(DataTables $datatable, Request $request)
    {
        $query = User::where('user_type', 'user')
            ->select([
                'id', 'first_name', 'last_name', 'email',
                'token_balance_cents', 'earning_suspended',
                'last_earned_at', 'last_spent_at', 'status'
            ]);

        $filter = $request->filter;

        // Filter by earning status
        if (isset($filter['earning_status']) && $filter['earning_status'] !== '') {
            if ($filter['earning_status'] === 'active') {
                $query->where('earning_suspended', false);
            } elseif ($filter['earning_status'] === 'suspended') {
                $query->where('earning_suspended', true);
            }
        }

        return $datatable->eloquent($query)
            ->addColumn('check', function ($data) {
                return '<input type="checkbox" class="form-check-input select-table-row" id="datatable-row-'.$data->id.'" name="datatable_ids[]" value="'.$data->id.'" onclick="dataTableRowCheck('.$data->id.')">';
            })
            ->addColumn('name', function ($data) {
                return $data->first_name . ' ' . $data->last_name;
            })
            ->addColumn('token_balance', function ($data) {
                $balance = number_format(($data->token_balance_cents ?? 0) / 100, 2, '.', '');
                return '<span class="badge bg-warning text-dark fs-6">KT ' . $balance . '</span>';
            })
            ->addColumn('earning_status', function ($data) {
                if ($data->earning_suspended) {
                    return '<span class="badge bg-danger">' . __('tokens::tokens.status_suspended') . '</span>';
                }
                return '<span class="badge bg-success">' . __('tokens::tokens.status_active') . '</span>';
            })
            ->editColumn('last_earned_at', function ($data) {
                return $data->last_earned_at
                    ? Carbon::parse($data->last_earned_at)->diffForHumans()
                    : '-';
            })
            ->editColumn('last_spent_at', function ($data) {
                return $data->last_spent_at
                    ? Carbon::parse($data->last_spent_at)->diffForHumans()
                    : '-';
            })
            ->addColumn('action', function ($data) {
                return '<a href="'.route('backend.token-users.details', $data->id).'" class="btn btn-soft-primary btn-sm" title="'.__('tokens::tokens.btn_view_details').'">
                    <i class="ph ph-eye"></i>
                </a>';
            })
            ->rawColumns(['check', 'token_balance', 'earning_status', 'action'])
            ->orderColumns(['id'], '-:column $1')
            ->make(true);
    }

    /**
     * Show user token details
     */
    public function details($id)
    {
        $user = User::findOrFail($id);
        $settings = TokenSetting::getInstance();
        $isSuperAdmin = auth()->user()->hasRole('admin');
        $module_action = 'Details';

        // Calculate lifetime stats
        $lifetimeEarned = TokenLedger::forUser($id)
            ->where('amount_cents', '>', 0)
            ->whereIn('type', [TokenLedger::TYPE_EARNED, TokenLedger::TYPE_ADMIN_CREDIT, TokenLedger::TYPE_REFUND])
            ->sum('amount_cents');

        $lifetimeSpent = abs(TokenLedger::forUser($id)
            ->where('amount_cents', '<', 0)
            ->whereIn('type', [TokenLedger::TYPE_SPENT, TokenLedger::TYPE_ADMIN_DEBIT])
            ->sum('amount_cents'));

        // Get ledger with pagination
        $ledger = TokenLedger::forUser($id)
            ->with('admin:id,first_name,last_name')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('tokens::backend.users.details', compact(
            'user', 'settings', 'isSuperAdmin', 'module_action',
            'lifetimeEarned', 'lifetimeSpent', 'ledger'
        ));
    }

    /**
     * Add tokens to user
     */
    public function addTokens(TokenAdjustmentRequest $request, $id)
    {
        if (env('IS_DEMO')) {
            return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
        }

        $user = User::findOrFail($id);
        $amountCents = abs($request->validated()['amount_cents']);
        $reason = $request->validated()['reason'] ?? 'Admin credit';

        $this->recordLedgerEntry(
            $user,
            $amountCents,
            TokenLedger::TYPE_ADMIN_CREDIT,
            'admin_panel',
            $reason
        );

        return response()->json([
            'status' => true,
            'message' => __('tokens::tokens.tokens_added'),
            'new_balance' => $user->fresh()->token_balance,
            'new_balance_cents' => $user->fresh()->token_balance_cents,
        ]);
    }

    /**
     * Deduct tokens from user
     */
    public function deductTokens(TokenAdjustmentRequest $request, $id)
    {
        if (env('IS_DEMO')) {
            return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
        }

        $user = User::findOrFail($id);
        $amountCents = abs($request->validated()['amount_cents']);
        $reason = $request->validated()['reason'] ?? 'Admin debit';

        // Ensure we don't go negative
        if ($amountCents > $user->token_balance_cents) {
            return response()->json([
                'status' => false,
                'message' => __('tokens::tokens.insufficient_balance'),
            ], 422);
        }

        $this->recordLedgerEntry(
            $user,
            -$amountCents,
            TokenLedger::TYPE_ADMIN_DEBIT,
            'admin_panel',
            $reason
        );

        return response()->json([
            'status' => true,
            'message' => __('tokens::tokens.tokens_deducted'),
            'new_balance' => $user->fresh()->token_balance,
            'new_balance_cents' => $user->fresh()->token_balance_cents,
        ]);
    }

    /**
     * Set user's token balance (super admin only)
     */
    public function setBalance(TokenAdjustmentRequest $request, $id)
    {
        if (env('IS_DEMO')) {
            return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
        }

        if (!auth()->user()->hasRole('admin')) {
            return response()->json([
                'status' => false,
                'message' => __('tokens::tokens.permission_denied'),
            ], 403);
        }

        $user = User::findOrFail($id);
        $newBalanceCents = abs($request->validated()['amount_cents']);
        $reason = $request->validated()['reason'] ?? 'Admin balance set';
        $oldBalance = $user->token_balance_cents;

        // Calculate the adjustment
        $adjustmentCents = $newBalanceCents - $oldBalance;

        $this->recordLedgerEntry(
            $user,
            $adjustmentCents,
            TokenLedger::TYPE_ADMIN_SET,
            'admin_panel',
            $reason,
            ['previous_balance_cents' => $oldBalance, 'new_balance_cents' => $newBalanceCents]
        );

        return response()->json([
            'status' => true,
            'message' => __('tokens::tokens.balance_set'),
            'new_balance' => $user->fresh()->token_balance,
            'new_balance_cents' => $user->fresh()->token_balance_cents,
        ]);
    }

    /**
     * Suspend user earning
     */
    public function suspendEarning(Request $request, $id)
    {
        if (env('IS_DEMO')) {
            return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
        }

        $user = User::findOrFail($id);
        $reason = $request->input('reason', '');

        $user->update([
            'earning_suspended' => true,
            'earning_suspended_reason' => $reason,
        ]);

        return response()->json([
            'status' => true,
            'message' => __('tokens::tokens.earning_suspended'),
        ]);
    }

    /**
     * Unsuspend user earning
     */
    public function unsuspendEarning($id)
    {
        if (env('IS_DEMO')) {
            return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
        }

        $user = User::findOrFail($id);

        $user->update([
            'earning_suspended' => false,
            'earning_suspended_reason' => null,
        ]);

        return response()->json([
            'status' => true,
            'message' => __('tokens::tokens.earning_unsuspended'),
        ]);
    }

    /**
     * Record a ledger entry and update user balance
     */
    protected function recordLedgerEntry(
        User $user,
        int $amountCents,
        string $type,
        string $source,
        ?string $reason = null,
        ?array $metadata = null
    ): TokenLedger {
        // Update user balance
        $newBalance = $user->token_balance_cents + $amountCents;
        $user->update(['token_balance_cents' => $newBalance]);

        // Update timestamp based on type
        if (in_array($type, [TokenLedger::TYPE_EARNED])) {
            $user->update(['last_earned_at' => now()]);
        } elseif (in_array($type, [TokenLedger::TYPE_SPENT])) {
            $user->update(['last_spent_at' => now()]);
        }

        // Create ledger entry
        return TokenLedger::create([
            'user_id' => $user->id,
            'amount_cents' => $amountCents,
            'type' => $type,
            'source' => $source,
            'reason' => $reason,
            'admin_id' => auth()->id(),
            'metadata' => $metadata,
            'balance_after_cents' => $newBalance,
        ]);
    }
}
