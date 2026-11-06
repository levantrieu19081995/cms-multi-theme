<?php

namespace App\Exports;

use App\Enums\PostStatus;
use App\Enums\PublishType;
use App\Enums\Taxonomy as EnumsTaxonomy;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Carbon;

class PostsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    // protected int $row = 0;

    // public function __construct(protected Collection $posts) {}

    // public function headings(): array
    // {
    //     return ['STT', 'Tiêu đề', 'Tác giả', 'Ngày xuất bản', 'Chuyên mục'];
    // }

    // public function map($post): array
    // {
    //     $cats = $post->taxonomies
    //         ->where('taxonomy', EnumsTaxonomy::CATEGORY->value)
    //         ->map(fn($t) => optional($t->term)->name)
    //         ->filter()
    //         ->implode(', ');
    //     // dd($post->toArray());
    //     $publishedAt = $post->published_at
    //         ? Carbon::parse($post->published_at)->format('d/m/Y H:i')
    //         : '';

    //     return [
    //         ++$this->row,                                // STT
    //         $post->title,                                // Tiêu đề
    //         $post->author_names ?: '',         // Tác giả
    //         $publishedAt,
    //         $cats,                                       // Chuyên mục
    //     ];
    // }

    // public function collection()
    // {
    //     return $this->posts;
    // }

    private Collection $posts;
    private $mapRow; // callable|null
    private int $i = 0;

    public function __construct($posts, $mapRow = null)
    {
        $this->posts = $posts instanceof Collection ? $posts : collect($posts);
        $this->mapRow = $mapRow; // fn($post, $index) => [...]
    }

    public function collection()
    {
        return $this->posts;
    }

    public function headings(): array
    {
        return [
            'TT',
            'TÊN TIN BÀI (TRÍCH YẾU)',
            'LOẠI TIN',
            'CHUYÊN MỤC',
            'TÁC GIẢ',
            'NGƯỜI ĐĂNG',
            'NGÀY ĐĂNG',
            'ĐƯỜNG LINK',
        ];
    }

    public function map($row): array
    {
        $this->i++;
        if (is_callable($this->mapRow)) {
            return call_user_func($this->mapRow, $row, $this->i);
        }

        // fallback tối thiểu nếu không truyền callback
        return [
            $this->i,
            (string)($row->title ?? ''),
            (string)($row->post_type ?? ''),
            '',
            (string)($row->author_name ?? ''),
            (string)($row->publisher_name ?? ''),
            '',
            (string)($row->slug ?? ''),
        ];
    }
}
