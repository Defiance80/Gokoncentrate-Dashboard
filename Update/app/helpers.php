    <?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Setting;
use App\Models\Device;
use App\Models\UserMultiProfile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Modules\Currency\Models\Currency;
use Modules\Entertainment\Models\Entertainment;
use Modules\Page\Models\Page;
use Illuminate\Support\Facades\File;

if (!function_exists('mail_footer')) {
function mail_footer($type)
{
    return [
        'notification_type' => $type,
        'logged_in_user_fullname' => auth()->user() ? auth()->user()->full_name ?? default_user_name() : '',
        'logged_in_user_role' => auth()->user() ? auth()->user()->getRoleNames()->first()->name ?? '-' : '',
        'company_name' => setting('app_name'),
        'company_contact_info' => implode('', [
            setting('helpline_number') . PHP_EOL,
            setting('inquriy_email'),
        ]),
    ];
}
}


function sendNotification($data)
{
    \Log::info("data". json_encode($data));
    $mailable = \Modules\NotificationTemplate\Models\NotificationTemplate::where('type', $data['notification_type'])->with('defaultNotificationTemplateMap')->where('status', 1)->first();
    if ($mailable != null && $mailable->to != null) {

        $mails = json_decode($mailable->to);
        foreach ($mails as $key => $mailTo) {
            $data['type'] = $data['notification_type'];
            $data['logged_in_user_fullname'] =  $data['logged_in_user_fullname'] ?? auth()->user()->full_name ?? '';
            $data['logged_in_user_role'] =  $data['logged_in_user_role'] ?? auth()->user()->user_type ?? '';
            $data['company_name'] =  $data['company_name'] ?? app_name() ?? '';
            $data['company_contact_info'] =  $data['company_contact_info'] ?? setting('helpline_number') ?? '' . setting('inquriy_email') ?? '';
            $data['user_id'] =  $data['user_id'] ?? '';
            $data['user_name'] =  $data['user_name'] ?? '';
            $data['movie_name'] =  $data['movie_name'] ??  '';
            $data['tvshow_name'] =  $data['tvshow_name'] ?? '';
            $data['season_name'] =  $data['season_name'] ?? '';
            $data['episode_name'] =  $data['episode_name'] ?? '';
            $data['end_date'] =  formatDateTimeWithTimezone($data['end_date'] ?? '', 'date');
            $data['content_type'] =  $data['content_type'] ?? '';
            $data['start_date'] =  formatDateTimeWithTimezone($data['start_date'] ?? '');
            $data['app_name'] =  $data['app_name'] ?? env('APP_NAME') ?? '';

            if (isset($data['amount']) && is_numeric($data['amount'])) {
                $data['amount_formatted'] = Currency::format($data['amount']);
            }

            $subscription = isset($data['subscription']) ? $data['subscription'] : '';
            if (isset($subscription) && $subscription != null) {
                $data['id'] = $subscription['id'];
                $data['user_id'] = $subscription['user_id'];
                $data['plan_id'] = $subscription['plan_id'];
                $data['subscription_plan'] = $subscription['name'];
                $data['identifier'] = $subscription['identifier'];
                $data['type'] = $subscription['type'];
                $data['status'] = $subscription['status'];
                $data['amount'] = ($subscription['total_amount'] ?? $subscription['amount']) - ($subscription['tax_amount'] ?? 0);
                $data['amount_formatted'] = Currency::format($data['amount']);
                $data['tax_amount'] = $subscription['tax_amount'] ?? 0;
                $data['tax_amount_formatted'] = Currency::format($subscription['tax_amount'] ?? 0);
                $data['total_amount'] = $subscription['total_amount'] ?? $subscription['amount'];
                $data['total_amount_formatted'] = Currency::format($subscription['total_amount'] ?? $subscription['amount']);
                $data['coupon_discount'] = $subscription['coupon_discount'] ?? 0;
                $data['coupon_discount_formatted'] = Currency::format($subscription['coupon_discount'] ?? 0);
                $data['duration'] = $subscription['duration'] ?? '';
                $data['duration_formatted'] = $subscription['duration'] . ' ' . ucfirst($subscription['type']);
                $data['plan_type'] = $subscription['plan_type'];
                $data['user_name'] = $subscription['user']->full_name ?? '';
                $data['user_email'] = $subscription['user']->email ?? '';
                $data['user_mobile'] = $subscription['user']->mobile ?? '-';
                $data['start_date'] =  formatDateTimeWithTimezone($subscription['start_date'] ?? '', 'date');
                $data['end_date'] =  formatDateTimeWithTimezone($subscription['end_date'] ?? '', 'date');
                // Ensure days and days_remaining are passed correctly for placeholder replacement
                $data['days'] = $subscription['days'] ?? $subscription['days_remaining'] ?? 0;
                $data['days_remaining'] = $subscription['days_remaining'] ?? $subscription['days'] ?? 0;
                $data['notification_group'] = 'subscription';
                $data['site_url'] = env('APP_URL');

                // Store the subscription user_id BEFORE unsetting
                $subscriptionUserId = $subscription['user_id'] ?? null;
                unset($data['subscription']);

            } else {
                $subscriptionUserId = null;
            }

            // Preserve original user data for later user notification
            $originalUserId = $data['user_id'] ?? null;
            $originalUserName = $data['user_name'] ?? null;
            $originalSubscriptionUserId = $subscriptionUserId;
            
            switch ($mailTo) {
                case 'admin':

                    $admin = \App\Models\User::role('admin')->first();

                    if (isset($admin->email)) {
                        try {
                            // Use admin data for this notification only
                            $data['user_type'] = $mailTo;
                            $data['user_id'] = $admin->id;
                            $data['user_name'] = $admin->full_name;
                            $admin->notify((new Modules\NotificationTemplate\Notifications\CommonNotification($data['notification_type'], $data))->onQueue('notifications'));
                        } catch (\Exception $e) {
                            Log::error($e);
                        }
                    }
                    // Restore original user data for subsequent iterations
                    $data['user_id'] = $originalUserId;
                    $data['user_name'] = $originalUserName;
                    break;
                case 'demo_admin':

                    $demoadmin = \App\Models\User::role('demo_Admin')->first();

                    if (isset($demoadmin->email)) {
                        try {
                            $data['user_type'] = $mailTo;
                            $data['user_id'] = $demoadmin->id;
                            $data['user_name'] = $demoadmin->full_name;
                            $demoadmin->notify((new Modules\NotificationTemplate\Notifications\CommonNotification($data['notification_type'], $data))->onQueue('notifications'));
                        } catch (\Exception $e) {
                            Log::error($e);
                        }
                        }
                    // Restore original user data
                    $data['user_id'] = $originalUserId;
                    $data['user_name'] = $originalUserName;
                    break;
                case 'user':
                    // If notification type is movie_add, tv_show_add, upcoming, or continue_watch, send to all users
                    if (in_array($data['notification_type'], ['movie_add', 'tv_show_add','episode_add','season_add','video_add', 'upcoming'])) {
                        \App\Models\User::where('user_type','user')->chunk(500, function ($users) use ($data) {
                            $chunkCount = 0;
                            foreach ($users as $user) {
                                try {
                                    $data['user_id']   = $user->id;
                                    $data['user_name'] = $user->full_name;
                                    $data['user_type'] = 'user';
                                    $user->notify(
                                        (new Modules\NotificationTemplate\Notifications\CommonNotification($data['notification_type'], $data))
                                            ->onQueue('notifications')
                                    );
                                } catch (\Exception $e) {
                                    Log::error($e);
                                }
                            }
                        });
                    } else {
                        // Send to specific user
                        // Use the preserved subscription user_id if available, otherwise fall back to data user_id
                        $targetUserId = $originalSubscriptionUserId ?? $data['user_id'] ?? null;
                        
                        if ($targetUserId) {
                            $user = \App\Models\User::find($targetUserId);
                            if ($user) {
                                try {
                                    $data['user_type'] = $mailTo;
                                    $user->notify((new Modules\NotificationTemplate\Notifications\CommonNotification($data['notification_type'], $data))->onQueue('notifications'));
                                } catch (\Exception $e) {
                                    Log::error($e);
                                }
                            }
                        }
                    }
                    break;
            }
        }
    }
}
function sendNotifications($data)
{

    $heading = '#' . $data['id'] . ' ' . str_replace("_", " ", $data['name']);
    $content = strip_tags($data['description']);
    $appName = env('APP_NAME');
    $topic = str_replace(' ', '_', strtolower($appName));
    $type = $data['type'];
    $additionalData = json_encode($data);

    $thumbnailImage = null;
    if (!empty($data['poster_url'])) {
        $thumbnailImage = getThumbnail($data['name'] ?? null, strtolower($data['type']));
    }

    // For push notifications we do NOT want to show a generic/logo image when no specific thumbnail
    // Leave $thumbnailImage as null so the push payload will omit image fields when no poster exists
    if (!$thumbnailImage || strpos($thumbnailImage, 'default-image/') !== false) {
        $thumbnailImage = null;
    }

    Log::info('[FCM_DEBUG] Notification Type: ' . strtolower($data['type']));
    Log::info('[FCM_DEBUG] Resolved Image: ' . ($thumbnailImage ?? 'None'));

    $message = [
        "topic" => $topic,
        "notification" => [
            "title" => $heading,
            "body" => $content,
        ],
        "data" => [
            "sound" => "default",
            "story_id" => "story_12345",
            "type" => $type,
            "additional_data" => $additionalData,
        ],
        "android" => [
            "priority" => "HIGH",
        ],
        "apns" => [
            "payload" => [
                "aps" => [
                    "category" => $type,
                    "mutable-content" => 1,
                ],
            ],
        ],
    ];

    if (!empty($thumbnailImage)) {
        $message['notification']['image'] = $thumbnailImage;
        $message['data']['image'] = $thumbnailImage;
        $message['data']['picture'] = $thumbnailImage;
        $message['data']['large_icon'] = $thumbnailImage;
        $message['android']['notification'] = ['image' => $thumbnailImage];
        $message['apns']['fcm_options'] = ['image' => $thumbnailImage];
    }

    return fcm([
        "message" => $message,
    ]);

}
function fcm($fields)
{

    $otherSetting = \App\Models\Setting::where('type', 'appconfig')->where('name', 'firebase_key')->first();


    $projectID = $otherSetting->val ?? null;

    $access_token = getAccessToken();

    $headers = [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json',
    ];
    $ch = curl_init('https://fcm.googleapis.com/v1/projects/' . $projectID . '/messages:send');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

    Log::info('[FCM_DEBUG] FCM REQUEST PAYLOAD: ' . json_encode($fields));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    Log::info('[FCM_DEBUG] FCM RESPONSE (HTTP ' . $httpCode . '): ' . $response);
    Log::info('[FCM_DEBUG] FCM RESPONSE: ' . $response);
    
    curl_close($ch);
}
function getAccessToken()
{
    $directory = storage_path('app/data');
    $credentialsFiles = File::glob($directory . '/*.json');

    if (empty($credentialsFiles)) {
        return null; // No credentials found
    }

    $client = new Google_Client();
    if(!isset($client) || empty($client)){
        return null;
    }
    $client->setAuthConfig($credentialsFiles[0]);
    $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

    try {
        $token = $client->fetchAccessTokenWithAssertion();
        return $token['access_token'];
    } catch (Exception $e) {
        // In case of any error, return null
        return null;
    }
}

