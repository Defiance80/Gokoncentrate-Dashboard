<?php

namespace Modules\Frontend\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;

/**
 * The sitemap for the public funnel.
 *
 * The website exists to catch searches and hand people to the app, so the only
 * pages worth listing are the ones a stranger can actually open: title detail
 * pages. Everything else on this site redirects a signed-out visitor to /login
 * (see RequireLogin), and listing a page that answers 302 teaches search
 * engines to distrust the file.
 */
class SitemapController extends Controller
{
    public function index()
    {
        $urls = Cache::remember('gk_sitemap_urls', now()->addHours(6), function () {
            $urls = [];

            // Films and series live in the same table, split by `type`, and each
            // goes to its own detail route.
            if (Schema::hasTable('entertainments')) {
                $rows = DB::table('entertainments')
                    ->select('slug', 'type', 'updated_at')
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->whereNotNull('slug')
                    ->where('slug', '!=', '')
                    ->get();

                foreach ($rows as $row) {
                    $route = $row->type === 'tvshow' ? 'tvshow-details' : 'movie-details';
                    $urls[] = [
                        'loc' => url("/{$route}/{$row->slug}"),
                        'lastmod' => $row->updated_at,
                        // A title page is the landing point for a search, so it
                        // outranks everything else we publish.
                        'priority' => '0.9',
                    ];
                }
            }

            if (Schema::hasTable('videos')) {
                $rows = DB::table('videos')
                    ->select('slug', 'updated_at')
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->whereNotNull('slug')
                    ->where('slug', '!=', '')
                    ->get();

                foreach ($rows as $row) {
                    $urls[] = [
                        'loc' => url("/video-details/{$row->slug}"),
                        'lastmod' => $row->updated_at,
                        'priority' => '0.7',
                    ];
                }
            }

            return $urls;
        });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
             . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1) . "</loc>\n";
            if (!empty($url['lastmod'])) {
                try {
                    $xml .= '    <lastmod>'
                          . \Carbon\Carbon::parse($url['lastmod'])->toAtomString()
                          . "</lastmod>\n";
                } catch (\Throwable $e) {
                    // An unparseable timestamp is better omitted than wrong.
                }
            }
            $xml .= '    <changefreq>weekly</changefreq>' . "\n";
            $xml .= '    <priority>' . $url['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
