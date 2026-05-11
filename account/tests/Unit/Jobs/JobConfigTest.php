<?php

namespace Tests\Unit\Jobs;

use App\Jobs\AggregateUsage;
use App\Jobs\DeliverWebhook;
use App\Jobs\ExportUserData;
use App\Jobs\GenerateInvoices;
use App\Jobs\IndexContentForSearch;
use App\Jobs\ProcessAccountDeletion;
use Tests\TestCase;

class JobConfigTest extends TestCase
{
    public function test_aggregate_usage_has_tries_and_timeout(): void
    {
        $job = new AggregateUsage();
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(300, $job->timeout);
    }

    public function test_deliver_webhook_has_single_try_and_timeout(): void
    {
        // DeliverWebhook manages its own retry chain — framework must not retry
        $job = new DeliverWebhook(
            $this->createMock(\App\Models\Webhook::class),
            [],
            'test.event'
        );
        $this->assertEquals(1, $job->tries);
        $this->assertEquals(60, $job->timeout);
    }

    public function test_export_user_data_has_tries_and_long_timeout(): void
    {
        $mockExport = $this->createMock(\App\Models\DataExport::class);
        $mockExport->method('__get')->willReturn(null);

        $job = new ExportUserData($mockExport);
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(600, $job->timeout);
    }

    public function test_generate_invoices_has_tries_and_long_timeout(): void
    {
        $job = new GenerateInvoices();
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(600, $job->timeout);
    }

    public function test_index_content_for_search_has_tries_and_timeout(): void
    {
        $job = new IndexContentForSearch('mail', 'message', 1, []);
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(60, $job->timeout);
    }

    public function test_process_account_deletion_has_tries_and_timeout(): void
    {
        $mockRequest = $this->createMock(\App\Models\AccountDeletionRequest::class);
        $mockRequest->method('__get')->willReturn(null);

        $job = new ProcessAccountDeletion($mockRequest);
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(300, $job->timeout);
    }
}
