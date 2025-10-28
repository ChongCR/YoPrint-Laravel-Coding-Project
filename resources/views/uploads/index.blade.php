<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CSV Uploads - YoPrint</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .upload-area {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
        }

        .upload-area.dragover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }

        .status-pending {
            color: #ffc107;
        }

        .status-processing {
            color: #0d6efd;
        }

        .status-completed {
            color: #198754;
        }

        .status-failed {
            color: #dc3545;
        }
    </style>
</head>

<body class="bg-light">
    <div class="container py-5">
        <h1 class="h3 mb-4 fw-bold">CSV Uploads</h1>

        <div class="mb-5">
            <div class="upload-area" id="dropZone">
                <p class="mb-3" id="uploadText">Select file / Drag and drop</p>
                <input type="file" accept=".csv" class="d-none" id="fileInput">
                <button type="button" onclick="fileInput.click()" class="btn btn-outline-primary">Choose File</button>
            </div>
            <small class="text-muted d-block mt-2">Only .csv files are allowed</small>
        </div>

        <div class="card shadow-sm">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>
                            <a href="?sort=created_at&direction={{ $sort === 'created_at' && $direction === 'desc' ? 'asc' : 'desc' }}"
                                class="text-dark text-decoration-none">
                                Time
                                @if($sort === 'created_at')
                                    <i class="bi bi-arrow-{{ $direction === 'desc' ? 'down' : 'up' }}"></i>
                                @else
                                    <i class="bi bi-arrow-down-up text-muted opacity-50"></i>
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="?sort=filename&direction={{ $sort === 'filename' && $direction === 'desc' ? 'asc' : 'desc' }}"
                                class="text-dark text-decoration-none">
                                File Name
                                @if($sort === 'filename')
                                    <i class="bi bi-arrow-{{ $direction === 'desc' ? 'down' : 'up' }}"></i>
                                @else
                                    <i class="bi bi-arrow-down-up text-muted opacity-50"></i>
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="?sort=status&direction={{ $sort === 'status' && $direction === 'desc' ? 'asc' : 'desc' }}"
                                class="text-dark text-decoration-none">
                                Status
                                @if($sort === 'status')
                                    <i class="bi bi-arrow-{{ $direction === 'desc' ? 'down' : 'up' }}"></i>
                                @else
                                    <i class="bi bi-arrow-down-up text-muted opacity-50"></i>
                                @endif
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody id="uploadsTable">
                    @forelse($uploads as $upload)
                        <tr>
                            <td class="text-muted small">
                                {{ $upload->created_at->format('g:i A') }}<br><em>{{ $upload->created_at->diffForHumans() }}</em>
                            </td>
                            <td>{{ $upload->filename }}</td>
                            <td>
                                <span
                                    class="status-{{ $upload->status }} fw-bold text-capitalize">{{ $upload->status }}</span>
                                @if($upload->status === 'processing' && $upload->total_rows)
                                    <div class="progress mt-1" style="height: 4px;">
                                        <div class="progress-bar"
                                            style="width: {{ ($upload->processed_rows / $upload->total_rows) * 100 }}%"></div>
                                    </div>
                                    <small class="text-muted">{{ $upload->processed_rows }} / {{ $upload->total_rows }}</small>
                                @endif
                                @if($upload->error)<br><small class="text-danger">{{ $upload->error }}</small>@endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">No uploads yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const uploadText = document.getElementById('uploadText');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(e => {
            dropZone.addEventListener(e, evt => { evt.preventDefault(); evt.stopPropagation(); });
        });

        ['dragenter', 'dragover'].forEach(e => {
            dropZone.addEventListener(e, () => dropZone.classList.add('dragover'));
        });

        ['dragleave', 'drop'].forEach(e => {
            dropZone.addEventListener(e, () => dropZone.classList.remove('dragover'));
        });

        dropZone.addEventListener('drop', e => {
            if (e.dataTransfer.files.length) uploadFile(e.dataTransfer.files[0]);
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) uploadFile(fileInput.files[0]);
        });

        async function uploadFile(file) {
            if (!file.name.endsWith('.csv')) {
                alert('Only CSV files allowed');
                return;
            }

            uploadText.textContent = `Uploading ${file.name}...`;
            const formData = new FormData();
            formData.append('file', file);

            try {
                const res = await fetch('{{ route("uploads.store") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await res.json();
                uploadText.textContent = res.ok ? `✓ ${file.name} uploaded!` : '✗ Upload failed';
                if (!res.ok) alert(data.error || 'Upload failed');

                setTimeout(() => {
                    uploadText.textContent = 'Select file / Drag and drop';
                    fileInput.value = '';
                }, 2000);
            } catch (err) {
                uploadText.textContent = '✗ Error';
                alert(err.message);
            }
        }

        setInterval(() => {
            fetch('{{ route("uploads.history") }}')
                .then(r => r.json())
                .then(data => {
                    const tbody = document.getElementById('uploadsTable');
                    tbody.innerHTML = data.length ? data.map(u => `
                        <tr>
                            <td class="text-muted small">${new Date(u.created_at).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}<br><em>${u.human_time}</em></td>
                            <td>${u.filename}</td>
                            <td>
                                <span class="status-${u.status} fw-bold text-capitalize">${u.status}</span>
                                ${u.status === 'processing' && u.total_rows ? `
                                    <div class="progress mt-1" style="height: 4px;"><div class="progress-bar" style="width: ${u.progress}%"></div></div>
                                    <small class="text-muted">${u.processed_rows} / ${u.total_rows}</small>
                                ` : ''}
                                ${u.error ? `<br><small class="text-danger">${u.error}</small>` : ''}
                            </td>
                        </tr>
                    `).join('') : '<tr><td colspan="3" class="text-center text-muted py-4">No uploads yet</td></tr>';
                });
        }, 3000);
    </script>
</body>

</html>