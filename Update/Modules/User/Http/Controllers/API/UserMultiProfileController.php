<?php

namespace Modules\User\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserMultiProfile;
use App\Models\Device;
use Modules\User\Transformers\UserMultiProfileResource;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Cache;

class UserMultiProfileController extends Controller
{
    public function profileList(Request $request)
    {
        $user_id = !empty($request->user_id)? $request->user_id :auth()->user()->id;

        $perPage = $request->input('per_page', 10);
        $profiles = UserMultiProfile::with('user');

        $profiles = $profiles->where('user_id', operator: $user_id)->paginate($perPage);

        $responseData = UserMultiProfileResource::collection($profiles);

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('movie.profile_list'),
        ], 200);
    }

    public function saveProfile(Request $request)
    {
        $user = auth()->user();
        $data = $request->all();

        if (isset($data['name']) && strlen(trim($data['name'])) > 12) {
            return response()->json([
                'status' => false,
                'error' => __('messages.name_max_12_characters')
            ], 422);
        }

        if(isset($request->id) && !empty($request->id))
        {
            $profilesCheck = UserMultiProfile::where('user_id', $user->id);

            $isChild = isset($data['is_child_profile']) ? (int)$data['is_child_profile'] : 0;
            if ($profilesCheck->count('id') <= 1 && $isChild === 1) {
                return response()->json([
                    'error' => 'If you only have one parent profile, you can`t convert it to a child profile'
                ], 406);
            }

            $proCheck = UserMultiProfile::where([
                'is_child_profile' => 0,
                'user_id' => $user->id
            ])->where('id','!=',$request->id)->count('id');


            if ($proCheck < 1 && $isChild === 1) {
                return response()->json([
                    'status' => false,
                    'error' => 'Atleast one parent profile is required'
                ], 406);
            }
        }

        $avatar = $data['avatar'] ?? asset('storage/avatars/image/icon2.png');

        $profile_data = [
            'user_id' => $user->id,
            'name'    => $data['name'],
            'avatar'  => $avatar
        ];

        $profile_data['is_child_profile'] = isset($data['is_child_profile']) ? $data['is_child_profile'] : 0;

        $profile_count = UserMultiProfile::where('user_id', $user->id)->count();

        if (empty($request->id)) {
            $max_profiles = $user->is_subscribe ? $this->getSubscriptionProfileLimit($user) : 1;

            if ($profile_count >= $max_profiles) {
                return response()->json([
                    'status' => false,
                    'error' => 'You’ve reached the profile limit for your current plan. Upgrade to add more profiles.'
                ], 406);
            }
        }

        $user_profile = UserMultiProfile::updateOrCreate(
            ['user_id' => $user->id, 'id' => $request->id],
            $profile_data
        );


        if ($request->hasFile('file_url')) {

            $file = $request->file('file_url');

            $destinationPath = 'avatars';

            $filename = $file->getClientOriginalName();
            $filePath = $file->storeAs($destinationPath, $filename, 'public');

            $file_url = '/storage/' . $filePath;

            $avatar=setavatarBaseUrl($file_url);

            $user_profile->update(['avatar'=> $avatar]);

        }

        $profiles = UserMultiProfile::where('user_id', $user->id)->get();
        $responseData = UserMultiProfileResource::collection($profiles);

        $message = empty($request->id) ? __('messages.profile_add') : __('messages.profile_update');

        cache::flush();

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'user_profile' => $user_profile,
            'message' => $message
        ]);
    }


private function getSubscriptionProfileLimit($user)
{
    if (!$user->subscriptionPackage) {
        return 0;
    }

    $subscription = $user->subscriptionPackage;

    if (isset($subscription->plan_type) && !empty($subscription->plan_type)) {
        $planLimitations = json_decode($subscription->plan_type, true);

        if (is_array($planLimitations)) {
            foreach ($planLimitations as $limitation) {
                if (isset($limitation['slug']) && $limitation['slug'] === 'profile-limit') {
                    $limitData = $limitation['limit'] ?? null;
                    
                    if (is_array($limitData) && isset($limitData['value'])) {
                        return (int)$limitData['value'];
                    } elseif (is_string($limitData) || is_numeric($limitData)) {
                        return (int)$limitData;
                    }
                    
                    return 0;   
                }
            }
        }
    }

    return 0;
}

    public function getprofile(Request $request, $id)
    {
        $user_id = !empty($request->user_id)? $request->user_id :auth()->user()->id;

        $profile = UserMultiProfile::where('id', $request->id)->first();

        $responseData = New UserMultiProfileResource($profile);

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('messages.profile_update'),
        ], 200);
    }

    public function SelectProfile(Request $request, $id)
{
    Cache::flush();
    $user_id = $request->user_id ?? auth()->id();
    $device = Device::where('user_id', $user_id)
                    ->where('device_id', $request->ip())
                    ->orderBy('id','DESC')
                    ->first();

    if ($device) {

        Device::where('user_id', $user_id)
                ->where('device_id', $request->ip())
                ->where('id','!=',$device->id)
                ->delete();

        $device->update(['active_profile' => $id]);
    } else {
        $agent = new Agent();
        $device_id = $request->getClientIp();
        $device_name =  $agent->browser();
        $platform = $agent->platform();

        $device = Device::create([
                'user_id' => $user_id,
                'device_id' => $device_id,
                'device_name' => $device_name,
                'platform' => $platform,
                  'session_id' => session()->getId(),
                'last_activity' => now(),
                'active_profile' => $id
            ]);

    }

    $profiles = UserMultiProfile::where('user_id', $user_id)->get();

    $responseData = UserMultiProfileResource::collection($profiles);

    setCurrentProfileSession(1,$id);

    return response()->json([
        'status' => true,
        'data'=>$responseData,
        'message' => __('movie.profile_selected'),
    ], 200);
}



    public function deleteProfile(Request $request)
    {
        $user = auth()->user();

        $profile = UserMultiProfile::where('user_id', $user->id)->where('id', $request->profile_id)->first();

        if ($profile == null) {

            $message = __('movie.profile');

            return response()->json(['status' => false, 'message' => $message]);
        }
        $profile->delete();
        $message = __('movie.profile_delete');

        cache::flush();
        return response()->json(['status' => true, 'message' => $message]);
    }

}
