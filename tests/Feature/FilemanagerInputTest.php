<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/** File manager: thiếu tham số bắt buộc trả 400 (không phải 500 do lỗi PHP trong gói). Chỉ đọc, không ghi dữ liệu. */
final class FilemanagerInputTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function endpoints(): array
    {
        return array_combine(
            ['crop', 'cropimage', 'cropnewimage', 'resize', 'doresize', 'doresizenew', 'delete', 'move', 'domove', 'download', 'rename'],
            array_map(fn (string $e) => [$e], ['crop', 'cropimage', 'cropnewimage', 'resize', 'doresize', 'doresizenew', 'delete', 'move', 'domove', 'download', 'rename']),
        );
    }

    /** @dataProvider endpoints */
    public function test_missing_input_is_a_400_not_a_500(string $endpoint): void
    {
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Quản trị'))->firstOrFail();
        $response = $this->actingAs($admin)->get('/laravel-filemanager/'.$endpoint);
        $response->assertStatus(400);
        $this->assertStringContainsString('Missing required input', $response->getContent());
        $this->actingAs($admin)->getJson('/laravel-filemanager/'.$endpoint)->assertStatus(400)->assertJsonStructure(['error', 'missing']);
    }

    public function test_upload_without_a_file_is_a_400_and_listing_still_works(): void
    {
        $admin = User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Quản trị'))->firstOrFail();
        $this->actingAs($admin)->post('/laravel-filemanager/upload', ['working_dir' => '/1', 'type' => 'Images'])->assertStatus(400);
        $this->get('/laravel-filemanager/jsonitems?working_dir=/1&type=Images')->assertOk();
    }

    public function test_guests_are_still_sent_to_the_login(): void
    {
        $this->get('/laravel-filemanager/delete')->assertRedirect();
    }
}
