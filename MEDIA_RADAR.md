# Media Radar

Automated YouTube + Vimeo discovery, editorial review and publishing for the
GoKoncentrate dashboard.

Media Radar searches the platforms on a schedule, filters results against
editor-defined search parameters, removes duplicates, scores and describes each
candidate, and drops it into an approval queue. Approving a candidate publishes
it as a normal GoKoncentrate movie whose video is still played from YouTube or
Vimeo.

**Module:** `Modules/MediaRadar` &nbsp;·&nbsp; **Admin:** `/app/media-radar`

---

## 1. What it does

```
SCHEDULER  ->  DISCOVERY RULE  ->  YouTube / Vimeo API  ->  normalize
                                                              |
                                                          deduplicate
                                                              |
                                                    persist the candidate
                                                              |
                                                   score + title/description
                                                              |
                                              APPROVAL QUEUE (auto or manual)
                                                              |
                                                     PUBLISHING BRIDGE
                                                              |
                                              entertainments (type = movie)
                                                              |
                                                  public app / mobile app
```

The public app and the mobile app never call YouTube or Vimeo. All provider
traffic happens in scheduled console commands and queued jobs.

---

## 2. Setup

### 2.1 Environment

Added to `.env` / `.env.example`. **Server side only** — these keys must never
appear in front-end JavaScript, the Flutter bundle or a public config file.

| Variable | Purpose |
|---|---|
| `MEDIA_RADAR_ENABLED` | Master kill switch |
| `MEDIA_RADAR_SCHEDULER_ENABLED` | Reserved for disabling the scheduled runs |
| `MEDIA_RADAR_DEFAULT_PUBLICATION_ID` | Publication these records belong to (defaults to 1) |
| `MEDIA_RADAR_QUEUE_NAME` | Queue the jobs are pushed onto |
| `YOUTUBE_API_KEY` | YouTube Data API v3 key |
| `YOUTUBE_API_PROJECT_ID` | Optional, for your own reference |
| `YOUTUBE_DAILY_SEARCH_CAP` | Local cap on `search.list` calls per day (default 90) |
| `VIMEO_ACCESS_TOKEN` | Vimeo API token |
| `VIMEO_CLIENT_ID` / `VIMEO_CLIENT_SECRET` | Vimeo app credentials |
| `AI_EDITORIAL_MODEL` | Recorded against each analysis for auditing |
| `MEDIA_RADAR_POSTER_RATIO` / `MEDIA_RADAR_POSTER_WIDTH` | Cover-art crop output |

The AI pass reuses the OpenAI key already stored in the `settings` table under
`ChatGPT_key`, through the existing `App\Services\ChatGTPService`. No second AI
integration was added.

### 2.2 Migrations

```bash
php artisan migrate
```

Ten migrations run from `Modules/MediaRadar/database/migrations`:

| Table | Purpose |
|---|---|
| `media_radar_settings` | Single-row config, including the auto/manual approval switch |
| `media_discovery_rules` | The search parameters |
| `media_discovery_runs` | One row per rule per platform per run |
| `media_candidates` | One row per discovered video |
| `media_candidate_rule_matches` | Which rules matched a candidate, and on which terms |
| `media_candidate_analysis` | Score breakdown and suggested metadata |
| `media_editorial_decisions` | Audit trail of every approve / reject / edit / publish |
| `media_trusted_sources` | Watched creators |
| `media_blocked_sources` | Creators whose videos are dropped |
| _(permissions migration)_ | Creates the Media Radar permissions and grants them to `admin` and `demo_admin` |

### 2.3 Queue

The module's jobs implement `ShouldQueue`. With `QUEUE_CONNECTION=sync` they run
inline, which makes "Run now" and "Re-run analysis" block the admin request.
**For production set `QUEUE_CONNECTION=database`** (or redis); the scheduler
already runs `queue:work --tries=3 --stop-when-empty` every minute.

### 2.4 Scheduler

Registered in `app/Console/Kernel.php`:

```php
$schedule->command('media-radar:discover')->hourly()->withoutOverlapping();
$schedule->command('media-radar:watch-sources')->everySixHours()->withoutOverlapping();
$schedule->command('media-radar:expire-candidates')->dailyAt('03:30');
```

`media-radar:discover` only queues the rules whose `next_run_at` has passed, so
the per-rule interval (default 6 hours) is what actually controls frequency.

Commands you can run by hand:

```bash
php artisan media-radar:discover                 # queue every rule that is due
php artisan media-radar:discover --rule=3        # one rule, ignoring its schedule
php artisan media-radar:discover --rule=3 --sync # and run it inline, for debugging
php artisan media-radar:watch-sources --limit=10
php artisan media-radar:expire-candidates --dry-run
```

---

## 3. Search parameters

`/app/media-radar/rules`. Only two fields are required.

