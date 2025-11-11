<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateFileUploadStatus implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $fileUpload;
    
    /**
     * Create a new event instance.
     */
    public function __construct($fileUpload = null)
    {
        // $this->fileUpload = $fileUpload->fresh();
        $this->fileUpload = $fileUpload;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // return new PrivateChannel('file-uploads.' . $this->fileUpload->user_id);
        return [new PrivateChannel('file-uploads.' . $this->fileUpload->user_id)];
    }

    public function broadcastAs()
    {
        return 'UpdateFileUploadStatus';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->fileUpload->id,
            'file_name' => $this->fileUpload->file_name,
            'status' => $this->fileUpload->status,
            'processed_rows' => $this->fileUpload->processed_rows ?? 0,
            'time_difference' => $this->fileUpload->created_at->diffForHumans(),
        ];
    }
}
