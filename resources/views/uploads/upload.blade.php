@extends('layouts.app')

@section('content')
<div class="container mx-auto p-8">
    <div class="">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <h2>Select file / Drag to upload </h2>
        <form action="{{ route('file.upload.save') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="mb-3">
                <input type="file" name="file" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Upload File</button>
        </form>
    </div>

    <div class="">

    </div>

    @if($uploads->isEmpty())
        <div class="alert alert-info">No upload records.</div>
    @else
        <!-- <table class="table table-striped">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>File Name</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                @foreach($uploads as $upload)
                <tr>
                    <td>{{ $upload->created_at->format('Y-m-d H:i') ?? '-' }} ({{ $upload->time_difference }} minutes ago)</td>
                    <td><pre style="white-space: pre-wrap;">{{ json_encode($upload->file_name) }}</pre></td>
                    <td>{{ $upload->status }}</td>
                </tr>
                @endforeach
            </tbody>
        </table> -->

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

<script src="//unpkg.com/alpinejs" defer></script>
<script>

</script>




@endsection