| Field | Required | Applied where |
|---|---|---|
| **Platform** (YouTube / Vimeo) | **yes** | Chooses the adapters to run |
| **Genre** | **yes** | Destination genre on the published movie; also the fallback search term |
| Search terms | no | `q` on YouTube, `query` on Vimeo |
| Excluded terms | no | `-term` on YouTube, and dropped after normalization on both |
| Keywords | no | Widen the query, and raise the relevance score when they match |
| Actors | no | Widen the query; names found in a video are linked to existing cast on publish |
| Type of video | no | Stored on the rule and shown to editors |
| Length (min / max seconds) | no | Mapped to YouTube's short/medium/long bucket, then enforced exactly |
| Year of release (from / to) | no | `publishedAfter` / `publishedBefore`, then enforced exactly |
| Minimum quality | no | `videoDefinition=high` on YouTube, then enforced against the reported height |
| Language / region | no | `relevanceLanguage` / `regionCode`, then compared on the two-letter prefix |
| Published within N days | no | `publishedAfter`, then enforced exactly |
| Minimum views | no | Enforced after normalization |
| Minimum editorial score | no | Threshold for automatic approval on this rule |

Anything a provider cannot filter server-side is enforced by
`Support\CandidateFilter` after normalization, and the reason a result was
dropped is counted on the discovery run.

**A note on quality.** Vimeo reports the real pixel height. YouTube only reports
`hd` or `sd`, which is treated as a floor of 720p / 480p. A rule asking for
1080p or better therefore keeps YouTube results whose resolution cannot be
confirmed, unless you tick *"Reject videos whose resolution the platform does
not report"*.

---

## 4. Approval: automatic or manual

The switch lives in two places, both writing the same setting:

* the **Media Radar dashboard**, as an inline toggle;
* **Media Radar Settings**, alongside the minimum score.

| Mode | Behaviour |
|---|---|
| **Manual** (default) | Every candidate stops at `READY_FOR_REVIEW` until a person approves it. |
| **Automatic** | A candidate is approved without a person when it scores at or above the minimum, is embeddable, and its creator is not blocked. |

Each rule can override the global switch (*Approval for this rule*: use the
global setting / manual / automatic). "Publish immediately after approval"
controls whether approval also publishes, or leaves the item approved and
waiting for a publish or a scheduled time.

Automatic approvals are still written to `media_editorial_decisions` with the
decision `auto_approved`, so the audit trail covers them.

---

## 5. Cover art

Set in Media Radar Settings.

| Mode | What is stored |
|---|---|
| **Use the YouTube / Vimeo artwork link** (default) | The best artwork URL the platform actually serves. `setBaseUrlWithFileName()` passes remote URLs through untouched, so the movie renders the platform's own image. Nothing is downloaded. |
| **Crop a poster from the provider artwork** | The same artwork is fetched once, centre-cropped to poster ratio with GD (biased slightly upward, where faces and titles usually sit), and written to `storage/app/public/movie/image/` like any other movie poster. The landscape thumbnail still points at the platform. |

For YouTube the candidates are `maxresdefault` → `sddefault` → `hqdefault` →
`mqdefault`; each is checked with a HEAD request because YouTube advertises
`maxresdefault` for every video but only serves it for some. For Vimeo the
picture sizes are sorted largest first and the signature query is stripped.

Both modes leave the artwork editable per candidate before publishing.

---

## 6. Publishing bridge

An approved candidate becomes a row in `entertainments` with `type = movie`,
using the mappings below. Media Radar does not create a parallel publishing
system.

| Candidate | Movie field |
|---|---|
| Editorial title (or original title) | `name`, plus a unique `slug` |
| Editorial description | `description` |
| Editorial summary | `short_description` |
| Poster / thumbnail | `poster_url`, `thumbnail_url` |
| Platform | `video_upload_type` = `YouTube` or `Vimeo` |
| Provider URL | `video_url_input` |
| Duration | `duration` as `HH:MM` |
| Published date | `release_date` |
| Language | `language` |
| Genre + secondary genres | `entertainment_gener_mapping` |
| Matched actors | `entertainment_talent_mapping` (existing `cast_crew` rows only) |
| Rule / settings defaults | `movie_access`, `plan_id`, `is_restricted`, `status` |

The video file itself is never downloaded, re-encoded or re-hosted, and
attribution (creator name and URL) is kept on the candidate record.

Slug collisions are resolved by appending the provider video id, never by
overwriting an existing movie.

---

## 7. Permissions

Created by the permissions migration and granted to `admin` and `demo_admin`.
The `admin` role also passes everything through the existing `Gate::before`.

