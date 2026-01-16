<?php

namespace Modules\User\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\User\Transformers\UserProfileResource;
use App\Models\User;
use App\Models\Device;
use Modules\Subscriptions\Models\Subscription;
use Modules\Entertainment\Models\ContinueWatch;
use Modules\Entertainment\Models\Watchlist;
use Modules\Entertainment\Models\EntertainmentDownload;
use Modules\Entertainment\Models\UserReminder;
use Modules\User\Transformers\AccountSettingResource;
use App\Models\UserMultiProfile;
use App\Models\Role;
use Modules\Page\Models\Page;
use App\Models\UserWatchHistory;
use Illuminate\Support\Facades\DB;
use Modules\User\Transformers\UserProfileResourceNew;
use Modules\User\Transformers\UserProfileResourceV2;
use Modules\User\Transformers\UserProfileResourceV3;

class UserController extends Controller
{
    public function profileDetails(Request $request){
        $userId = $request->user_id ? $request->user_id : auth()->user()->id;

        $user = User::with('subscriptionPackage', 'watchList', 'continueWatch')->where('id', $userId)->first();

        if($user->is_subscribe == 1){
            $user['plan_details'] = $user->subscriptionPackage;
        }

        $responseData = new UserProfileResource($user);

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('users.user_details'),
        ], 200);
    }


    public function accountSetting(Request $request)
    {
        $userId = auth()->user()->id;
        $user = User::with('subscriptionPackage')->where('id', $userId)->first();
        $devices = Device::where('user_id', $userId)->get();

        $your_device = null;
        $other_device = [];

        // Prefer explicit device_id from request, then current token name, finally subscription device
        if ($request->filled('device_id')) {
            $currentDeviceId = $request->device_id;
        } elseif ($request->user() && $request->user()->currentAccessToken()) {
            $currentDeviceId = $request->user()->currentAccessToken()->name;
        } else {
            $currentDeviceId = $user->subscriptionPackage->device_id ?? null;
        }

        if ($devices->isNotEmpty()) {
            foreach ($devices as $device) {
                if ($currentDeviceId && $device->device_id === $currentDeviceId) {
                    $your_device = $device;
                } else {
                    $other_device[] = $device;
                }
            }

            // Fallback only when we truly don't know the current device (no id info)
            if (!$your_device && !$request->filled('device_id')) {
                $your_device = $devices->sortByDesc('updated_at')->first();
                $other_device = $devices->where('id', '!=', optional($your_device)->id)->values();
            }
        }

        $user['your_device']= $your_device;
        $user['other_device']= $other_device;

        // $user['page'] =  Page::where('status',1)->get();


        if ($user->is_subscribe == 1) {
            $plan_details = $user->subscriptionPackage;
            $plan_details['start_date'] = formatDate($plan_details['start_date']);
            $plan_details['end_date'] = formatDate($plan_details['end_date']);
            $user->plan_details = $plan_details;
        }

        $responseData = new AccountSettingResource($user);

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('users.account_setting'),
        ], 200);
    }
  public function deviceLogout(Request $request)
{

    $userId = auth()->check() ? auth()->user()->id : $request->input('user_id');

    $deviceQuery = Device::where('user_id', $userId);

    if ($request->has('device_id')) {
        $deviceQuery->where('device_id', $request->device_id);
    }

    if ($request->has('id')) {
        $deviceQuery->orWhere('id', $request->id);
    }

    $device = $deviceQuery->first();

    if (!$device) {
        return response()->json([
            'status' => false,
            'message' => __('users.device_not_found'), // Change the message to suit your needs
        ], 404);
    }

    $deviceIdToLogout = $device->device_id;

    // Revoke sanctum tokens for this specific device (token name = device_id)
    try {
        $user = \App\Models\User::find($userId);
        if ($user && class_exists('Laravel\\Sanctum\\PersonalAccessToken')) {
            \Laravel\Sanctum\PersonalAccessToken::where('tokenable_id', $userId)
                ->where('name', $deviceIdToLogout)
                ->delete();
        }
    } catch (\Throwable $e) {
        // best-effort; ignore errors revoking tokens
    }

    $device->delete();

    try {
        DB::table('sessions')
            ->where('user_id', $userId)
            ->where('ip_address', $deviceIdToLogout)
            ->delete();
    } catch (\Throwable $e) {
        // ignore if sessions table not present
    }

    return response()->json([
        'status' => true,
        'message' => __('users.device_logout'),
    ], 200);
}

    public function deleteAccount(Request $request){
        $userId = auth()->user()->id;

        User::where('id', $userId)->forceDelete();
        Device::where('user_id', $userId)->delete();
        Subscription::where('user_id', $userId)->update(['status' => 'deactivated']);
        ContinueWatch::where('user_id', $userId)->delete();
        Watchlist::where('user_id', $userId)->delete();
        EntertainmentDownload::where('user_id', $userId)->delete();
        UserReminder::where('user_id', $userId)->delete();
        UserMultiProfile::where('user_id', $userId)->forceDelete();

        return response()->json([
            'status' => true,
            'message' => __('users.delete_account'),
        ], 200);
    }

    public function logoutAll(Request $request){

        $userId = auth()->check() ? auth()->user()->id : $request->input('user_id');

        $device = Device::where('user_id', $userId)->where('device_id','!=', $request->device_id)->delete();

         // Remove all sessions for this user
            DB::table('sessions')
            ->where('user_id', $userId)
            ->delete();

        try {
            $user = \App\Models\User::find($userId);
            if ($user && class_exists('Laravel\\Sanctum\\PersonalAccessToken')) {
                \Laravel\Sanctum\PersonalAccessToken::where('tokenable_id', $userId)
                    ->delete();
            }
        } catch (\Throwable $e) {
            // best-effort; ignore errors revoking tokens
        }

        return response()->json([
            'status' => true,
            'message' => __('users.device_logout'),
        ], 200);
    }

    public function saveWatchHistory(Request $request)
    {
        $user = auth()->user();
        $data = $request->all();

        $profile_id=$request->has('profile_id') && $request->profile_id
        ? $request->profile_id
        : getCurrentProfile($user->id, $request);

        $data['profile_id']=$profile_id;


        $search_data  = [
            'user_id' => $user->id,
            'entertainment_id' =>$data['entertainment_id'],
            'profile_id' => $data['profile_id'],
            'entertainment_type' => $data['entertainment_type']
        ];
        UserWatchHistory::create($search_data);

        ContinueWatch::where('user_id',$user->id)->where('profile_id',$profile_id)->where('entertainment_id',$data['entertainment_id'])->where('entertainment_type', $data['entertainment_type'])->forceDelete();


        return response()->json(['status' => true, 'message' => __('movie.history_save')]);
    }

    public function profileDetailsV2(Request $request)
    {
        $userId = $request->user_id ? $request->user_id : auth()->user()->id;

        $profile_id = isset($request->profile_id) ? $request->profile_id : NULL;

        $user = User::with('subscriptionPackage')
        ->with(['watchList' => function($q) use($userId,$profile_id){
            $q->where('user_id', $userId)
                ->where('profile_id', $profile_id);
        }])
        ->with(['continueWatchnew' => function($q) use($userId,$profile_id){
            $q->where('user_id', $userId)
                ->where('profile_id', $profile_id)
                ->orderBy('created_at', 'desc');
        }])
        ->where('id', $userId)->first();

        if($user->is_subscribe == 1){
            $user['plan_details'] = $user->subscriptionPackage;
        }

        $responseData = new UserProfileResourceV2($user);

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('users.user_details'),
        ], 200);
    }

    public function profileDetailsV3(Request $request)
    {
        $userId = $request->user_id ? $request->user_id : auth()->user()->id;
        $profile_id = isset($request->profile_id) ? $request->profile_id : NULL;
        $device_type = getDeviceType($request);

        $cacheKey = 'profile_details_v3_' . md5(json_encode([
            'user_id' => $userId,
            'profile_id' => $profile_id,
            'device_type' => $device_type
        ]));

        $cachedResponse = cacheApiResponse($cacheKey, 300, function () use ($request, $userId, $profile_id, $device_type) {
            $user = User::with('subscriptionPackage')
            ->with(['watchList' => function($q) use($userId,$profile_id){
                $q->where('user_id', $userId)
                    ->where('profile_id', $profile_id);
            }])
            ->with(['continueWatchnew' => function($q) use($userId,$profile_id){
                $q->where('user_id', $userId)
                    ->where('profile_id', $profile_id)
                    ->orderBy('created_at', 'desc');
            }])
            ->where('id', $userId)->first();

            if($user->is_subscribe == 1){
                $user['plan_details'] = $user->subscriptionPackage;
            }

        $getDeviceTypeData = Subscription::checkPlanSupportDevice($userId, $device_type);
        $deviceTypeResponse = json_decode($getDeviceTypeData->getContent(), true);
        $userLevel = Subscription::select('plan_id')->where(['user_id' => $userId, 'status' => 'active'])->latest()->first();
        $userPlanId = $userLevel->plan_id ?? 0;

        $user['watching_profiles'] = UserMultiProfile::where('user_id', $userId)->get();



        $responseData = new UserProfileResourceV3($user);

        return [
            'status' => true,
            'data' => $responseData,
            'message' => __('users.user_details'),
        ];
        });

        return response()->json($cachedResponse['data'], 200);
    }
}
