<?php

namespace App\Console\Commands;

use App\Models\VeeMagSection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Fills in veemag_sections.synopsis - the short blurb the app shows under
 * the player while a section plays.
 *
 * Source material, in order of preference:
 *   1. the publisher's own description on the section
 *   2. the video's description from the YouTube Data API
 *
 * That text is then reduced to <= 500 characters. When an LLM key is
 * configured the reduction is a real summary; without one it falls back to
 * trimming at a sentence boundary, which still reads cleanly and needs no
 * third-party service.
 */
class GenerateVeeMagSynopses extends Command
{
    protected $signature = 'veemag:synopses
        {--force : Rewrite synopses that already exist}
        {--limit=0 : Stop after this many sections (0 = no limit)}';

    protected $description = 'Generate the short synopsis shown under each VeeMag section';

    private const MAX = 500;

    public function handle(): int
    {
        $query = VeeMagSection::query()->orderBy('issue_id')->orderBy('order_index');

        if (! $this->option('force')) {
            $query->where(fn ($q) => $q->whereNull('synopsis')->orWhere('synopsis', ''));
        }

        if ($limit = (int) $this->option('limit')) {
            $query->limit($limit);
        }

        $sections = $query->get();

        if ($sections->isEmpty()) {
            $this->info('Nothing to do - every section already has a synopsis.');

            return self::SUCCESS;
        }

        $this->info("Generating synopses for {$sections->count()} section(s)...");

        $written = 0;
        $skipped = 0;

        foreach ($sections as $section) {
            $source = $this->sourceText($section);

            if ($source === '') {
                $skipped++;
                $this->line("  - skipped #{$section->id} {$section->title} (no source text)");
                continue;
            }

            $section->update(['synopsis' => $this->condense($source, $section->title)]);
            $written++;
        }

        $this->info("Done. {$written} written, {$skipped} skipped.");

        return self::SUCCESS;
    }

    /** The richest text we hold for this section. */
    private function sourceText(VeeMagSection $section): string
    {
        $own = trim(strip_tags((string) $section->description));

        if (Str::length($own) >= 80) {
            return $own;
        }

        $remote = $this->youtubeDescription($section);

        return Str::length($remote) > Str::length($own) ? $remote : $own;
    }

    /** The video's own description, via the YouTube Data API. */
    private function youtubeDescription(VeeMagSection $section): string
    {
        if (Str::lower((string) $section->video_upload_type) !== 'youtube') {
            return '';
        }

        $key = (string) config('services.youtube.key');

        if ($key === '') {
            return '';
        }

        if (! preg_match('~(?:youtu\.be/|watch\?v=|embed/|shorts/)([A-Za-z0-9_-]{11})~',
            (string) $section->video_url_input, $m)) {
            return '';
        }

        try {
            $response = Http::timeout(15)->get('https://www.googleapis.com/youtube/v3/videos', [
                'part' => 'snippet',
                'id'   => $m[1],
                'key'  => $key,
            ]);

            if (! $response->successful()) {
                return '';
            }

            return trim((string) data_get($response->json(), 'items.0.snippet.description', ''));
        } catch (\Throwable $e) {
            $this->warn("  ! YouTube lookup failed for #{$section->id}: {$e->getMessage()}");

            return '';
        }
    }

    /** Reduce source text to a readable blurb of at most MAX characters. */
    private function condense(string $text, string $title): string
    {
        $text = $this->tidy($text);

        if (Str::length($text) <= self::MAX) {
            return $text;
        }

        $summary = $this->summariseWithLlm($text, $title);

        if ($summary !== '') {
            return Str::limit($summary, self::MAX, '');
        }

        return $this->trimToSentence($text);
    }

    /**
     * Strip the boilerplate that collects at the end of video descriptions -
     * subscribe pitches, socials, bare links - so the blurb stays editorial.
     */
    private function tidy(string $text): string
    {
        $text = preg_replace('~https?://\S+~', '', $text) ?? $text;
        $text = preg_replace('~^\s*(subscribe|follow us|instagram|twitter|tiktok|facebook|#\w+).*$~im', '', $text) ?? $text;
        $text = preg_replace('~[ \t]+~', ' ', $text) ?? $text;
        $text = preg_replace('~\n{2,}~', "\n", $text) ?? $text;

        return trim($text);
    }

    /** Cut at the last sentence that fits, rather than mid-word. */
    private function trimToSentence(string $text): string
    {
        $slice = Str::substr($text, 0, self::MAX);
        $end = max(
            (int) strrpos($slice, '. '),
            (int) strrpos($slice, '! '),
            (int) strrpos($slice, '? ')
        );

        if ($end > 120) {
            return trim(Str::substr($slice, 0, $end + 1));
        }

        return rtrim(Str::limit($slice, self::MAX - 3, ''), " \t\n,;:-") . '...';
    }

    /**
     * Summarise with an LLM when one is configured. Returns '' when no key
     * is set, so the command works out of the box without a paid service.
     */
    private function summariseWithLlm(string $text, string $title): string
    {
        $key = (string) config('services.openai.key');

        if ($key === '') {
            return '';
        }

        try {
            $response = Http::timeout(45)
                ->withToken($key)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.model', 'gpt-4o'),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You write short synopses for a video magazine. '
                                . 'Reply with one paragraph of at most 450 characters, in plain prose, '
                                . 'describing what the viewer will watch. No marketing language, no hashtags, '
                                . 'no links, no quotation marks around the answer.',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Title: {$title}\n\nSource description:\n{$text}",
                        ],
                    ],
                    'temperature' => 0.4,
                ]);

            if (! $response->successful()) {
                return '';
            }

            return trim((string) data_get($response->json(), 'choices.0.message.content', ''));
        } catch (\Throwable $e) {
            $this->warn("  ! LLM summary failed: {$e->getMessage()}");

            return '';
        }
    }
}