function formatOffset($offset)
{
    $hours = $offset / 3600;
    $remainder = $offset % 3600;
    $sign = $hours > 0 ? '+' : '-';
    $hour = (int) abs($hours);
    $minutes = (int) abs($remainder / 60);

    if ($hour == 0 and $minutes == 0) {
        $sign = ' ';
    }

    return 'GMT' . $sign . str_pad($hour, 2, '0', STR_PAD_LEFT)
        . ':' . str_pad($minutes, 2, '0');
}

function timeZoneList()
{
    $list = \DateTimeZone::listAbbreviations();
    $idents = \DateTimeZone::listIdentifiers();

    $data = $offset = $added = [];
    foreach ($list as $abbr => $info) {
        foreach ($info as $zone) {
            if (!empty($zone['timezone_id']) and !in_array($zone['timezone_id'], $added) and in_array($zone['timezone_id'], $idents)) {
                $z = new \DateTimeZone($zone['timezone_id']);
                $c = new \DateTime(null, $z);
                $zone['time'] = $c->format('H:i a');
                $offset[] = $zone['offset'] = $z->getOffset($c);
                $data[] = $zone;
                $added[] = $zone['timezone_id'];
            }
        }
    }

    array_multisort($offset, SORT_ASC, $data);
    $options = [];
    foreach ($data as $key => $row) {
        $options[$row['timezone_id']] = $row['time'] . ' - ' . formatOffset($row['offset']) . ' ' . $row['timezone_id'];
    }

    return $options;
}

/*
 * Global helpers file with misc functions.
 */
if (!function_exists('app_name')) {
    /**
     * Helper to grab the application name.
     *
     * @return mixed
     */
    function app_name()
    {
        return setting('app_name') ?? config('app.name');
    }
}
/**
 * Avatar Find By Gender
 */
if (!function_exists('default_user_avatar')) {
    function default_user_avatar()
    {
        return asset(config('app.avatar_base_path') . 'avatar.webp');
    }
    function default_user_name()
    {
        return __('messages.unknown_user');
    }
}
if (!function_exists('user_avatar')) {
    function user_avatar()
    {
        if (auth()->user()->file_url ?? null) {
            return auth()->user()->file_url;
        } else {
            return asset(config('app.avatar_base_path') . 'avatar.webp');
        }
    }
}

if (!function_exists('default_file_url')) {
    function default_file_url()
    {
        return asset(config('app.image_path') . 'default.webp');
    }
}

/*
 * Global helpers file with misc functions.
 */
if (!function_exists('user_registration')) {
    /**
     * Helper to grab the application name.
     *
     * @return mixed
     */
    function user_registration()
    {
        $user_registration = false;

        if (env('USER_REGISTRATION') == 'true') {
            $user_registration = true;
        }

        return $user_registration;
    }
}

/**
 * Global Json DD
 * !USAGE
 * return jdd($id);
 */
if (!function_exists('jdd')) {
    function jdd($data)
    {
        return response()->json($data, 500);
        exit();
    }
}
function GetcurrentCurrency()
{
    $currency = Currency::getDefaultCurrency();

    $currency_code = $currency ? strtolower($currency->currency_code) : 'usd';
    return $currency_code;
}


/*
 *
 * label_case
 *
 * ------------------------------------------------------------------------
 */
if (!function_exists('label_case')) {
    /**
     * Prepare the Column Name for Lables.
     */
    function label_case($text)
    {
        $order = ['_', '-'];
        $replace = ' ';

        $new_text = trim(\Illuminate\Support\Str::title(str_replace('"', '', $text)));
        $new_text = trim(\Illuminate\Support\Str::title(str_replace($order, $replace, $text)));
        $new_text = preg_replace('!\s+!', ' ', $new_text);

        return $new_text;
    }
}


