<?php

use App\Models\System;
use Inertia\Testing\AssertableInertia as Assert;

test('the homepage displays active featured systems', function () {
    System::factory()->create([
        'name' => 'Featured Gaming PC',
        'is_active' => true,
        'is_featured' => true,
    ]);

    System::factory()->create([
        'name' => 'Inactive Gaming PC',
        'is_active' => false,
        'is_featured' => true,
    ]);

    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/index')
            ->has('featuredSystems', 1)
            ->where(
                'featuredSystems.0.name',
                'Featured Gaming PC',
            )
        );
});