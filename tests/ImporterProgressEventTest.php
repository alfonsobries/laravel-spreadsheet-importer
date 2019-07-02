<?php

namespace Alfonsobries\LaravelSpreadsheetImporter\Tests;

use Alfonsobries\LaravelSpreadsheetImporter\Events\ImporterProgressEvent;
use Alfonsobries\LaravelSpreadsheetImporter\Tests\stubs\Models\MyModel;

class ImporterProgressEventTest extends TestCase
{
    /** @test */
    public function it_update_the_file_values_in_the_event()
    {
        $importable = MyModel::create();
        $importable->importable_table_name = 'temporal_table';

        // Emulated the events in a 19 csv with 4 items chunk
        $events = collect([
            [
                'type' => 'readed',
            ],
            [
                'type' => 'total_rows',
                'data' => '19',
            ],
            [
                'type' => 'connected'
            ],
            [
                'type' => 'table_created',
                'data' => $importable->importable_table_name,
            ],
            [
                'type' => 'processing',
                'data' => 4,
            ],
            [
                'type' => 'processing',
                'data' => 8,
            ],
            [
                'type' => 'processing',
                'data' => 12,
            ],
            [
                'type' => 'processing',
                'data' => 16,
            ],
            [
                'type' => 'processing',
                'data' => 19,
            ],
            [
                'type' => 'finished'
            ],
        ]);


        $events->each(function ($event) use ($importable) {
            event(new ImporterProgressEvent(
                get_class($importable),
                $importable->id,
                $event['type'],
                !empty($event['data']) ? $event['data'] : null,
                !empty($event['message']) ? $event['data'] : null,
                1
            ));
        });

        $importable->refresh();

        $this->assertEquals(19, $importable->importable_total_rows);
        $this->assertEquals(19, $importable->importable_processed);
        $this->assertEquals('finished', $importable->importable_status);
    }
}