if (!function_exists('fielf_required')) {
    /**
     * Prepare the Column Name for Lables.
     */
    function fielf_required($required)
    {
        $return_text = '';

        if ($required != '') {
            $return_text = '<span class="text-danger">*</span>';
        }

        return $return_text;
    }
}

/*
 * Get or Set the Settings Values
 *
 * @var [type]
 */
if (!function_exists('setting')) {
    function setting($key, $default = null)
    {

        if (is_null($key)) {
            return new App\Models\Setting();
        }

        if (is_array($key)) {
            return App\Models\Setting::set($key[0], $key[1]);
        }
        // dd($key);
        $value = App\Models\Setting::get($key);
        // dd($value);
        return is_null($value) ? value($default) : $value;
    }
}

function app_name()
{
        $value = App\Models\Setting::where('name','app_name')->select('val')->first();
        $app_name = $value->val;
        return is_null($app_name) ? : $app_name;
}



/*
 * Show Human readable file size
 *
 * @var [type]
 */
if (!function_exists('humanFilesize')) {
    function humanFilesize($size, $precision = 2)
    {
        $units = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $step = 1024;
        $i = 0;

        while (($size / $step) > 0.9) {
            $size = $size / $step;
            $i++;
        }

        return round($size, $precision) . $units[$i];
    }
}



/*
 *
 * Prepare a Slug for a given string
 * Laravel default str_slug does not work for Unicode
 *
 * ------------------------------------------------------------------------
 */
if (!function_exists('slug_format')) {
    /**
     * Format a string to Slug.
     */
    function slug_format($string)
    {
        $base_string = $string;

        $string = preg_replace('/\s+/u', '-', trim($string));
        $string = str_replace('/', '-', $string);
        $string = str_replace('\\', '-', $string);
        $string = strtolower($string);

        $slug_string = $string;

        return $slug_string;
    }
}

/*
 *
 * icon
 * A short and easy way to show icon fornts
 * Default value will be check icon from FontAwesome
 *
 * ------------------------------------------------------------------------
 */
if (!function_exists('icon')) {
    /**
     * Format a string to Slug.
     */
    function icon($string = 'fas fa-check')
    {
        $return_string = "<i class='" . $string . "'></i>";

        return $return_string;
    }
}



if (!function_exists('language_direction')) {
    /**
     * return direction of languages.
     *
     * @return string
     */
    function language_direction($language = null)
    {
        if (empty($language)) {
            $language = app()->getLocale();
        }
        $language = strtolower(substr($language, 0, 2));
        $rtlLanguages = [
            'ar', //  'العربية', Arabic
            'arc', //  'ܐܪܡܝܐ', Aramaic
            'bcc', //  'بلوچی مکرانی', Southern Balochi
            'bqi', //  'بختياري', Bakthiari
            'ckb', //  'Soranî / کوردی', Sorani Kurdish
            'dv', //  'ދިވެހިބަސް', Dhivehi
            'fa', //  'فارسی', Persian
            'glk', //  'گیلکی', Gilaki
            'he', //  'עברית', Hebrew
            'lrc', //- 'لوری', Northern Luri
            'mzn', //  'مازِرونی', Mazanderani
            'pnb', //  'پنجابی', Western Punjabi
            'ps', //  'پښتو', Pashto
            'sd', //  'سنڌي', Sindhi
            'ug', //  'Uyghurche / ئۇيغۇرچە', Uyghur
            'ur', //  'اردو', Urdu
            'yi', //  'ייִדיש', Yiddish
        ];
        if (in_array($language, $rtlLanguages)) {
            return 'rtl';
        }

        return 'ltr';
    }
}




function getCustomizationSetting($name, $key = 'customization_json')
{
    $settingObject = setting($key);
    if (isset($settingObject) && $key == 'customization_json') {
        try {
            $settings = (array) json_decode(html_entity_decode(stripslashes($settingObject)))->setting;

            if (isset($settings[$name])) {
                return collect($settings[$name])['value'];
            }

            return '';
        } catch (\Exception $e) {
            return '';
        }

        return '';
    } elseif ($key == 'root_color') {
        //
    }

    return '';
}

/**
 * Get footer copyright text for current locale using Laravel translation system
 *
 * @param string|null $locale Optional locale, defaults to current locale
 * @return string
 */

function str_slug($title, $separator = '-', $language = 'en')
{
    return Str::slug($title, $separator, $language);
}
function formatDuration($duration)
{
    if (strpos($duration, ':') !== false) {
        list($hours, $minutes) = explode(':', $duration);
        $hours = intval($hours);
        $minutes = intval($minutes);
        // Format as "05h 20m" with leading zeros for hours
        $hoursFormatted = str_pad($hours, 2, '0', STR_PAD_LEFT);
        $minutesFormatted = str_pad($minutes, 2, '0', STR_PAD_LEFT);
        return "{$hoursFormatted}h {$minutesFormatted}m";
    }

    return $duration;
}

function formatCurrency($number, $noOfDecimal, $decimalSeparator, $thousandSeparator, $currencyPosition, $currencySymbol)
{

    $formattedNumber = number_format($number, $noOfDecimal, '.', '');


    $parts = explode('.', $formattedNumber);
    $integerPart = $parts[0];
    $decimalPart = isset($parts[1]) ? $parts[1] : '';

    $integerPart = number_format($integerPart, 0, '', $thousandSeparator);


    $currencyString = '';

    if ($currencyPosition == 'left' || $currencyPosition == 'left_with_space') {
        $currencyString .= $currencySymbol;
        if ($currencyPosition == 'left_with_space') {
            $currencyString .= ' ';
        }

        $currencyString .= $integerPart;

        if ($noOfDecimal > 0) {
            $currencyString .= $decimalSeparator . $decimalPart;
        }
    }


    if ($currencyPosition == 'right' || $currencyPosition == 'right_with_space') {

        if ($noOfDecimal > 0) {
            $currencyString .= $integerPart . $decimalSeparator . $decimalPart;
        }
        if ($currencyPosition == 'right_with_space') {
            $currencyString .= ' ';
        }
        $currencyString .= $currencySymbol;
    }

    return $currencyString;
}


function formatUpdatedAt($updatedAt)
{
    $diff = Carbon::now()->diffInHours($updatedAt);
    return $diff < 25 ? $updatedAt->diffForHumans() : $updatedAt->isoFormat('llll');
}
function storeMediaFileAWS($module, $filePath, $key = 'file_url')
{
    // Clear existing media collection
    $module->clearMediaCollection($key);

    // Store the file using Laravel's media library
    $mediaItems = $module->addMedia($filePath)->toMediaCollection($key);

    // Get the stored file's path or name
    if ($mediaItems->count() > 0) {
        // Return the path or name of the stored file
        return $mediaItems[0]->file_name; // Adjust this based on your media library configuration
    }

    return null; // Return null or handle error as needed
}

function storeMediaFile($module, $files, $key = 'file_url')
{

    $module->clearMediaCollection($key);

    if (is_array($files)) {
        foreach ($files as $file) {
            if (!empty($file)) {
                $module->addMedia($file)->toMediaCollection($key);
            }
        }
    } else {
        $module->clearMediaCollection($key);
        $mediaItems = $module->addMedia($files)->toMediaCollection($key);
    }
}





