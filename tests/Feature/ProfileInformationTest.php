<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_information_can_be_updated(): void
    {
        $this->actingAs($user = User::factory()->create());

        $this->put('/user/profile-information', [
            'name' => 'Test Name',
            'email' => 'test@example.com',
        ]);

        $this->assertEquals('Test Name', $user->fresh()->name);
        $this->assertEquals('test@example.com', $user->fresh()->email);
    }

    public function test_fatsecret_locale_preferences_can_be_updated(): void
    {
        $this->actingAs($user = User::factory()->create());

        $this->put('/user/profile-information', [
            'name' => $user->name,
            'email' => $user->email,
            'fatsecret_region' => 'BE',
            'fatsecret_language' => 'fr',
        ]);

        $user->refresh();

        $this->assertSame('BE', $user->fatsecret_region);
        $this->assertSame('fr', $user->fatsecret_language);
    }
}
