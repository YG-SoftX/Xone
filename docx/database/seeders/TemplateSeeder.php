<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Blank Document',
                'description' => 'Start from scratch',
                'content' => '<p></p>',
                'content_json' => null,
                'category' => 'personal',
                'is_public' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Business Letter',
                'description' => 'Professional business letter format',
                'content' => '<p>[Your Name]<br>[Your Address]<br>[City, State ZIP]</p><p><br></p><p>[Date]</p><p><br></p><p>[Recipient Name]<br>[Recipient Title]<br>[Company]<br>[Address]</p><p><br></p><p>Dear [Name],</p><p><br></p><p>[Your letter content]</p><p><br></p><p>Sincerely,<br>[Your Name]</p>',
                'category' => 'business',
                'is_public' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Meeting Notes',
                'description' => 'Template for meeting notes and action items',
                'content' => '<h1>Meeting Notes</h1><p><strong>Date:</strong> [Date]<br><strong>Time:</strong> [Time]<br><strong>Location:</strong> [Location]<br><strong>Attendees:</strong> [Names]</p><h2>Agenda</h2><ol><li>[Topic 1]</li><li>[Topic 2]</li><li>[Topic 3]</li></ol><h2>Discussion Points</h2><p>[Notes]</p><h2>Action Items</h2><table border="1" cellpadding="8" cellspacing="0"><tr><th>Task</th><th>Assigned To</th><th>Deadline</th></tr><tr><td></td><td></td><td></td></tr></table>',
                'category' => 'business',
                'is_public' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Project Proposal',
                'description' => 'Structured project proposal template',
                'content' => '<h1>Project Proposal</h1><h2>Executive Summary</h2><p>[Brief overview of the project]</p><h2>Objectives</h2><ul><li>[Objective 1]</li><li>[Objective 2]</li><li>[Objective 3]</li></ul><h2>Scope</h2><p>[What is included and excluded]</p><h2>Timeline</h2><p>[Key milestones and deadlines]</p><h2>Budget</h2><p>[Estimated costs]</p><h2>Team</h2><p>[Key team members and roles]</p>',
                'category' => 'business',
                'is_public' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Resume',
                'description' => 'Professional resume template',
                'content' => '<h1>[Your Full Name]</h1><p>[Email] | [Phone] | [Location] | [LinkedIn]</p><hr><h2>Professional Summary</h2><p>[2-3 sentences about your experience and goals]</p><h2>Experience</h2><h3>[Job Title] - [Company]</h3><p><em>[Start Date] - [End Date]</em></p><ul><li>[Achievement 1]</li><li>[Achievement 2]</li><li>[Achievement 3]</li></ul><h2>Education</h2><p><strong>[Degree]</strong> - [University], [Year]</p><h2>Skills</h2><ul><li>[Skill 1]</li><li>[Skill 2]</li><li>[Skill 3]</li></ul>',
                'category' => 'resume',
                'is_public' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Essay',
                'description' => 'Academic essay structure',
                'content' => '<h1 style="text-align: center;">[Essay Title]</h1><p style="text-align: center;">[Your Name]<br>[Course]<br>[Date]</p><h2>Introduction</h2><p>[Hook and background information]</p><p><strong>Thesis:</strong> [Your thesis statement]</p><h2>Body Paragraph 1</h2><p>[Topic sentence, evidence, analysis]</p><h2>Body Paragraph 2</h2><p>[Topic sentence, evidence, analysis]</p><h2>Body Paragraph 3</h2><p>[Topic sentence, evidence, analysis]</p><h2>Conclusion</h2><p>[Restate thesis, summarize main points, final thought]</p>',
                'category' => 'education',
                'is_public' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Report',
                'description' => 'Formal report with sections',
                'content' => '<h1 style="text-align: center;">[Report Title]</h1><p style="text-align: center;">Prepared by: [Name]<br>Date: [Date]</p><hr><h2>Table of Contents</h2><ol><li>Executive Summary</li><li>Introduction</li><li>Methodology</li><li>Findings</li><li>Analysis</li><li>Recommendations</li><li>Conclusion</li></ol><h2>1. Executive Summary</h2><p>[Brief summary of the entire report]</p><h2>2. Introduction</h2><p>[Background, purpose, and scope]</p><h2>3. Methodology</h2><p>[How the research was conducted]</p><h2>4. Findings</h2><p>[Present the data and findings]</p><h2>5. Analysis</h2><p>[Interpret the findings]</p><h2>6. Recommendations</h2><p>[Actionable recommendations]</p><h2>7. Conclusion</h2><p>[Final summary]</p>',
                'category' => 'business',
                'is_public' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Invoice',
                'description' => 'Simple invoice template',
                'content' => '<h1>INVOICE</h1><p><strong>Invoice #:</strong> [Number]<br><strong>Date:</strong> [Date]<br><strong>Due Date:</strong> [Due Date]</p><hr><p><strong>From:</strong><br>[Your Name/Company]<br>[Address]<br>[Email]</p><p><strong>Bill To:</strong><br>[Client Name]<br>[Client Address]<br>[Client Email]</p><table border="1" cellpadding="8" cellspacing="0" style="width: 100%;"><tr><th>Description</th><th>Quantity</th><th>Rate</th><th>Amount</th></tr><tr><td>[Service/Product]</td><td>[Qty]</td><td>[$]</td><td>[$]</td></tr></table><p style="text-align: right;"><strong>Total: $[Amount]</strong></p>',
                'category' => 'business',
                'is_public' => true,
                'is_system' => true,
            ],
        ];

        foreach ($templates as $template) {
            Template::create($template);
        }
    }
}
