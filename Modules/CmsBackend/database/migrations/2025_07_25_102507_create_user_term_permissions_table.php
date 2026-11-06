<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTermPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_term_permissions', function (Blueprint $table) {
            $table->id();  // Tạo cột id tự động tăng
            $table->foreignId('user_id')->constrained('users', 'id')->onDelete('cascade');  // Liên kết với bảng users (khóa ngoại user_id)
            $table->foreignId('term_id')->constrained('terms', 'term_id')->onDelete('cascade');  // Liên kết với bảng terms (khóa ngoại term_id)
            $table->boolean('can_create')->default(false);  // Quyền tạo bài viết (true/false)
            $table->timestamps();  // Thêm created_at và updated_at
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_term_permissions');
    }
}