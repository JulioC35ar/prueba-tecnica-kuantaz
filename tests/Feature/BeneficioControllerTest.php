<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BeneficioControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testGetData()
    {
        $response = $this->json('GET', 'api/get_data');
        $response->assertOk();
        $response->assertJson([
            'code' => 200,
            'success' => true
        ]);
    }
}
