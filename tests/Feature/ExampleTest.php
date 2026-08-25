<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_serves_the_landing_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('PayrollPro', false);
    }
}
