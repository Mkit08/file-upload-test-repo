@extends('layouts.app')

@section('content')
<div class="container mx-auto p-8">
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div
        x-data="fileUpload()"
        class="border-2 border-dashed border-gray-400 rounded-xl p-8 text-center bg-gray-50 relative"
        @dragover.prevent="dragging = true"
        @dragleave.prevent="dragging = false"
        @drop.prevent="handleDrop($event)"
        :class="dragging ? 'bg-blue-50 border-blue-500' : ''"
    >
        <p class="text-gray-600 mb-4">
            <strong>Select file</strong> or drag and drop here
        </p>
        
        <input
            type="file"
            name="file"
            id="fileInput"
            class="hidden"
            x-ref="fileInput"
            @change="handleFileChange"
        />

        <button
            type="button"
            @click="$refs.fileInput.click()"
            class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
        >
            Select file / Drag to upload
        </button>

        <template x-if="fileName">
            <div class="mt-4 text-sm text-gray-700">
                Selected: <span class="font-semibold" x-text="fileName"></span>
            </div>
        </template>

        <!-- <h2>Select file / Drag to upload </h2>
        <form action="{{ route('file.upload.save') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="mb-3">
                <input type="file" name="file" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Upload File</button>
        </form> -->
    </div>

    @if($uploads->isEmpty())
        <div class="alert alert-info">No upload records.</div>
    @else
        <div class="mt-10 bg-white rounded-xl shadow border border-gray-200">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 text-left text-gray-700 uppercase">
                    <tr>
                        <th class="px-6 py-3">Time</th>
                        <th class="px-6 py-3">File Name</th>
                        <th class="px-6 py-3">Status</th>
                    </tr>
                </thead>
                <tbody id="upload-list" class="divide-y divide-gray-200">
                    @foreach ($uploads as $upload)
                    <tr>
                        <td class="px-6 py-4">
                            {{ $upload->created_at->format('Y-m-d g:i a') }}<br>
                            <span class="text-gray-500 text-xs">
                                ({{ $upload->time_difference }})
                            </span>
                        </td>
                        <td class="px-6 py-4">{{ $upload->file_name }}</td>
                        <td class="px-6 py-4">
                            @if ($upload->status === 'completed')
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">Completed</span>
                            @elseif ($upload->status === 'failed')
                                <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs">Failed</span>
                            @elseif ($upload->status === 'processing')
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs">Processing</span>
                            @else
                                <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs">Pending</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@push('scripts')
<script src="//unpkg.com/alpinejs" defer></script>

<script>
let fileName = null;
let dragging = true;

function fileUpload() {
    return {
        fileName: null,
        dragging: false,
        handleFileChange(event) {
            this.fileName = event.target.files[0]?.name || null;
            this.uploadFile(event.target.files[0]);
        },
        handleDrop(event) {
            this.dragging = false;
            const file = event.dataTransfer.files[0];
            this.fileName = file.name;
            this.uploadFile(file);
        },
        async uploadFile(file) {
            if (!file) return;
            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', '{{ csrf_token() }}');

            try {
                await fetch('{{ route('file.upload.save') }}', {
                    method: 'POST',
                    body: formData,
                });
                alert('File uploaded successfully!');
            } catch (error) {
                console.error(error);
                alert('Upload failed.');
            }
        },
    };
}

</script>

<script type="module">

document.addEventListener('DOMContentLoaded', function() {
    const userId = {{ auth()->id() ?? 1 }};
    // if (!userId) return;
    
    if (! window.Echo) {
        if (typeof Echo !== 'undefined') {
            window.Echo = Echo;
        }
    }
    if (! window.Echo) return;

    window.Echo.private(`uploads.${userId}`)
        .listen('.UploadStatusUpdated', (e) => {
            upsertUploadRow(e);
        });

    function upsertUploadRow(upload) {
        const tbody = document.getElementById('upload-list');
        if (!tbody) return;

        let row = tbody.querySelector('tr[data-id="' + upload.id + '"]');

        const badge = getStatusBadgeHtml(upload.status);
        const uploadedAt = upload.uploaded_at || '';

        const rowHtml = `
            <tr data-id="${upload.id}">
                <td class="px-6 py-4">
                    ${uploadedAt}<br>
                    <span class="text-gray-500 text-xs">(${upload.time_difference || ''})</span>
                </td>
                <td class="px-6 py-4">${escapeHtml(upload.file_name)}</td>
                <td class="px-6 py-4">${badge}</td>
            </tr>
        `;

        if (row) {
            row.outerHTML = rowHtml;
        } else {
            tbody.insertAdjacentHTML('afterbegin', rowHtml);
        }
    }

    function getStatusBadgeHtml(status) {
        let classes = 'px-2 py-1 rounded text-xs ';
        if (status === 'completed') {
            return `<span class="${classes} bg-green-100 text-green-800">Completed</span>`;
        } else if (status === 'processing') {
            return `<span class="${classes} bg-yellow-100 text-yellow-800">Processing</span>`;
        } else if (status === 'failed') {
            return `<span class="${classes} bg-red-100 text-red-800">Failed</span>`;
        } else {
            return `<span class="${classes} bg-gray-100 text-gray-800">Pending</span>`;
        }
    }

    function escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return String(unsafe)
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');
    }
});

</script>
@endpush



@endsection


