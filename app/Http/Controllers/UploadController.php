<?php

namespace App\Http\Controllers;

use App\Http\Resources\UploadResource;
use App\Jobs\ProcessCsvUpload;
use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->query('sort', 'created_at');
        $direction = $request->query('direction', 'desc');

        // Validate to prevent SQL injection
        $allowedSorts = ['created_at', 'filename', 'status'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }

        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        $uploads = Upload::orderBy($sort, $direction)->paginate(10);

        return view('uploads.index', compact('uploads', 'sort', 'direction'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'file' => [
                    'required',
                    'file',
                    'mimes:csv,txt',
                    'max:51200',
                ],
            ], [
                'file.required' => 'Please select a CSV file to upload.',
                'file.mimes' => 'Only CSV files are allowed.',
                'file.max' => 'File size must not exceed 50MB.',
            ]);

            $file = $request->file('file');
            $filename = $file->getClientOriginalName();

            $file->storeAs('uploads', $filename, 'public');

            $upload = Upload::create([
                'filename' => $filename,
                'status' => 'pending',
                'total_rows' => 0,
                'processed_rows' => 0,
            ]);

            ProcessCsvUpload::dispatch($upload->id);

            // Return JSON for AJAX requests
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'File uploaded successfully'
                ]);
            }

            return back()->with('success', 'File uploaded successfully! Processing in background...');

        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors()
                ], 422);
            }
            return back()->withErrors($e->errors());

        } catch (\Exception $e) {
            \Log::error('Upload error: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Upload failed: ' . $e->getMessage());
        }
    }

    public function history()
    {
        $uploads = Upload::latest()
            ->limit(50)
            ->get();

        return UploadResource::collection($uploads);
    }


    public function destroy(string $id)
    {
        $upload = Upload::findOrFail($id);

        if (Storage::disk('public')->exists('uploads/' . $upload->filename)) {
            Storage::disk('public')->delete('uploads/' . $upload->filename);
        }

        $upload->delete();

        return back()->with('success', 'Upload deleted successfully.');
    }
}