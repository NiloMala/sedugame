<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Models\Question;
use Illuminate\Support\Str;

/**
 * Corrige uma resposta e calcula a pontuação, conforme a seção 13/14 do brief
 * e docs/02-database-mysql.md (Haversine em vez de PostGIS). Regras vêm de
 * PlatformSetting::current()->scoring_rules — configuráveis pelo admin, nunca
 * hardcoded aqui além dos defaults.
 */
class ScoringService
{
    /**
     * @return array{is_correct: bool, score: int, distance_meters: ?float}
     */
    public function score(Question $question, array $payload, int $hintPenalty, int $timeSpentSeconds, int $streakCount): array
    {
        $rules = PlatformSetting::current()->scoring_rules;
        $baseScore = $question->max_score ?: ($rules['base_score'] ?? 1000);

        [$isCorrect, $rawScore, $distanceMeters] = match ($question->type) {
            'map_location' => $this->scoreMapLocation($question, $payload, $baseScore, $rules),
            'multiple_choice' => [$this->multipleChoiceCorrect($question, $payload), $baseScore, null],
            'fill_blank' => [$this->fillBlankCorrect($question, $payload), $baseScore, null],
            'ordering' => [$this->orderingCorrect($question, $payload), $baseScore, null],
            'matching' => [$this->matchingCorrect($question, $payload), $baseScore, null],
            'short_answer' => [$this->shortAnswerCorrect($question, $payload), $baseScore, null],
            default => [$this->singleChoiceCorrect($question, $payload), $baseScore, null], // single_choice, true_false
        };

        $score = max(0, $rawScore - $hintPenalty);

        if ($isCorrect) {
            if (($question->time_limit_seconds ?? 0) > 0 && $timeSpentSeconds <= $question->time_limit_seconds * 0.5) {
                $score += $rules['fast_answer_bonus'] ?? 0;
            }
            if ($streakCount >= 2) { // essa resposta seria a 3ª+ correta seguida
                $score += $rules['streak_bonus'] ?? 0;
            }
        } else {
            $score = 0;
        }

        return ['is_correct' => $isCorrect, 'score' => (int) $score, 'distance_meters' => $distanceMeters];
    }

    /**
     * Fórmula de Haversine — ver docs/02-database-mysql.md. Retorna metros.
     */
    public static function haversineDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    private function scoreMapLocation(Question $question, array $payload, int $baseScore, array $rules): array
    {
        $target = $question->location;
        if (! $target || ! isset($payload['latitude'], $payload['longitude'])) {
            return [false, 0, null];
        }

        $distance = self::haversineDistanceMeters(
            (float) $payload['latitude'], (float) $payload['longitude'],
            (float) $target->latitude, (float) $target->longitude
        );

        $bands = $rules['distance_bands_meters'] ?? [];
        $score = 0;
        $isCorrect = false;

        foreach ($bands as $index => $band) {
            if ($band['max'] === null || $distance <= $band['max']) {
                $score = $band['score'];
                $isCorrect = $index === 0; // só a melhor faixa conta como "acerto" pra fins de sequência/streak
                break;
            }
        }

        if ($target->accepted_radius_meters > 0 && $distance <= $target->accepted_radius_meters) {
            $isCorrect = true;
            $score = max($score, $baseScore);
        }

        return [$isCorrect, $score, round($distance, 2)];
    }

    private function singleChoiceCorrect(Question $question, array $payload): bool
    {
        $selected = $payload['selected_option_id'] ?? null;

        return $selected && $question->options->firstWhere('id', $selected)?->is_correct === true;
    }

    private function multipleChoiceCorrect(Question $question, array $payload): bool
    {
        $selected = collect($payload['selected_option_ids'] ?? [])->sort()->values();
        $correct = $question->options->where('is_correct', true)->pluck('id')->sort()->values();

        return $selected->all() === $correct->all();
    }

    private function shortAnswerCorrect(Question $question, array $payload): bool
    {
        $answer = $this->normalizeText($payload['answer_text'] ?? '');
        if ($answer === '') {
            return false;
        }

        return $question->options->where('is_correct', true)
            ->contains(fn ($option) => $this->normalizeText($option->text) === $answer);
    }

    private function fillBlankCorrect(Question $question, array $payload): bool
    {
        $answers = $payload['answer_text'] ?? [];
        if (! is_array($answers) || empty($answers)) {
            return false;
        }

        $acceptedByBlank = $question->options->where('is_correct', true)->groupBy('order');

        foreach ($answers as $index => $answer) {
            $accepted = $acceptedByBlank->get($index, collect());
            $normalized = $this->normalizeText($answer);
            if (! $accepted->contains(fn ($option) => $this->normalizeText($option->text) === $normalized)) {
                return false;
            }
        }

        return true;
    }

    private function orderingCorrect(Question $question, array $payload): bool
    {
        $submitted = $payload['ordered_option_ids'] ?? [];
        $correct = $question->options->sortBy('order')->pluck('id')->values()->all();

        return $submitted === $correct;
    }

    private function matchingCorrect(Question $question, array $payload): bool
    {
        $submitted = collect($payload['matches'] ?? []);
        $leftOptions = $question->options->where('side', 'left');
        $rightByOrder = $question->options->where('side', 'right')->keyBy('order');

        if ($submitted->count() !== $leftOptions->count()) {
            return false;
        }

        foreach ($leftOptions as $left) {
            $expectedRightId = $rightByOrder->get($left->order)?->id;
            $match = $submitted->firstWhere('left_option_id', $left->id);
            if (! $match || (int) ($match['right_option_id'] ?? null) !== $expectedRightId) {
                return false;
            }
        }

        return true;
    }

    private function normalizeText(string $value): string
    {
        $value = Str::of($value)->trim()->lower()->ascii()->toString();

        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }
}
