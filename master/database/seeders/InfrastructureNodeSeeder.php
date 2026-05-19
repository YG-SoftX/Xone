<?php

namespace Database\Seeders;

use App\Models\InfrastructureNode;
use Illuminate\Database\Seeder;

/**
 * InfrastructureNodeSeeder
 * 
 * Seeds default server and infrastructure configurations.
 */
class InfrastructureNodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $nodes = [
            [
                'name' => 'app1.ygxone.com',
                'type' => 'app_server',
                'ip_address' => '10.0.1.10',
                'region' => 'us-east-1',
                'status' => 'running',
                'cpu_usage' => 15.5,
            ],
            [
                'name' => 'db1.ygxone.com',
                'type' => 'database',
                'ip_address' => '10.0.2.10',
                'region' => 'us-east-1',
                'status' => 'running',
                'cpu_usage' => 45.2,
            ],
            [
                'name' => 'redis1.ygxone.com',
                'type' => 'cache',
                'ip_address' => '10.0.3.10',
                'region' => 'us-east-1',
                'status' => 'running',
                'cpu_usage' => 10.0,
            ],
            [
                'name' => 'queue1.ygxone.com',
                'type' => 'worker',
                'ip_address' => '10.0.4.10',
                'region' => 'us-east-1',
                'status' => 'running',
                'cpu_usage' => 25.4,
            ],
            [
                'name' => 'lb.ygxone.com',
                'type' => 'load_balancer',
                'ip_address' => '10.0.0.10',
                'region' => 'us-east-1',
                'status' => 'running',
                'cpu_usage' => 5.1,
            ],
        ];

        foreach ($nodes as $nodeData) {
            InfrastructureNode::updateOrCreate(
                ['name' => $nodeData['name']],
                $nodeData
            );
        }

        $this->command->info("Infrastructure nodes seeded successfully!");
    }
}