function getMediaUrls($searchQuery = null, $perPage = 21, $page = 1)
{
    // $activeDisk = DB::table('settings')->where('name', 'disc_type')->value('val') ?? env('ACTIVE_STORAGE','local');
    $activeDisk = env('ACTIVE_STORAGE'); // set on live server

    $folder = $activeDisk === 'local' ? 'public/' : '';
    $files = Storage::disk($activeDisk)->allFiles($folder);

    if ($searchQuery) {
        $files = array_filter($files, function ($file) use ($searchQuery) {
            // Ensure $file is a string and check if it contains the search query
            return is_string($file) && stripos($file, $searchQuery) !== false;
        });
    }

    //     // Sort files in descending order (newest first)
    // // Convert to array if it's not already
    // $files = array_values($files);
    // // Sort by file modification time in descending order (newest first)
    // usort($files, function($a, $b) use ($activeDisk) {
    //     $timeA = Storage::disk($activeDisk)->lastModified($a);
    //     $timeB = Storage::disk($activeDisk)->lastModified($b);
    //     return $timeB - $timeA; // Descending order
    // });

    $totalFiles = count($files);
    $offset = ($page - 1) * $perPage;
    $paginatedFiles = array_slice($files, $offset, $perPage);

    $mediaUrls = array_map(function ($file) use ($activeDisk) {
        if ($activeDisk === 'local') {
            $file = str_replace('public/', '', $file);
            return asset('storage/' . $file);
        } else {
            return Storage::disk($activeDisk)->url($file);
        }
    }, $paginatedFiles);

    return [
        'mediaUrls' => $mediaUrls,
        'hasMore' => $offset + $perPage < $totalFiles,
    ];
}

if (!function_exists('setDefaultImage')) {
    function setDefaultImage($fileUrl = '')
    {
        $defaultImagePath = '/default-image/Default-Image.jpg';
        $defaultImage = asset($defaultImagePath);

        if (empty($fileUrl)) {
            return $defaultImage;
        }

        return $fileUrl;
    }
}



if (!function_exists('getImageUrlOrDefault')) {
    /**
     * Check if the image exists, return the file URL or the default image URL.
     *
     * @param string $fileUrl The full URL of the file to check
     * @return string The valid file URL or the default image URL
     */
    function getImageUrlOrDefault($fileUrl)
    {

        $fileUrl = setBaseUrlWithFileName($fileUrl);

        return $fileUrl;

    }
}


function formatDate($date)
{
    if (empty($date)) {
        return '-';
    }

    try {
        // Handle both date (Y-m-d) and datetime (Y-m-d H:i:s) formats

        $releaseDate = Carbon::parse($date);
        $defaultFormat = Setting::where('name', 'default_date_format')->where('datatype', 'misc')->value('val')  ?? 'Y-m-d';
        $userAgent = request()->header('user-agent');
        $isAppOrTv = false;

        $formatedDateApiRoutes = [
            'api.user-subscription_histroy',
            'api.account-setting',
            'api.v3.profile-details',
            'api.transaction-history',
            'api.cast_crew_details_v3',
        ];

        $currentRoute = \Illuminate\Support\Facades\Route::currentRouteName();

        $isWebFormat = in_array($currentRoute, $formatedDateApiRoutes);
        if($isWebFormat){
            return $releaseDate->format($defaultFormat);
        }



        if (!empty($userAgent)) {
            $ua = strtolower($userAgent);
            if (Str::contains($ua, ['android', 'iphone', 'ipad','androidtv','smarttv','firetv','tizen','apple tv','roku','tv'])) {
                $isAppOrTv = true;
            }
        }
        if ($isAppOrTv) {
            return $releaseDate->format('Y-m-d');
        }



        if (!empty($defaultFormat)) {
            return $releaseDate->format($defaultFormat);
        }

    } catch (\Exception $e) {
        return '-';
    }
}

function formatTime($date)
{
    if (empty($date)) {
        return '-';
    }

    try {
        // Handle both date (Y-m-d) and datetime (Y-m-d H:i:s) formats
        $releaseDate = Carbon::parse($date);

        $defaultFormat = Setting::where('name', 'default_time_format')->where('datatype', 'misc')->value('val');

        if (!empty($defaultFormat)) {
            return $releaseDate->format($defaultFormat);
        }

        return $releaseDate->format('H:i');
    } catch (\Exception $e) {
        return '-';
    }
}

function isenablemodule($key)
{
    $setting = Setting::where('name', $key)->value('val');
    return $setting !== null ? $setting : 0;
}

function gettmdbapiKey()
{
    $tbdb_key = Setting::where('name', 'tmdb_api_key')->value('val');
    return $tbdb_key !== null ? $tbdb_key : null;
}

function getCurrentProfile($user_id, $request)
{
    $device_id = $request->ip();

    return Device::where('user_id', $user_id)
        ->where('device_id', $device_id)
        ->value('active_profile');
}

function checkPlanLimit($user_id, $limit)
{
    $user = App\Models\User::where('id', $user_id)->first();

    if (isset($user->subscriptionPackage) && $user->subscriptionPackage->plan_id != null) {
        $subscription = $user->subscriptionPackage;

        if (isset($subscription['plan_type'])) {
            $planLimitations = json_decode($subscription['plan_type'], true);

            if (is_array($planLimitations)) {
                foreach ($planLimitations as $limitation) {
                    if (
                        isset($limitation['slug']) &&
                        $limitation['slug'] === $limit &&
                        isset($limitation['limitation_value'])
                    ) {
                        return $limitation['limitation_value'];
                    }
                }
            }
        }
    }
    return null;
}

function isSmtpConfigured()
{
    $host = config('mail.mailers.smtp.host');
    $port = config('mail.mailers.smtp.port');
    $username = config('mail.mailers.smtp.username');
    $password = config('mail.mailers.smtp.password');

    return !empty($host) &&
        !empty($port) &&
        !empty($username) &&
        !empty($password) &&
        $username !== 'null' &&
        $password !== 'null';
}

