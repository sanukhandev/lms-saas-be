<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\ExamQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExamQuestion>
 */
class ExamQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $questionTypes = ['multiple_choice', 'true_false', 'short_answer', 'essay'];
        $type = $this->faker->randomElement($questionTypes);

        $questions = [
            'What is the primary purpose of this concept?',
            'Which of the following best describes the functionality?',
            'How would you implement this feature?',
            'What are the main advantages of this approach?',
            'Which statement is correct regarding this topic?',
            'What is the expected output of this code?',
            'How do you handle errors in this scenario?',
            'What is the difference between these two methods?',
            'Which design pattern is most appropriate here?',
            'What are the performance implications of this solution?',
            'How would you optimize this implementation?',
            'What security considerations should be taken into account?',
            'Which testing strategy would you recommend?',
            'How does this relate to the overall architecture?',
            'What are the scalability concerns with this approach?'
        ];

        return [
            'question' => $this->faker->randomElement($questions),
            'question_type' => $type,
            'options' => $this->generateOptions($type),
            'correct_answer' => $this->generateCorrectAnswer($type),
            'points' => $this->faker->numberBetween(1, 10),
            'order' => $this->faker->numberBetween(1, 50),
            'explanation' => $this->faker->paragraph(),
            'exam_id' => Exam::factory(),
        ];
    }

    /**
     * Generate options based on question type.
     */
    private function generateOptions(string $type): array
    {
        switch ($type) {
            case 'multiple_choice':
                return [
                    'A' => $this->faker->sentence(),
                    'B' => $this->faker->sentence(),
                    'C' => $this->faker->sentence(),
                    'D' => $this->faker->sentence(),
                ];
            case 'true_false':
                return [
                    'A' => 'True',
                    'B' => 'False',
                ];
            case 'short_answer':
            case 'essay':
                return [];
            default:
                return [];
        }
    }

    /**
     * Generate correct answer based on question type.
     */
    private function generateCorrectAnswer(string $type): string
    {
        switch ($type) {
            case 'multiple_choice':
                return $this->faker->randomElement(['A', 'B', 'C', 'D']);
            case 'true_false':
                return $this->faker->randomElement(['A', 'B']);
            case 'short_answer':
                return $this->faker->sentence();
            case 'essay':
                return $this->faker->paragraph();
            default:
                return 'A';
        }
    }

    /**
     * Create a multiple choice question.
     */
    public function multipleChoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'question_type' => 'multiple_choice',
            'options' => [
                'A' => $this->faker->sentence(),
                'B' => $this->faker->sentence(),
                'C' => $this->faker->sentence(),
                'D' => $this->faker->sentence(),
            ],
            'correct_answer' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
        ]);
    }

    /**
     * Create a true/false question.
     */
    public function trueFalse(): static
    {
        return $this->state(fn (array $attributes) => [
            'question_type' => 'true_false',
            'options' => [
                'A' => 'True',
                'B' => 'False',
            ],
            'correct_answer' => $this->faker->randomElement(['A', 'B']),
        ]);
    }

    /**
     * Create an essay question.
     */
    public function essay(): static
    {
        return $this->state(fn (array $attributes) => [
            'question_type' => 'essay',
            'options' => [],
            'correct_answer' => $this->faker->paragraph(),
            'points' => $this->faker->numberBetween(10, 25),
        ]);
    }
}
