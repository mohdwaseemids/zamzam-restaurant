<?php

namespace Database\Factories;

use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'spent_on' => today(),
            'category' => fake()->randomElement(array_keys(Expense::CATEGORIES)),
            'description' => fake()->sentence(4),
            'amount' => fake()->numberBetween(100, 5000),
            'paid_via' => 'cash',
        ];
    }
}