function decryptVideoUrl($encryptedUrl)
{
    try {
        // Remove escape characters
        $cleanUrl = stripslashes($encryptedUrl);


        // Decrypt the URL
        $decryptedUrl = Crypt::decryptString(urldecode($cleanUrl));
        // ✅ Normalize smart quotes to plain quotes
        $decryptedUrl = str_replace(
            ['“', '”', '‘', '’'],
            ['"', '"', "'", "'"],
            $decryptedUrl
        );

        // Check if the URL is a YouTube link
        preg_match("/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^\"&?\/ ]{11})/", $decryptedUrl, $youtubeMatches);
        if (isset($youtubeMatches[1])) {
            return ['platform' => 'youtube', 'videoId' => $youtubeMatches[1]];
        }

        // Check if the URL is a Vimeo link
        preg_match("/player\.vimeo\.com\/video\/(\d+)/", $decryptedUrl, $vimeoMatches);
        if (isset($vimeoMatches[1])) {
            return ['platform' => 'vimeo', 'videoId' => $vimeoMatches[1]];
        }

        preg_match('/(?:https?:\/\/)?(?:www\.)?(?:vimeo\.com\/(?:channels\/[\w]+\/|groups\/[\w]+\/videos\/|album\/\d+\/video\/|video\/|)(\d+)(?:$|\/|\?))/i', $decryptedUrl, $vimeoMatches);

        if (isset($vimeoMatches[1])) {
            return ['platform' => 'vimeo', 'videoId' => $vimeoMatches[1]];
        }

        // Check if the URL is an HLS stream (m3u8)
        if (preg_match('/\.m3u8$/', $decryptedUrl)) {
            return ['platform' => 'hls', 'url' => $decryptedUrl];
        }

        if (
            preg_match('/\.(workers\.dev|cloudfront\.net|amazonaws\.com|koyeb\.app)/', $decryptedUrl) ||
            preg_match('/filmager\.koyeb\.app\/(\d+)\?hash=/', $decryptedUrl)
        ) {

            return [
                'platform' => 'external',
                'videoId' => $decryptedUrl,
                'url' => $decryptedUrl
            ];
        }

        // Check if it's a local file
        $filePath = str_replace(url('/storage'), 'public', $decryptedUrl);
        if (Storage::exists($filePath)) {
            $actualPath = Storage::path($filePath);
            $fileMimeType = mime_content_type($actualPath);

            // Heuristic: check for x265/HEVC in filename or extension
            $isHEVC = false;
            if (preg_match('/\.(mkv|hevc)$/i', $actualPath) || stripos($actualPath, 'x265') !== false || stripos($actualPath, 'hevc') !== false) {
                $isHEVC = true;
            }

            return [
                'platform' => 'local',
                'url' => $actualPath,
                'mimeType' => $fileMimeType,
                'isHEVC' => $isHEVC
            ];
        }

        // If it's an external URL
        if (filter_var($decryptedUrl, FILTER_VALIDATE_URL)) {
            // Heuristic: check for x265/HEVC in URL
            $isHEVC = false;
            if (preg_match('/\.(mkv|hevc)$/i', $decryptedUrl) || stripos($decryptedUrl, 'x265') !== false || stripos($decryptedUrl, 'hevc') !== false) {
                $isHEVC = true;
            }

            return [
                'platform' => 'local',
                'url' => $decryptedUrl,
                'isHEVC' => $isHEVC
            ];
        }

        // Check for embedded iframe-type URL (e.g. short.icu, embedded players)
        if (preg_match('/<iframe.*?src=[\"\']([^\"\']+)[\"\'].*?>.*?<\/iframe>/i', $decryptedUrl, $embedMatch)) {
            return [
                'platform' => 'embedded',
                'url' => $embedMatch[1]
            ];
        }

        // OR: If the decrypted URL is directly an embeddable iframe source (no iframe tag)
        if (preg_match('/^(https?:\/\/)?(short\.icu|iframe\..+|embed\..+|player\..+)\//i', $decryptedUrl)) {
            return [
                'platform' => 'embedded',
                'url' => $decryptedUrl
            ];
        }


        // If no conditions are met
        return ['error' => 'File not found'];
    } catch (\Exception $e) {
        return ['error' => 'Invalid encrypted URL'];
    }
}

function extractFileNameFromUrl($url = '', $page_type = 'default')
{

    $normalizedFolder = $page_type;
    if ($page_type === 'season') {
        $normalizedFolder = 'tvshow/season';
    } elseif ($page_type === 'episode') {
        $normalizedFolder = 'tvshow/episode';
    }

    $isInCorrectFolder = strpos($url, '/storage/' . $normalizedFolder . '/video/') !== false
                      || strpos($url, '/storage/' . $normalizedFolder . '/image/') !== false;

    if (!$isInCorrectFolder) {
        $url = copyImageToFolder($url, $page_type);
    }

    return basename(parse_url($url, PHP_URL_PATH));
}

function copyImageToFolder($fileUrl, $folder = 'other')
{
    try {
        if (empty($fileUrl)) {
            return $fileUrl;
        }

        // Normalize folder names (same logic)
        if ($folder === 'season') {
            $folder = 'tvshow/season';
        } elseif ($folder === 'episode') {
            $folder = 'tvshow/episode';
        }

        // Get active storage disk
        $activeDisk = env('ACTIVE_STORAGE', 'local');
        $disk = Storage::disk($activeDisk);

        // Skip if already in correct folder
        if (strpos($fileUrl, '/storage/' . $folder . '/') !== false) {
            return $fileUrl;
        }

        // Parse URL and derive file path
        $parsedUrl = parse_url($fileUrl);
        $urlPath = ltrim($parsedUrl['path'] ?? '', '/');
        $sourcePath = null;

        if ($activeDisk === 'local') {
            if (($pos = strpos($urlPath, 'storage/')) !== false) {
                $sourcePath = 'public/' . substr($urlPath, $pos + 8);
            }
        } elseif ($activeDisk === 'bunny') {
            $bunnyCdnUrl = env('BUNNY_PULL_ZONE');
            if ($bunnyCdnUrl && str_starts_with($fileUrl, $bunnyCdnUrl)) {
                $sourcePath = ltrim(str_replace($bunnyCdnUrl, '', $fileUrl), '/');
            } else {
                $sourcePath = ltrim($urlPath, '/');
            }
        } else {
            if (($pos = strpos($urlPath, 'storage/')) !== false) {
                $sourcePath = ltrim(substr($urlPath, $pos + 8), '/');
            }
        }

        if (!$sourcePath) {
            return $fileUrl;
        }

        // Get file details
        $fileName = basename($sourcePath);
        $isVideo = preg_match('/\.(mp4|webm|avi|mov)$/i', $fileName);
        $type = $isVideo ? 'video' : 'image';

        // Build target path
        $targetPath = $activeDisk === 'local'
            ? "public/{$folder}/{$type}/{$fileName}"
            : "{$folder}/{$type}/{$fileName}";

        // If source file doesn’t exist, return as is
        if (!$disk->exists($sourcePath)) {
            return $fileUrl;
        }

        // Create target directory if needed
        $targetDir = dirname($targetPath);
        if (!$disk->exists($targetDir)) {
            if ($activeDisk === 'local') {
                $absoluteDir = storage_path('app/' . $targetDir);
                File::makeDirectory($absoluteDir, 0775, true, true);
            } else {
                $disk->makeDirectory($targetDir);
            }
        }

        // Copy file
        $copied = $disk->put($targetPath, $disk->get($sourcePath));

        if ($copied && $activeDisk === 'local') {
            $fullPath = storage_path('app/' . $targetPath);
            if (file_exists($fullPath)) {
                chmod($fullPath, 0664);
            }

            $dirPath = storage_path('app/' . $targetDir);
            if (is_dir($dirPath)) {
                chmod($dirPath, 0775);
            }
        }

        // Generate new URL
        if ($copied) {
            return $activeDisk === 'local'
                ? asset("storage/{$folder}/{$type}/{$fileName}")
                : $disk->url($targetPath);
        }

        return $fileUrl;
    } catch (\Throwable $e) {
        return $fileUrl;
    }
}




function setBaseUrlWithFileName($url = '', $type = 'image', $page_type = 'other')
{

    // Return a default image if the URL is empty
    if (empty($url)) {
        return setDefaultImage();
    }

    if($page_type == 'season'){
        $page_type = 'tvshow/season';
    }

    if($page_type == 'episode'){
        $page_type = 'tvshow/episode';
    }

    // Check if the URL is remote
    $isRemote = filter_var($url, FILTER_VALIDATE_URL) !== false;

    // Handle remote URL
    if ($isRemote) {
        // Return immediately if the remote image exists
        return $url;

        return checkImageExists($url) ? $url : setDefaultImage();
    }

    // Extract the file name
    $fileName = basename($url);

    $activeDisk =  env('ACTIVE_STORAGE', 'local');

    // Handle local storage
    if ($activeDisk === 'local') {
        $filePath = public_path("storage/$page_type/$type/$fileName");
        // Return local asset path if the file exists
        if (file_exists($filePath)) {
            return asset("storage/$page_type/$type/$fileName");
        }
    } elseif ($activeDisk == 'bunny') {
        $baseUrl = env('BUNNY_PULL_ZONE');
        $filePath = "$baseUrl/$page_type/$type/$fileName";
        return $filePath;

    } else {

        $baseUrl = rtrim(env('DO_SPACES_URL'), '/');
        $filePath = "$baseUrl/$page_type/$type/$fileName";

        if (checkImageExists($filePath)) {
            return $filePath;
        }
    }


    // Return a default image as fallback
   return setDefaultImage();

}

