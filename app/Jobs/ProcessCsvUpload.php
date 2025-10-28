<?php

namespace App\Jobs;

use App\Events\UploadStatusUpdated;
use App\Models\Upload;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use League\Csv\CharsetConverter;
use League\Csv\Reader;
use League\Csv\ByteSequence;

class ProcessCsvUpload implements ShouldQueue
{
    use Queueable;

    public $timeout = 3600;
    private $uploadId;
    private const BATCH_SIZE = 500;

    private const EXPECTED_HEADERS = [
        'UNIQUE_KEY',
        'PRODUCT_TITLE',
        'PRODUCT_DESCRIPTION',
        'STYLE#',
        'SANMAR_MAINFRAME_COLOR',
        'SIZE',
        'COLOR_NAME',
        'PIECE_PRICE'
    ];

    public function __construct(int $uploadId)
    {
        $this->uploadId = $uploadId;
    }

    public function handle(): void
    {
        $upload = Upload::find($this->uploadId);

        if (!$upload) {
            return;
        }

        try {
            $this->updateUploadStatus($upload, 'processing');

            $csv = $this->loadCsvFile($upload);

            if (!$this->validateHeaders($csv)) {
                return; 
            }

            $this->processRecords($csv, $upload);
            $this->updateUploadStatus($upload, 'completed');

        } catch (\Exception $e) {
            Log::error('CSV Processing Error', [
                'upload_id' => $this->uploadId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->markAsFailed($upload, $e->getMessage());
        }
    }

    private function loadCsvFile(Upload $upload): Reader
    {
        $filePath = storage_path('app/public/uploads/' . $upload->filename);

        if (!file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);
        $inputBom = $csv->getInputBOM();
        if ($inputBom === ByteSequence::BOM_UTF16_LE || $inputBom === ByteSequence::BOM_UTF16_BE) {
            CharsetConverter::addTo($csv, 'UTF-8', 'UTF-16');
        }
        return $csv;
    }

    private function validateHeaders(Reader $csv): bool
    {
        $actualHeaders = array_map('trim', (array) $csv->getHeader());
        $missing = array_values(array_filter(self::EXPECTED_HEADERS, function ($required) use ($actualHeaders) {
            return !in_array($required, $actualHeaders, true);
        }));

        if (!empty($missing)) {
            Log::warning('CSV missing required headers', ['missing' => $missing, 'actual' => $actualHeaders]);
            $this->markAsFailed(
                Upload::find($this->uploadId),
                'Invalid CSV headers. Missing: ' . implode(', ', $missing)
            );
            return false;
        }

        return true;
    }

    private function processRecords(Reader $csv, Upload $upload): void
    {
        $records = $csv->getRecords();
        $totalRecords = iterator_count($records);

        Log::info('Total records counted', ['count' => $totalRecords]);

        if ($totalRecords === 0) {
            throw new \Exception('CSV file is empty');
        }

        $upload->update(['total_rows' => $totalRecords]);

        $records = $csv->getRecords();
        $batch = [];
        $processedCount = 0;

        foreach ($records as $record) {
            $batch[] = $this->formatRecordForDatabase($record);
            $processedCount++;

            if (count($batch) >= self::BATCH_SIZE) {
                $this->saveBatchToDatabase($batch);
                $batch = [];
                $this->updateProgress($upload, $processedCount);
            }
        }

        if (!empty($batch)) {
            $this->saveBatchToDatabase($batch);
            $this->updateProgress($upload, $processedCount);
        }
    }

    private function formatRecordForDatabase(array $record): array
    {
        return [
            'unique_key' => clean_text($record['UNIQUE_KEY']),
            'product_title' => clean_text($record['PRODUCT_TITLE']),
            'product_description' => clean_text($record['PRODUCT_DESCRIPTION']),
            'style_number' => clean_text($record['STYLE#']),
            'sanmar_mainframe_color' => clean_text($record['SANMAR_MAINFRAME_COLOR']),
            'size' => clean_text($record['SIZE']),
            'color_name' => clean_text($record['COLOR_NAME']),
            'piece_price' => $record['PIECE_PRICE'],
            'updated_at' => now(),
        ];
    }

    private function saveBatchToDatabase(array $batch): void
    {
        DB::table('products')->upsert(
            $batch,
            ['unique_key'],
            [
                'product_title',
                'product_description',
                'style_number',
                'sanmar_mainframe_color',
                'size',
                'color_name',
                'piece_price',
                'updated_at'
            ]
        );
    }

    private function updateProgress(Upload $upload, int $processedRows): void
    {
        $upload->update(['processed_rows' => $processedRows]);
        broadcast(new UploadStatusUpdated($upload));
    }

    private function updateUploadStatus(Upload $upload, string $status): void
    {
        $upload->update(['status' => $status]);
        broadcast(new UploadStatusUpdated($upload));
    }

    private function markAsFailed(Upload $upload, string $errorMessage): void
    {
        $upload->update([
            'status' => 'failed',
            'error' => $errorMessage
        ]);
        broadcast(new UploadStatusUpdated($upload));
    }

    public function failed(\Throwable $exception): void
    {
        $upload = Upload::find($this->uploadId);
        if ($upload) {
            $this->markAsFailed($upload, $exception->getMessage());
        }
    }
}