| Permission | Grants |
|---|---|
| `view_media_radar` | Dashboard, queue, rules, sources, runs |
| `edit_media_radar` | Edit candidate metadata, archive, re-run analysis |
| `approve_media_radar` | Approve, reject, schedule, publish |
| `manage_media_radar_rules` | Create / edit / delete / run search parameters |
| `manage_media_radar_sources` | Trust and block creators |
| `manage_media_radar_settings` | Media Radar settings, including the approval switch |
| `add_media_radar`, `delete_media_radar` | Reserved for future use |

---

## 8. Admin API

Sanctum-authenticated, under `/api/admin/media-radar`. Every action re-checks the
permissions above. No endpoint returns provider credentials.

```
GET    /api/admin/media-radar/rules
POST   /api/admin/media-radar/rules
GET    /api/admin/media-radar/rules/{id}
PATCH  /api/admin/media-radar/rules/{id}
DELETE /api/admin/media-radar/rules/{id}
POST   /api/admin/media-radar/rules/{id}/run
GET    /api/admin/media-radar/rules/{id}/runs

GET    /api/admin/media-radar/candidates
GET    /api/admin/media-radar/candidates/{id}
PATCH  /api/admin/media-radar/candidates/{id}
POST   /api/admin/media-radar/candidates/{id}/approve      { publish_now?, notes? }
POST   /api/admin/media-radar/candidates/{id}/reject        { rejection_reason?, notes?, block_creator? }
POST   /api/admin/media-radar/candidates/{id}/archive
POST   /api/admin/media-radar/candidates/{id}/analyze
POST   /api/admin/media-radar/candidates/{id}/schedule      { scheduled_for }
POST   /api/admin/media-radar/candidates/{id}/publish
POST   /api/admin/media-radar/candidates/{id}/trust
POST   /api/admin/media-radar/candidates/{id}/block         { reason? }

GET    /api/admin/media-radar/sources
DELETE /api/admin/media-radar/sources/{id}/trust
DELETE /api/admin/media-radar/sources/{id}/block

GET    /api/admin/media-radar/runs
GET    /api/admin/media-radar/runs/{id}
POST   /api/admin/media-radar/runs/{id}/retry

GET    /api/admin/media-radar/status
GET    /api/admin/media-radar/provider-status
```

---

## 9. Candidate lifecycle

```
DISCOVERED -> NORMALIZED -> ENRICHED -> DEDUPLICATED -> ANALYZING -> READY_FOR_REVIEW
                                                                          |
                                                              APPROVED  /   \  REJECTED
                                                                 |
                                                            SCHEDULED -> PUBLISHED
```

Failure states: `PROVIDER_ERROR`, `ANALYSIS_ERROR`, `INVALID`,
`EMBED_UNAVAILABLE`, `ARCHIVED`. Transitions are validated by
`Support\CandidateStatus`; an invalid one is refused rather than applied.

The candidate row is written **before** analysis runs, so an AI or API outage
never loses a discovered video — it sits in `ANALYSIS_ERROR` and can be retried
from the candidate screen.

---

## 10. Quota and rate limits

* **YouTube** — `search.list` draws on a small daily bucket, so each rule issues
  at most 3 search calls per run (terms are OR-ed together, four per query), and
  the module refuses further searches once `YOUTUBE_DAILY_SEARCH_CAP` is hit
  that day. Metadata comes from the much cheaper `videos.list`, batched 50 ids
  at a time. Trusted creators are checked through their uploads playlist instead
  of search.
* **Vimeo** — responses use field filtering, rate-limit headers are recorded, and
  a 429 puts the platform into a cooldown that later runs skip rather than
  hammer.
* Both are surfaced on the dashboard and the settings screen, with the last
  error and the last successful request.

Retries: immediate, +1 min, +5 min, +30 min, +2 h, each with jitter. Permanent
failures (bad credentials, deleted video) are recorded and not retried.

---

## 11. Tests

```bash
vendor/bin/phpunit Modules/MediaRadar/Tests/Unit
```

67 unit tests covering provider normalization (both platforms), duplicate
detection, the rule filters, the scoring model, the state machine, AI
structured-output validation, and the candidate → movie mapping. They run
without a database or network.

Integration and end-to-end coverage (real migrations, queue jobs, publishing a
candidate through to the public app) is **not** included and needs a working
test database; the unit suite deliberately avoids one so it runs anywhere.

---

## 12. Phase 1 boundaries

Deliberately not built:

* TikTok / Instagram / Facebook ingestion;
* downloading or re-hosting third-party video;
* fully autonomous publishing of third-party media — trusted sources default to
  `approval_required`, and the `auto_publish` mode is stored but only takes
  effect if you choose it;
* transcript analysis;
* self-modifying AI behaviour. Editorial decisions are recorded for future use
  as controlled context, and nothing reads them back automatically yet.

Every major record already carries a `publication_id`, so a second publication
can be added without a schema change.
