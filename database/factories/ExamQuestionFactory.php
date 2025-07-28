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

        $options = [
            'A' => $this->faker->sentence(),
            'B' => $this->faker->sentence(),
            'C' => $this->faker->sentence(),
            'D' => $this->faker->sentence(),
        ];

        return [
            'question' => $this->faker->randomElement($questions),
            'options' => $options,
            'correct_answer' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
            'marks' => $this->faker->numberBetween(1, 5),
            'exam_id' => Exam::factory(),
            'tenant_id' => function (array $attributes) {
                return Exam::find($attributes['exam_id'])->tenant_id ?? 1;
            },
        ];
    }

    /**
     * Create a multiple choice question.
     */
    public function multipleChoice(): static
    {
        return $this->state(fn (array $attributes) => [
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
            'options' => [
                'A' => 'True',
                'B' => 'False',
            ],
            'correct_answer' => $this->faker->randomElement(['A', 'B']),
        ]);
    }

    /**
     * Create a high-value question.
     */
    public function highValue(): static
    {
        return $this->state(fn (array $attributes) => [
            'marks' => $this->faker->numberBetween(5, 10),
        ]);
    }
}
