<?php

namespace Modules\PublisherStudio\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * Storage connection options (scaffold).
 *
 * Lets an admin record which object store publisher media should use once
 * publishers go live: the native Hostinger virtual storage (local disk) or an
 * Amazon S3 bucket. Values are stored in the shared settings table (key/value)
 * under the "storage" datatype. This is intentionally passive: saving the S3
 * credentials does NOT re-point live uploads yet -- the connective option is
 * captured so it can be activated when publishers are onboarded.
 */
class StorageSettingsController extends Controller
{
    private array $keys = [
        'publisher_storage_driver',   // hostinger_local | s3
        'publisher_s3_key',
        'publisher_s3_secret',
        'publisher_s3_region',
        'publisher_s3_bucket',
        'publisher_s3_endpoint',
        'publisher_s3_url',
    ];

    public function edit()
    {
        $settings = [];
        foreach ($this->keys as $key) {
            $settings[$key] = Setting::get($key);
        }
        if (empty($settings['publisher_storage_driver'])) {
            $settings['publisher_storage_driver'] = 'hostinger_local';
        }

        return view('publisherstudio::backend.storage.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'publisher_storage_driver' => ['required', 'in:hostinger_local,s3'],
            'publisher_s3_key'         => ['nullable', 'string', 'max:255'],
            'publisher_s3_secret'      => ['nullable', 'string', 'max:255'],
            'publisher_s3_region'      => ['nullable', 'string', 'max:120'],
            'publisher_s3_bucket'      => ['nullable', 'string', 'max:190'],
            'publisher_s3_endpoint'    => ['nullable', 'string', 'max:255'],
            'publisher_s3_url'         => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($this->keys as $key) {
            Setting::add($key, $data[$key] ?? '', 'string', 'misc');
        }

        return redirect()->route('backend.publisher-storage.edit')
            ->with('status', 'Storage connection settings saved. (Inactive until activated for live uploads.)');
    }
}
