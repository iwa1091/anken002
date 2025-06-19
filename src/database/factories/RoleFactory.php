<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     * 対応するモデルの名前
     *
     * @var string
     */
    protected $model = Role::class;

    /**
     * Define the model's default state.
     * モデルのデフォルトの状態を定義
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // デフォルトのロール名を定義
        // 'staff' などの具体的なロール名を設定するか、より汎用的な名前を生成する
        // 今回は、管理者とスタッフのロールが中心なので、デフォルトでは staff を想定
        $roles = ['admin', 'staff'];
        $randomRoleName = $this->faker->randomElement($roles);

        return [
            'name' => $randomRoleName,
        ];
    }

    /**
     * Indicate that the role is an administrator.
     * ロールが管理者である状態を示す
     *
     * @return static
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'admin',
        ]);
    }

    /**
     * Indicate that the role is a staff member.
     * ロールがスタッフである状態を示す
     *
     * @return static
     */
    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'staff',
        ]);
    }
}
