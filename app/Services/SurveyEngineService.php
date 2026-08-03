<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\FormResponse;
use App\Models\FormVersion;
use App\Models\User;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SurveyEngineService
{
    /**
     * Submit survey response for a form version linked to an activity.
     */
    public function submitResponse(
        FormVersion $formVersion,
        User $respondent,
        array $answers,
        ?Activity $activity = null,
        ?float $latitude = null,
        ?float $longitude = null
    ): FormResponse {
        $score = $this->calculateScore($formVersion, $answers);

        return FormResponse::create([
            'client_uuid' => (string) Str::uuid(),
            'sequence' => 1,
            'form_version_id' => $formVersion->id,
            'activity_id' => $activity?->id,
            'respondent_id' => $respondent->id,
            'response_data' => $answers,
            'score' => $score,
            'submission_latitude' => $latitude,
            'submission_longitude' => $longitude,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Calculate score percentage based on question weights and answers.
     */
    public function calculateScore(FormVersion $formVersion, array $answers): float
    {
        $schema = $formVersion->schema_definition;
        $questions = $schema['questions'] ?? [];

        if (empty($questions)) {
            return 100.0;
        }

        $totalMaxPoints = 0.0;
        $earnedPoints = 0.0;

        foreach ($questions as $q) {
            $qId = $q['id'] ?? null;
            $weight = (float) ($q['weight'] ?? 1.0);
            $totalMaxPoints += $weight;

            if ($qId && isset($answers[$qId])) {
                $userAnswer = $answers[$qId];
                if (isset($q['correct_answer'])) {
                    if ($userAnswer == $q['correct_answer']) {
                        $earnedPoints += $weight;
                    }
                } else {
                    // Non-scored informational question gets full credit if answered
                    $earnedPoints += $weight;
                }
            }
        }

        return $totalMaxPoints > 0 ? round(($earnedPoints / $totalMaxPoints) * 100.0, 2) : 100.0;
    }
}
