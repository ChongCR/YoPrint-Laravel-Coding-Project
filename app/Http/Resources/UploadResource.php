<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UploadResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'status' => $this->status,
            'total_rows' => $this->total_rows ?? 0,
            'processed_rows' => $this->processed_rows ?? 0,
            'progress' => $this->calculateProgress(),
            'error' => $this->error,
            'created_at' => $this->created_at->toIso8601String(),
            'human_time' => $this->created_at->diffForHumans(),
        ];
    }

    private function calculateProgress()
    {
        if (!$this->total_rows) {
            return 0;
        }

        return round(($this->processed_rows / $this->total_rows) * 100, 2);
    }
}