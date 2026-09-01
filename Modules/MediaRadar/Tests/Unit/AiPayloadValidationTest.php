<?php

namespace Modules\MediaRadar\Tests\Unit;

use Modules\MediaRadar\Services\EditorialAnalysisService;
use PHPUnit\Framework\TestCase;

/**
 * AI output is never trusted as-is: it is parsed and validated before any of it
 * reaches the database.
 */
class AiPayloadValidationTest extends TestCase
{
    public function test_it_accepts_a_well_formed_response(): void
    {
        $payload = EditorialAnalysisService::validateAiPayload(json_encode([
            'editorial_score' => 91,
            'suggested_category' => 'Wired Wisdom',
            'secondary_categories' => ['Business BluePrint'],
            'suggested_title' => 'Black Founders Building AI Infrastructure',
            'summary' => 'A discussion with technology founders.',
            'description' => 'Suggested editorial description.',
            'tags' => ['Artificial Intelligence', 'Technology'],
            'risk_flags' => [],
            'why_selected' => 'Strong alignment with technology programming.',
        ]));

        $this->assertSame(91, $payload['editorial_score']);
        $this->assertSame('Wired Wisdom', $payload['suggested_category']);
        $this->assertSame(['Artificial Intelligence', 'Technology'], $payload['tags']);
        $this->assertSame(['Business BluePrint'], $payload['secondary_categories']);
    }

    public function test_it_extracts_json_wrapped_in_prose_or_a_code_fence(): void
    {
        $content = "Sure! Here is the analysis:\n```json\n{\"editorial_score\": 78, \"suggested_title\": \"A Title\"}\n```\nHope that helps.";

        $payload = EditorialAnalysisService::validateAiPayload($content);

        $this->assertSame(78, $payload['editorial_score']);
        $this->assertSame('A Title', $payload['suggested_title']);
    }

    public function test_it_clamps_an_out_of_range_score(): void
    {
        $this->assertSame(100, EditorialAnalysisService::validateAiPayload('{"editorial_score": 4000}')['editorial_score']);
        $this->assertSame(0, EditorialAnalysisService::validateAiPayload('{"editorial_score": -50}')['editorial_score']);
    }

    public function test_it_drops_fields_of_the_wrong_type(): void
    {
        $payload = EditorialAnalysisService::validateAiPayload(json_encode([
            'editorial_score' => 'not a number',
            'suggested_title' => ['an', 'array'],
            'tags' => 'not an array',
            'summary' => 'A valid summary.',
        ]));

        $this->assertArrayNotHasKey('editorial_score', $payload);
        $this->assertArrayNotHasKey('suggested_title', $payload);
        $this->assertArrayNotHasKey('tags', $payload);
        $this->assertSame('A valid summary.', $payload['summary']);
    }

    public function test_it_returns_null_when_there_is_no_json(): void
    {
        $this->assertNull(EditorialAnalysisService::validateAiPayload('I could not analyse this video.'));
    }

    public function test_it_returns_null_for_malformed_json(): void
    {
        $this->assertNull(EditorialAnalysisService::validateAiPayload('{"editorial_score": }'));
    }

    public function test_it_returns_null_when_nothing_usable_survives(): void
    {
        $this->assertNull(EditorialAnalysisService::validateAiPayload('{"unexpected": "field"}'));
    }
}
