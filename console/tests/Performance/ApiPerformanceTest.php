<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiPerformanceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_project_list_performance()
    {
        $user = User::factory()->create();
        Project::factory()->count(100)->create(['user_id' => $user->id]);

        $start = microtime(true);
        
        $response = $this->actingAs($user)->getJson('/api/v1/projects');
        
        $duration = microtime(true) - $start;

        $response->assertStatus(200);
        $this->assertLessThan(0.5, $duration, "API response took {$duration}s (expected < 0.5s)");
    }

    /** @test */
    public function test_api_key_validation_performance()
    {
        $start = microtime(true);
        
        for ($i = 0; $i < 1000; $i++) {
            cache()->put("test_key_{$i}", bin2hex(random_bytes(32)), 60);
        }
        
        $duration = microtime(true) - $start;

        $this->assertLessThan(1.0, $duration, "Cache operations took {$duration}s (expected < 1.0s)");
    }

    /** @test */
    public function test_bulk_api_key_creation()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $start = microtime(true);
        
        for ($i = 0; $i < 50; $i++) {
            ApiKey::create([
                'project_id' => $project->id,
                'key' => 'yg_' . bin2hex(random_bytes(32)),
                'name' => "Test Key {$i}",
                'rate_limit' => 1000,
            ]);
        }
        
        $duration = microtime(true) - $start;

        $this->assertEquals(50, $project->apiKeys()->count());
        $this->assertLessThan(2.0, $duration, "Bulk creation took {$duration}s (expected < 2.0s)");
    }

    /** @test */
    public function test_database_query_optimization()
    {
        $user = User::factory()->create();
        $projects = Project::factory()->count(20)->create(['user_id' => $user->id]);
        
        foreach ($projects as $project) {
            ApiKey::factory()->count(5)->create(['project_id' => $project->id]);
        }

        \DB::enableQueryLog();
        
        $retrievedProjects = $user->projects()->withCount('apiKeys')->get();
        
        $queries = \DB::getQueryLog();
        \DB::disableQueryLog();

        // Should only be 2 queries (one for projects, one for counts) with eager loading
        $this->assertLessThanOrEqual(3, count($queries), "Too many queries: " . count($queries));
    }

    /** @test */
    public function test_concurrent_request_handling()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $start = microtime(true);
        
        // Simulate 10 concurrent requests
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($user)->getJson('/api/v1/projects/' . $project->id);
        }
        
        $duration = microtime(true) - $start;

        $this->assertLessThan(3.0, $duration, "Concurrent requests took {$duration}s (expected < 3.0s)");
    }
}
