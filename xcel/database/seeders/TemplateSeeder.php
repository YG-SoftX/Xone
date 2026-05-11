<?php
namespace Database\Seeders;
use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder {
    public function run(): void {
        $templates = [
            ['name' => 'Blank Spreadsheet', 'description' => 'Start from scratch', 'category' => 'finance', 'is_public' => true, 'is_system' => true, 'sheets_data' => '[{"name":"Sheet1","row_count":100,"column_count":26}]'],
            ['name' => 'Budget Tracker', 'description' => 'Monthly budget with income and expenses', 'category' => 'budget', 'is_public' => true, 'is_system' => true, 'sheets_data' => '[{"name":"Budget","cells":[{"address":"A1","value":"Category"},{"address":"B1","value":"Budgeted"},{"address":"C1","value":"Actual"},{"address":"D1","value":"Difference"},{"address":"A2","value":"Income"},{"address":"B2","value":"5000"},{"address":"A3","value":"Expenses"},{"address":"B3","value":"3000"},{"address":"A4","value":"Savings"},{"address":"B4","value":"=B2-B3"}]}]'],
            ['name' => 'Invoice Template', 'description' => 'Professional invoice with calculations', 'category' => 'invoice', 'is_public' => true, 'is_system' => true, 'sheets_data' => '[{"name":"Invoice","cells":[{"address":"A1","value":"INVOICE"},{"address":"A3","value":"Date"},{"address":"B3","value":""},{"address":"A5","value":"Item"},{"address":"B5","value":"Qty"},{"address":"C5","value":"Price"},{"address":"D5","value":"Total"},{"address":"A6","value":"Service 1"},{"address":"B6","value":"1"},{"address":"C6","value":"100"},{"address":"D6","value":"=B6*C6"},{"address":"D8","value":"Total"},{"address":"E8","value":"=SUM(D6:D7)"}]}]'],
            ['name' => 'Grade Book', 'description' => 'Student grades with averages', 'category' => 'education', 'is_public' => true, 'is_system' => true, 'sheets_data' => '[{"name":"Grades","cells":[{"address":"A1","value":"Student"},{"address":"B1","value":"Test 1"},{"address":"C1","value":"Test 2"},{"address":"D1","value":"Test 3"},{"address":"E1","value":"Average"},{"address":"A2","value":"Student 1"},{"address":"B2","value":"85"},{"address":"C2","value":"90"},{"address":"D2","value":"88"},{"address":"E2","value":"=AVERAGE(B2:D2)"}]}]'],
            ['name' => 'Inventory Tracker', 'description' => 'Product inventory with stock levels', 'category' => 'inventory', 'is_public' => true, 'is_system' => true, 'sheets_data' => '[{"name":"Inventory","cells":[{"address":"A1","value":"Product"},{"address":"B1","value":"In Stock"},{"address":"C1","value":"Reorder Level"},{"address":"D1","value":"Status"},{"address":"A2","value":"Product A"},{"address":"B2","value":"150"},{"address":"C2","value":"50"},{"address":"D2","value":"=IF(B2>C2,\"OK\",\"Reorder\")"}]}]'],
            ['name' => 'Weekly Schedule', 'description' => 'Weekly planner template', 'category' => 'schedule', 'is_public' => true, 'is_system' => true, 'sheets_data' => '[{"name":"Schedule","cells":[{"address":"A1","value":"Time"},{"address":"B1","value":"Monday"},{"address":"C1","value":"Tuesday"},{"address":"D1","value":"Wednesday"},{"address":"E1","value":"Thursday"},{"address":"F1","value":"Friday"},{"address":"A2","value":"9:00"},{"address":"A3","value":"10:00"},{"address":"A4","value":"11:00"},{"address":"A5","value":"12:00"}]}]'],
        ];
        foreach ($templates as $t) Template::create($t);
    }
}
