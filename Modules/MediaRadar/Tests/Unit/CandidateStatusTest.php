<?php

namespace Modules\MediaRadar\Tests\Unit;

use Modules\MediaRadar\Support\CandidateStatus;
use PHPUnit\Framework\TestCase;

class CandidateStatusTest extends TestCase
{
    public function test_the_happy_path_is_allowed_step_by_step(): void
    {
        $path = [
            CandidateStatus::DISCOVERED,
            CandidateStatus::NORMALIZED,
            CandidateStatus::ENRICHED,
            CandidateStatus::DEDUPLICATED,
            CandidateStatus::ANALYZING,
            CandidateStatus::READY_FOR_REVIEW,
            CandidateStatus::APPROVED,
            CandidateStatus::SCHEDULED,
            CandidateStatus::PUBLISHED,
        ];

        for ($i = 0; $i < count($path) - 1; $i++) {
            $this->assertTrue(
                CandidateStatus::canTransition($path[$i], $path[$i + 1]),
                $path[$i].' should reach '.$path[$i + 1]
            );
        }
    }

    public function test_a_discovered_candidate_cannot_jump_straight_to_published(): void
    {
        $this->assertFalse(CandidateStatus::canTransition(CandidateStatus::DISCOVERED, CandidateStatus::PUBLISHED));
    }

    public function test_a_published_candidate_cannot_be_rejected(): void
    {
        $this->assertFalse(CandidateStatus::canTransition(CandidateStatus::PUBLISHED, CandidateStatus::REJECTED));
    }

    public function test_a_published_candidate_can_only_be_archived(): void
    {
        $this->assertTrue(CandidateStatus::canTransition(CandidateStatus::PUBLISHED, CandidateStatus::ARCHIVED));
    }

    public function test_a_rejected_candidate_can_be_reopened_for_review(): void
    {
        $this->assertTrue(CandidateStatus::canTransition(CandidateStatus::REJECTED, CandidateStatus::READY_FOR_REVIEW));
    }

    public function test_analysis_failures_can_be_retried(): void
    {
        $this->assertTrue(CandidateStatus::canTransition(CandidateStatus::ANALYSIS_ERROR, CandidateStatus::ANALYZING));
    }

    public function test_the_same_status_is_always_allowed(): void
    {
        $this->assertTrue(CandidateStatus::canTransition(CandidateStatus::APPROVED, CandidateStatus::APPROVED));
    }

    public function test_reviewable_statuses_are_the_ones_an_editor_acts_on(): void
    {
        $this->assertContains(CandidateStatus::READY_FOR_REVIEW, CandidateStatus::reviewable());
        $this->assertNotContains(CandidateStatus::PUBLISHED, CandidateStatus::reviewable());
    }

    public function test_terminal_statuses(): void
    {
        $this->assertTrue(CandidateStatus::isTerminal(CandidateStatus::PUBLISHED));
        $this->assertFalse(CandidateStatus::isTerminal(CandidateStatus::READY_FOR_REVIEW));
    }
}