function setBaseUrlWithFileNameV2($url = '')
{
    // Check if the URL is remote
    $isRemote = filter_var($url, FILTER_VALIDATE_URL) !== false;

    // Handle remote URL
    if ($isRemote) {
        // Return immediately if the remote image exists
        return $url;

        return checkImageExists($url) ? $url : setDefaultImage();
    }

    // Extract the file name
    $fileName = basename($url);
    $activeDisk = env('ACTIVE_STORAGE', 'local');

    // Handle local storage
    if ($activeDisk === 'local') {
        $filePath = public_path("storage/streamit-laravel/$fileName");

        // Return local asset path if the file exists
        if (file_exists($filePath)) {
            return asset("storage/streamit-laravel/$fileName");
        }
    } elseif ($activeDisk == 'bunny') {
        $baseUrl = env('BUNNY_PULL_ZONE');
        $filePath = "$baseUrl/$page_type/$type/$fileName";
        return $filePath;

    } else {
        // Handle remote storage
        $baseUrl = rtrim(env('AWS_URL'), '/');
        $filePath = "$baseUrl/streamit-laravel/$fileName";

        // Return remote file URL if it exists
        if (checkImageExists($filePath)) {
            return $filePath;
        }
    }

    // Return a default image as fallback
    return setDefaultImage();
}


function setBaseUrlSubtitleFile($url = '')
{

    $fileName = basename($url);
    $filePath = public_path("storage/subtitles/$fileName");

    if (file_exists($filePath)) {
        return asset("storage/subtitles/$fileName");
    }

    return null;

}


function checkImageExists($url)
{
    $headers = @get_headers($url);

    if ($headers && strpos($headers[0], '200') !== false) {
        return true;
    } else {
        return false;
    }
}


function getIdsBySlug($slug)
{
    return json_decode(App\Models\MobileSetting::getValueBySlug($slug));
}

function GetpaymentMethod($name)
{

    if ($name) {
        $payment_key = Setting::where('name', $name)->value('val');
        return $payment_key !== null ? $payment_key : null;
    }
    return null;
}

function GetSettingValue($key)
{

    if ($key) {
        $data = Setting::where('name', $key)->value('val');
        return $data !== null ? $data : null;
    }
    return null;
}


function getResourceCollection($model, $ids, $resource, $toArray = false)
{

    if (empty($ids) || !is_array($ids)) {
        return $toArray ? [] : collect();
    }
    $query = $model::whereIn('id', $ids);

    if (\Schema::hasColumn((new $model)->getTable(), 'status')) {
        $query->where('status', 1);
    }

    $items = $query->get();


    $collection = $resource::collection($items);

    return $toArray ? $collection->toArray(request()) : $collection;
}

function setavatarBaseUrl($url = '')
{

    if ($url != '') {

        $baseUrl = url('/');

        return $baseUrl . $url;

    } else {

        return setDefaultImage();
    }
}

function translate($text)
{

    $currentLang = app()->getLocale();
    return GoogleTranslate::trans($text, $currentLang);
}


if (!function_exists('isActive')) {
    /**
     * Returns 'active' or 'done' class based on the current step.
     *
     * @param  string|array  $route
     * @param  string  $className
     * @return string
     */
    function isActive($route, $className = 'active')
    {
        $currentRoute = Route::currentRouteName();

        if (is_array($route)) {
            return in_array($currentRoute, $route) ? $className : '';
        }

        return $currentRoute == $route ? $className : '';
    }
}

function dbConnectionStatus(): bool
{
    try {
        DB::connection()->getPdo();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
function getThumbnail(string $name, string $type): ?string
{
    switch ($type) {
        case 'movie':
        case 'tv_show':
            $url = \Modules\Entertainment\Models\Entertainment::where('name', $name)->value('poster_url');
            break;

        case 'episode':
            $url = \Modules\Episode\Models\Episode::where('name', $name)->value('poster_url');
            break;

        case 'season':
            $url = \Modules\Season\Models\Season::where('name', $name)->value('poster_url');
            break;

        case 'video':
            $url = \Modules\Video\Models\Video::where('name', $name)->value('poster_url');
            break;

        default:
            return url('default-image/Default-Image.jpg');
    }

    $folder = match ($type) {
        'tv_show' => 'tvshow',
        default => $type,
    };

    return setBaseUrlWithFileName($url, 'image', $folder);
}

if (!function_exists('getFooterData')) {
    function getFooterData()
    {
        $cacheKey = 'footer_data';
        $data = Cache::get($cacheKey);
        if (!$data) {

            if (function_exists('isenablemodule') && isenablemodule('tvshow') == 1) {
                $data['premiumShows'] = \Modules\Entertainment\Models\Entertainment::where('movie_access', 'paid')
                    ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                        $query->where('is_restricted', 0);
                    })
                    ->where('status', 1)
                    ->take(4)
                    ->get();
            } else {
                $data['premiumShows'] = [];
            }

            if (function_exists('isenablemodule') && isenablemodule('movie') == 1) {
                $data['topMovies'] = \Modules\Entertainment\Models\Entertainment::where('type', 'movie')
                    ->where('IMDb_rating', '>', 5)
                    ->when(getCurrentProfileSession('is_child_profile') && getCurrentProfileSession('is_child_profile') != 0, function ($query) {
                        $query->where('is_restricted', 0);
                    })
                    ->orderBy('IMDb_rating', 'desc')
                    ->where('status', 1)
                    ->take(4)
                    ->get();
            } else {
                $data['topMovies'] = [];
            }
            $data['pages'] = Page::where('status', 1)
                             ->where('deleted_at', '=', null)
                              ->get();

            $data['app_store_url'] = GetSettingValue('ios_url');
            $data['play_store_url'] = GetSettingValue('android_url');
            $data['helpline_number'] = GetSettingValue('helpline_number');
            $data['inquriy_email'] = GetSettingValue('inquriy_email');
            $data['short_description'] = GetSettingValue('short_description');
            $data['facebook_url'] = GetSettingValue('facebook_url');
            $data['instagram_url'] = GetSettingValue('instagram_url');
            $data['youtube_url'] = GetSettingValue('youtube_url');
            $data['x_url'] = GetSettingValue('x_url');

            Cache::put($cacheKey, $data);
        }
        return $data;
    }
}


function setEnvValue($key, $value)
{
    $path = base_path('.env');

    // Ensure the .env file exists
    if (file_exists($path)) {
        $envContent = file_get_contents($path);

        // Check if the key already exists
        if (strpos($envContent, "$key=") !== false) {
            // Replace the existing key value pair
            $envContent = preg_replace("/^$key=.*/m", "$key=$value", $envContent);
        } else {
            // Add the key value pair if not found
            $envContent .= "\n$key=$value";
        }

        // Write the content back to the .env file
        file_put_contents($path, $envContent);

        Artisan::call('config:clear');
        Artisan::call('config:cache');
    }
}

function removeCurrentProfileSession()
{
    \Session::forget('current_profile_' . auth()->id() . '');
}
function getActionPlan($slug)
{
    $plan_type = NULL;
    if (auth()->id()) {
        $activeSubscriptions = Modules\Subscriptions\Models\Subscription::where('user_id', auth()->id())
            ->with('subscription_transaction')
            ->where('status', 'active')
            ->where('end_date', '>', now())
            ->orderBy('id', 'desc')
            ->first(['id', 'plan_type', 'plan_id']);

        if (!empty($activeSubscriptions)) {
            $plan_type = collect(json_decode($activeSubscriptions->plan_type));
            $plan_type = $plan_type->where('slug', $slug)->where('status', '1');
        }
    }

    return $plan_type;

}


