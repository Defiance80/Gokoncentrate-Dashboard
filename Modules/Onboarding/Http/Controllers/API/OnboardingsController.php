<?php

namespace Modules\Onboarding\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Modules\Onboarding\Models\Onboarding;
use Illuminate\Http\Request;
use Modules\Onboarding\Transformers\OnboardingResource;

class OnboardingsController extends Controller
{
  // api controller logic
  public function onboardingDataList(Request $request)
  {
    $perPage = $request->input('per_page', 10);
    $searchTerm = $request->input('search', null);
    $page = $request->input('page', 1);

    $onboardings = Onboarding::where('status', 1)->paginate($perPage);

    $responseData = OnboardingResource::collection($onboardings);


    return response()->json([
        'status' => true,
        'data' => $responseData,
        'message' => __('messages.onboardings_list'),
    ], 200);
  }
}
