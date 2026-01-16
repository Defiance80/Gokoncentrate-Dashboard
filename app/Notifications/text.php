function setBaseUrlWithFileName($url = '')
{

    if (empty($url)) {
        return setDefaultImage();
    }

    if (filter_var($url, FILTER_VALIDATE_URL) && checkImageExists($url)) {
        return $url;
    }

    $fileName = basename(parse_url($url, PHP_URL_PATH));
    if (!$fileName) {
        return setDefaultImage();
    }

    $activeDisk = env('ACTIVE_STORAGE', 'local');

    switch ($activeDisk) {
        case 'local':
            $filePath = public_path("storage/GoKoncentrate-laravel/$fileName");
            if (file_exists($filePath)) {
                return asset("storage/GoKoncentrate-laravel/$fileName");
            }
            break;

        default:
            $baseUrl = env('DO_SPACES_URL');
            $filePath = "$baseUrl/GoKoncentrate-laravel/$fileName";

            if (checkImageExists($filePath)) {
                return $filePath;
            }
            break;
    }

    // Return default image if file doesn't exist
    return setDefaultImage();
}