function setCurrentProfileSession($checkMultiProfile = 0, $id = NULL)
{
    if ($checkMultiProfile == 0) {
        $name = auth()->user()->first_name . " " . auth()->user()->last_name;
        $currentProfile = UserMultiProfile::where([
            'user_id' => auth()->user()->id,
            'name' => $name
        ])
            ->orderBy('id', 'DESC')->first();
    } else {
        $currentProfile = UserMultiProfile::where('id', $id)->first();
    }

    removeCurrentProfileSession();

    \Session::put('current_profile_' . auth()->id() . '', $currentProfile);
}

function getCurrentProfileSession($key = NULL)
{
    $current_profile = \Session::get('current_profile_' . auth()->id() . '');


    if (!empty($current_profile)) {
        (!empty($key)) && $current_profile = $current_profile->$key;
        return $current_profile;
    } else if (auth()->user()) {
        $name = auth()->user()->first_name . " " . auth()->user()->last_name;

        $current_profile = UserMultiProfile::where([
            'user_id' => auth()->user()->id
        ])
            ->where(function ($q) use ($name) {
                $q->where('name', $name)
                    ->Orwhere('name', auth()->user()->first_name);
            })
            ->orderBy('id', 'DESC')->first();

        (!empty($key) && !empty($current_profile)) && $current_profile = $current_profile->$key;
        return $current_profile;
    } else {
        return NULL;
    }
}

function getLoggedUserPin($profileId)
{
    if (!auth()->user()) {
        return "no";
    }
    ### check pin is empty or not
    $pincheck = DB::table('users')->where('id', auth()->user()->id)->pluck('pin')->first();
    if (empty($pincheck)) {
        return "no";
    }

    $parentUser = DB::table('users')->where('id', $profileId)->first();
    if (!$parentUser || $parentUser->is_parental_lock_enable == 0) {
        return "no";
    }

    $Ischild = DB::table('user_multi_profiles')
        ->where('id', $profileId)
        // ->where('is_child_profile',1)
        ->pluck('is_child_profile')
        ->first();

    if (($Ischild == 0 || $Ischild == 1) && getCurrentProfileSession('is_child_profile') == 0) {
        return "no";
    }

    if (getCurrentProfileSession('is_child_profile') == 1 && $Ischild == 0) {
        return "yes";
    } else {
        return "no";
    }

    return "no";
}



function pr($data)
{
    $result = print_r($data);
    exit();
    return $result;
}

function isenablemoduleV2($key)
{
    $responseData = Cache::remember('setting_v2', 3600, function () {
        return Setting::select(['id', 'val', 'name', 'type'])
            ->get()
            ->keyBy('name')
            ->toArray();
    });

    return (isset($responseData[$key]['val']) && !empty($responseData[$key]['val'])) ? $responseData[$key]['val'] : 0;
}

function loggedUserId()
{
    if (!empty(auth()->id())) {
        return auth()->id();
    } else {
        return 0;
    }
}

function getRequestedProfileId()
{
    if (isset(request()->profile_id) && !empty(request()->profile_id)) {
        return request()->profile_id;
    } else {
        return 0;
    }
}
function defaultCurrency()
{
    $currency = Currency::where('is_primary', 1)->first();
    $currency = $currency ? strtolower($currency->currency_code) : 'inr';
    return $currency;
}

function getCurrencySymbolByCurrency($currency)
{
    $currency = Currency::where('currency_code', strtoupper($currency))->first();
    $currency_symbol = $currency ? $currency->currency_symbol : '₹';
    return $currency_symbol;
}

function encryptId($id)
{
    return rtrim(strtr(base64_encode("{$id}"), '+/', '-_'), '=');
}

function decryptId($id)
{
    return base64_decode(strtr($id, '-_', '+/'));
}

/**
 * Convert SRT subtitle file to VTT format
 *
 * @param string $srtContent The content of the SRT file
 * @return string The converted VTT content
 */
function convertSrtToVtt($srtContent) {
    // Add VTT header
    $vttContent = "WEBVTT\n\n";

    // Split the SRT content into subtitle blocks
    $blocks = preg_split('/\n\s*\n/', trim($srtContent));

    foreach ($blocks as $block) {
        $lines = explode("\n", trim($block));

        // Skip if not enough lines
        if (count($lines) < 3) continue;

        // Get timestamp line
        $timestamp = $lines[1];

        // Convert SRT timestamp format to VTT format
        $timestamp = str_replace(',', '.', $timestamp);

        // Get subtitle text
        $text = implode("\n", array_slice($lines, 2));

        // Add to VTT content
        $vttContent .= $timestamp . "\n" . $text . "\n\n";
    }

    return $vttContent;
}

if (! function_exists('cacheApiResponse')) {
    function cacheApiResponse($key, $ttl, $callback) {
        $startTime = microtime(true);

        $data = Cache::remember($key, $ttl, $callback);

        $endTime = microtime(true);
        $responseTimeMs = ($endTime - $startTime) * 1000;

        return [
            'data' => $data,
            'response_time_ms' => round($responseTimeMs, 2)
        ];
    }
}

function dateFormatList()
{
    return [
        'Y-m-d' => date('Y-m-d'),
        'm-d-Y' => date('m-d-Y'),
        'd-m-Y' => date('d-m-Y'),
        'd/m/Y' => date('d/m/Y'),
        'm/d/Y' => date('m/d/Y'),
        'Y/m/d' => date('Y/m/d'),
        'Y.m.d' => date('Y.m.d'),
        'd.m.Y' => date('d.m.Y'),
        'm.d.Y' => date('m.d.Y'),
        'jS M Y' => date('jS M Y'),
        'M jS Y' => date('M jS Y'),
        'D, M d, Y' => date('D, M d, Y'),
        'D, d M, Y' => date('D, d M, Y'),
        'D, M jS Y' => date('D, M jS Y'),
        'D, jS M Y' => date('D, jS M Y'),
        'F j, Y' => date('F j, Y'),
        'd F, Y' => date('d F, Y'),
        'jS F, Y' => date('jS F, Y'),
        'l jS F Y' => date('l jS F Y'),
        'l, F j, Y' => date('l, F j, Y'),

    ];
}

function timeFormatList()
{
    $timeFormats = [
        "H:i",
        "H:i:s",
        "g:i A",
        "H:i:s T",
        "H:i:s.v",
        "U",
        "u",
        "G.i",
        "@BMT"
    ];

    return array_map(function ($format) {
        return ['format' => $format, 'time' => getTimeInFormat($format)];
    }, $timeFormats);
}

function getTimeInFormat($format)
{
    $now = new DateTime();
    $hours = $now->format('H');
    $minutes = $now->format('i');
    $seconds = $now->format('s');
    $milliseconds = $now->format('v');
    $totalSecondsSinceMidnight = ($hours * 3600) + ($minutes * 60) + $seconds;

    switch ($format) {
        case "H:i":
            return "$hours:$minutes";
        case "H:i:s":
            return "$hours:$minutes:$seconds";
        case "g:i A":
            $ampm = $hours >= 12 ? 'PM' : 'AM';
            $formattedHours = $hours % 12 || 12;
            return "$formattedHours:$minutes $ampm";
        case "H:i:s T":
            return "$hours:$minutes:$seconds UTC";
        case "H:i:s.v":
            return "$hours:$minutes:$seconds.$milliseconds";
        case "U":
            return $now->getTimestamp();
        case "u":
            return $milliseconds * 1000;
        case "G.i":
            return $hours + $minutes / 60;
        case "@BMT":
            $swatchBeat = floor($totalSecondsSinceMidnight / 86.4);
            return "@{$swatchBeat}BMT";
        default:
            return "Invalid format";
    }
}

