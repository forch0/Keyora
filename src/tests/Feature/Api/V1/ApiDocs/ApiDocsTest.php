<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\ApiDocs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiDocsTest extends TestCase
{
    use RefreshDatabase;

    public function test_openapi_spec_endpoint_returns_valid_json(): void
    {
        $response = $this->getJson('/docs/api.json');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'openapi',
            'info' => ['title', 'version'],
            'paths',
        ]);
    }

    public function test_openapi_spec_has_correct_title(): void
    {
        $response = $this->getJson('/docs/api.json');

        $response->assertStatus(200);
        $response->assertJsonPath('info.title', 'Keyora API Documentation');
    }

    public function test_openapi_spec_has_correct_version(): void
    {
        $response = $this->getJson('/docs/api.json');

        $response->assertStatus(200);
        $response->assertJsonPath('info.version', '1.0.0');
    }

    public function test_openapi_spec_includes_auth_endpoints(): void
    {
        $response = $this->getJson('/docs/api.json');

        $response->assertStatus(200);
        $data = $response->json('paths', []);
        $this->assertArrayHasKey('/auth/login', $data);
        $this->assertArrayHasKey('/auth/register', $data);
    }

    public function test_openapi_spec_includes_all_28_groups(): void
    {
        $response = $this->getJson('/docs/api.json');

        $response->assertStatus(200);
        $tags = collect($response->json('paths', []))
            ->flatten(1)
            ->flatMap(fn ($endpoint) => $endpoint['tags'] ?? [])
            ->unique()
            ->values();

        $this->assertGreaterThanOrEqual(28, $tags->count(), 'Expected at least 28 API groups in the OpenAPI spec.');
    }

    public function test_openapi_spec_has_bearer_security_scheme(): void
    {
        $response = $this->getJson('/docs/api.json');

        $response->assertStatus(200);
        $securitySchemes = $response->json('components.securitySchemes', []);
        $this->assertNotEmpty($securitySchemes, 'Expected at least one security scheme in the OpenAPI spec.');
    }

    public function test_html_docs_page_is_accessible(): void
    {
        $response = $this->get('/docs/api');

        $response->assertStatus(200);
    }
}
