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
                'name' => 'Primary Application Server',
                'type' => 'application',
                'hostname' => 'app1.ygxone.com',
                'ip_address' => '10.0.1.10',
                'region' => 'us-east-1',
                'status' => 'active',
                'cpu_cores' => 8,
                'memory_gb' => 32,
                'disk_gb' => 500,
                'is_primary' => true,
            ],
            [
                'name' => 'Database Server - Primary',
                'type' => 'database',
                'hostname' => 'db1.ygxone.com',
                'ip_address' => '10.0.2.10',
                'region' => 'us-east-1',
                'status' => 'active',
                'cpu_cores' => 16,
                'memory_gb' => 64,
                'disk_gb' => 2000,
                'is_primary' => true,
            ],
            [
                'name' => 'Redis Cache Server',
                'type' => 'cache',
                'hostname' => 'redis1.ygxone.com',
                'ip_address' => '10.0.3.10',
                'region' => 'us-east-1',
                'status' => 'active',
                'cpu_cores' => 4,
                'memory_gb' => 16,
                'disk_gb' => 100,
                'is_primary' => true,
            ],
            [
                'name' => 'Queue Worker Server',
                'type' => 'queue',
                'hostname' => 'queue1.ygxone.com',
                'ip_address' => '10.0.4.10',
                'region' => 'us-east-1',
                'status' => 'active',
                'cpu_cores' => 8,
                'memory_gb' => 32,
                'disk_gb' => 200,
                'is_primary' => false,
            ],
            [
                'name' => 'Load Balancer',
                'type' => 'load_balancer',
                'hostname' => 'lb.ygxone.com',
                'ip_address' => '10.0.0.10',
                'region' => 'us-east-1',
                'status' => 'active',
                'cpu_cores' => 4,
                'memory_gb' => 8,
                'disk_gb' => 50,
                'is_primary' => true,
            ],
        ];

        foreach ($nodes as $nodeData) {
            InfrastructureNode::updateOrCreate(
                ['hostname' => $nodeData['hostname']],
                $nodeData
            );
        }

        $this->command->info("Infrastructure nodes seeded successfully!");
    }
}