function getDeviceType($request){
    $userAgent = $request->header('User-Agent', '');

        if (preg_match('/mobile/i', $userAgent)) {
            $device_type = 'mobile';
        } elseif (preg_match('/smart-tv|tv|hbbtv/i', $userAgent)) {
            $device_type = 'tv';
        } else {
            $device_type = 'desktop';
        }
        return $device_type;
}


    /**
     * Set content access and required plan level based on content type and user plan
     *
     * @param array $item The content item
     * @param int $user_id The user ID
     * @param int $userPlanLevel The user's plan level
     * @return array The updated item with access information
     */
    if (!function_exists('setContentAccess')) {
    /**
     * Set content access and required plan level based on content type and user plan
     *
     * @param array $item The content item
     * @param int $user_id The user ID
     * @param int $userPlanLevel The user's plan level
     * @return array The updated item with access information
     */
    function setContentAccess($item, $user_id, $userPlanLevel) {


        $accessType = strtolower(trim($item['access'] ?? ''));
          switch ($accessType) {
              case 'free':
                  $item['has_content_access'] = 1;
                  $item['required_plan_level'] = 0;
                  break;

              case 'paid':
                  $requiredLevel = $item['plan_id'] ?? 0;
                  $item['has_content_access'] = ($userPlanLevel >= $requiredLevel) ? 1 : 0;
                  $item['required_plan_level'] = $requiredLevel;
                  break;

              case 'pay-per-view':
                  $isPurchased = Entertainment::isPurchased($item['id'], $item['type'], $user_id);
                  $item['has_content_access'] = $isPurchased ? 1 : 0;
                  $item['required_plan_level'] = 0;
                  break;

              default:
                  $item['has_content_access'] = 0;
                  $item['required_plan_level'] = 0;
                  break;
          }
        return $item;
    }

}





function bunnyIngestAndGetM3u8(string $sourceUrl, string $title): ?string
{
    $apiKey = env('BUNNY_STREAM_API_KEY');
    $libraryId = env('BUNNY_VIDEO_KEY');
    $cdnHost = env('BUNNY_CDN_HOSTNAME');
    if (!$apiKey || !$libraryId || !$cdnHost) return null;

    $base = 'https://video.bunnycdn.com/library';

    $list = Http::withHeaders([
        'AccessKey' => $apiKey,
    ])->get("{$base}/{$libraryId}/videos");

    if ($list->successful()) {
        $videos = $list->json()['items'] ?? [];
        foreach ($videos as $video) {
            if (isset($video['title']) && $video['title'] == $title) {
                $guid = $video['guid'];
                return "https://{$cdnHost}/{$guid}/playlist.m3u8";
            }
        }
    }

    $create = Http::withHeaders([
        'AccessKey' => $apiKey,
        'Content-Type' => 'application/json',
    ])->post("{$base}/{$libraryId}/videos", ['title' => $title]);

    if (!$create->successful()) return null;
    $guid = data_get($create->json(), 'guid');
    if (!$guid) return null;

    $fetch = Http::withHeaders([
        'AccessKey' => $apiKey,
        'Content-Type' => 'application/json',
    ])->post("{$base}/{$libraryId}/videos/{$guid}/fetch", [
        'url' => $sourceUrl,
    ]);
    if (!$fetch->successful()) return null;

    return "https://{$cdnHost}/{$guid}/playlist.m3u8";
}

function formatDateTimeWithTimezone($value, $type = null)
{
    if (empty($value)) {
        return null;
    }

    $settings = Cache::remember('app_datetime_settings', 3600, function () {
        return DB::table('settings')
            ->whereIn('name', ['default_date_format', 'default_time_format', 'default_time_zone'])
            ->pluck('val', 'name')
            ->toArray();
    });

    $timezone    = $settings['default_time_zone'] ?? config('app.timezone', 'UTC');
    $dateFormat  = $settings['default_date_format'] ?? 'Y-m-d';
    $timeFormat  = $settings['default_time_format'] ?? 'H:i:s';

    try {
        $carbon = Carbon::parse($value)->setTimezone($timezone);

        switch ($type) {
            case 'date':
                return $carbon->format($dateFormat);
            case 'time':
                return $carbon->format($timeFormat);
            default:
                return $carbon->format("{$dateFormat} {$timeFormat}");
        }
    } catch (Exception $e) {
        return $value;
    }
}

/**
 * Delete video from Bunny Stream by GUID or title
 * 
 * @param string $identifier Video GUID or title
 * @param bool $isGuid Whether identifier is GUID (true) or title (false)
 * @return bool Success status
 */
function bunnyDeleteVideo(string $identifier, bool $isGuid = false): bool
{
    $apiKey = env('BUNNY_STREAM_API_KEY');
    $libraryId = env('BUNNY_VIDEO_KEY');
    
    if (!$apiKey || !$libraryId) {
        \Log::warning('Bunny Stream credentials not configured');
        return false;
    }
    
    $base = 'https://video.bunnycdn.com/library';
    $videoGuid = null;
    
    // If identifier is a title, find the GUID first
    if (!$isGuid) {
        $list = Http::withHeaders(['AccessKey' => $apiKey])
            ->get("{$base}/{$libraryId}/videos");
        
        if ($list->successful()) {
            $videos = $list->json()['items'] ?? [];
            foreach ($videos as $video) {
                if (isset($video['title']) && $video['title'] == $identifier) {
                    $videoGuid = $video['guid'];
                    break;
                }
            }
        }
        
        if (!$videoGuid) {
            \Log::info("Bunny Stream video not found: {$identifier}");
            return false;
        }
    } else {
        $videoGuid = $identifier;
    }
    
    // Delete the video
    $response = Http::withHeaders(['AccessKey' => $apiKey])
        ->delete("{$base}/{$libraryId}/videos/{$videoGuid}");
    
    if ($response->successful()) {
        \Log::info("Successfully deleted Bunny Stream video: {$videoGuid}");
        return true;
    } else {
        \Log::error("Failed to delete Bunny Stream video: {$videoGuid}", [
            'status' => $response->status(),
            'body' => $response->body()
        ]);
        return false;
    }
}

/**
 * Extract GUID from Bunny Stream M3U8 URL
 * 
 * @param string $m3u8Url M3U8 URL (e.g., https://cdn.example.com/abc123/playlist.m3u8)
 * @return string|null GUID or null
 */
function extractBunnyGuid(string $m3u8Url): ?string
{
    // Extract GUID from URL pattern: https://{cdn}/{guid}/playlist.m3u8
    if (preg_match('/\/([a-f0-9\-]{36})\/playlist\.m3u8/i', $m3u8Url, $matches)) {
        return $matches[1];
    }
    return null;
}

/**
 * Delete video from Bunny Stream when file is deleted from media library
 * 
 * @param string $fileName The filename to delete
 * @return bool Success status
 */
function deleteBunnyStreamVideoByFile(string $fileName): bool
{
    // Check if Bunny Stream is active
    if (env('ACTIVE_STORAGE') != 'bunny') {
        return false;
    }
    
    // Check if file is a video
    $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', '3gp', 'm4v', 'mpg', 'mpeg'];
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (!in_array($extension, $videoExtensions)) {
        return false;
    }
    
    \Log::info("Deleting video from Bunny Stream: {$fileName}");
    
    // Delete by filename from Bunny Stream
    return bunnyDeleteVideo($fileName, false);
}