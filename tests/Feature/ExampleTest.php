<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guest_is_redirected_from_dashboard_to_login(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));
    }

    public function test_login_page_is_accessible(): void
    {
        $this->get(route('login'))
            ->assertOk();
    }
}
