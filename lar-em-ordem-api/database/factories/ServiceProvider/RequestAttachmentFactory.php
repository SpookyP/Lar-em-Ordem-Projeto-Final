<?php

namespace Database\Factories\ServiceProvider;

use App\Models\File\File;
use App\Models\ServiceProvider\AssistanceRequest;
use App\Models\ServiceProvider\RequestAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequestAttachment>
 */
class RequestAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assistance_request_id' => AssistanceRequest::factory(),
            'file_id'               => File::factory()->state([
                'original_name' => 'foto.jpg',
                'mime_type'     => 'image/jpeg',
                'category'      => 'assistance_requests',
            ]),
        ];
    }
}
