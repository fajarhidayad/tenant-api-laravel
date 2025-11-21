<?php

namespace Database\Factories;

use App\Enums\TenantType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tenantType = fake()->randomElement(TenantType::cases());

        $data = [
            'tenant_name' => fake()->company(),
            'tenant_type' => $tenantType,
            'tenant_phone' => fake()->phoneNumber(),
            'tenant_address' => fake()->address(),
            'booth_num' => null,
            'area_sm' => null,
        ];

        // Add conditional fields based on tenant type
        if ($tenantType === TenantType::BOOTH) {
            $data['booth_num'] = 'B-'.str_pad(fake()->numberBetween(1, 100), 3, '0', STR_PAD_LEFT);
        } elseif ($tenantType === TenantType::SPACE_ONLY) {
            $data['area_sm'] = fake()->randomFloat(2, 5, 50);
        }

        return $data;
    }

    public function foodTruck(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_name' => fake()->company().' Food Truck',
            'tenant_type' => TenantType::FOOD_TRUCK,
            'booth_num' => null,
            'area_sm' => null,
        ]);
    }

    public function booth(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_type' => TenantType::BOOTH,
            'booth_num' => 'B-'.str_pad(fake()->numberBetween(1, 100), 3, '0', STR_PAD_LEFT),
            'area_sm' => null,
        ]);
    }

    public function spaceOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_name' => fake()->company().' Space',
            'tenant_type' => TenantType::SPACE_ONLY,
            'booth_num' => null,
            'area_sm' => fake()->randomFloat(2, 5, 50),
        ]);
    }
}
