<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Console;

use Illuminate\Console\Command;
use Modules\CmsBackend\Services\CareerService;
use Modules\CmsBackend\Services\SurveyService;
use Modules\CmsFrontend\Models\CareerMajor;
use Modules\CmsFrontend\Models\KhaoSat;

/**
 * Tạo bộ khởi đầu cho khảo sát Định hướng nghề nghiệp (RIASEC): MỘT khảo sát Nháp với vài câu hỏi mỗi nhóm và vài ngành gợi ý.
 * Đây chỉ là điểm xuất phát để quản trị chỉnh trong giao diện (câu hỏi ở tab "Câu hỏi", ngành ở màn "Định hướng nghề nghiệp > Nhóm & ngành nghề"); không
 * chạy tự động, không phát hành, và không ghi đè dữ liệu đã có (khảo sát loại career đã tồn tại thì bỏ qua; đã có ngành thì không thêm ngành).
 */
class InstallCareerStarter extends Command
{
    protected $signature = 'survey:career-starter {--dry-run : Chỉ in nội dung sẽ tạo, không ghi database}';

    protected $description = 'Tạo khảo sát Định hướng nghề nghiệp (RIASEC) ở trạng thái Nháp cùng vài câu hỏi và ngành gợi ý để quản trị chỉnh sửa.';

    /** Hai câu hỏi cho mỗi nhóm (thang 1–5 "mức độ thích"). Nhóm => [tên phần, [câu hỏi...]]. */
    private const QUESTIONS = [
        'R' => ['Hoạt động thực hành, kỹ thuật', ['Lắp ráp, sửa chữa máy móc hoặc thiết bị điện.', 'Làm việc ngoài trời với cây trồng, vật nuôi hoặc dụng cụ lao động.']],
        'I' => ['Tìm hiểu, nghiên cứu', ['Tìm hiểu nguyên nhân của một hiện tượng khoa học hay một sự cố kỹ thuật.', 'Phân tích số liệu để tìm ra quy luật.']],
        'A' => ['Sáng tạo, nghệ thuật', ['Vẽ, thiết kế hoặc sáng tác (âm nhạc, văn chương, video).', 'Thể hiện ý tưởng riêng thay vì làm theo khuôn mẫu có sẵn.']],
        'S' => ['Giúp đỡ, hỗ trợ con người', ['Hướng dẫn, giảng giải hoặc chăm sóc người khác.', 'Làm việc nhóm và lắng nghe chia sẻ của mọi người.']],
        'E' => ['Dẫn dắt, kinh doanh', ['Thuyết phục người khác ủng hộ ý tưởng của mình.', 'Dẫn dắt một nhóm hoặc khởi sự một dự án kinh doanh nhỏ.']],
        'C' => ['Tổ chức, nghiệp vụ', ['Sắp xếp hồ sơ, dữ liệu một cách ngăn nắp và chính xác.', 'Làm việc theo quy trình rõ ràng, có hướng dẫn cụ thể.']],
    ];

    /** Ngành khởi đầu: [tên, mã RIASEC, mô tả ngắn]. Quản trị tự thay bằng các ngành thực tế của trường. */
    private const MAJORS = [
        ['Công nghệ thông tin', 'IRC', 'Phát triển phần mềm, mạng máy tính, an toàn thông tin.'],
        ['Kế toán – Kiểm toán', 'CE', 'Ghi chép, phân tích số liệu tài chính và lập báo cáo.'],
        ['Quản trị kinh doanh', 'EC', 'Điều hành doanh nghiệp, marketing, bán hàng.'],
        ['Điều dưỡng', 'SIR', 'Chăm sóc sức khỏe và hỗ trợ người bệnh.'],
        ['Công nghệ kỹ thuật cơ khí – ô tô', 'RIC', 'Thiết kế, vận hành và bảo trì máy móc, phương tiện.'],
        ['Thiết kế đồ họa', 'AE', 'Sáng tạo hình ảnh, nhận diện thương hiệu và truyền thông.'],
        ['Sư phạm – Giáo dục', 'SAE', 'Giảng dạy, hướng dẫn và phát triển người học.'],
    ];

    public function handle(SurveyService $surveys, CareerService $career): int
    {
        $dry = (bool) $this->option('dry-run');

        if (KhaoSat::withTrashed()->where('ks_loai', SurveyService::CAREER)->exists()) {
            $this->warn('Đã có khảo sát loại "Định hướng nghề nghiệp": không tạo thêm khảo sát.');
        } else {
            $this->line(($dry ? '[thử] ' : '').'Tạo khảo sát Nháp "Định hướng nghề nghiệp (RIASEC)" với '.array_sum(array_map(fn (array $g) => count($g[1]), self::QUESTIONS)).' câu hỏi.');
            if (! $dry) {
                $survey = $surveys->create([
                    'ks_ten' => 'Định hướng nghề nghiệp (RIASEC)',
                    'ks_ma' => 'dinh-huong-nghe-nghiep',
                    'ks_mo_ta_ngan' => 'Khám phá nhóm sở thích nghề nghiệp của bạn và các ngành học phù hợp.',
                    'ks_gioi_thieu' => '<p>Hãy cho biết bạn thích hay không thích từng hoạt động dưới đây. Không có câu trả lời đúng hay sai. Kết quả cho biết ba nhóm sở thích nổi bật nhất của bạn (mã Holland) và gợi ý các ngành học.</p>',
                    'ks_loai' => SurveyService::CAREER,
                    'ks_cho_phep_an_danh' => true,
                    'ks_yeu_cau_thong_tin' => false,
                    'ks_so_lan_gui' => 0,
                    'ks_noi_dung_hoan_thanh' => 'Cảm ơn bạn đã hoàn thành khảo sát. Dưới đây là kết quả định hướng nghề nghiệp của bạn.',
                    'ks_ghichu' => 'Bộ câu hỏi khởi đầu: hãy chỉnh sửa/bổ sung câu hỏi trong tab "Câu hỏi" trước khi phát hành.',
                ], null);
                $groups = [];
                foreach (self::QUESTIONS as $letter => [$title, $texts]) {
                    $groups[] = [
                        'id' => null,
                        'name' => $title,
                        'description' => null,
                        'questions' => array_map(fn (string $text) => [
                            'id' => null, 'type' => 'scale', 'text' => 'Bạn thích hoạt động này đến mức nào: '.lcfirst($text),
                            'description' => null, 'required' => true, 'enabled' => true,
                            'config' => ['min' => 1, 'max' => 5, 'min_label' => 'Rất không thích', 'max_label' => 'Rất thích', 'riasec' => $letter],
                            'options' => [],
                        ], $texts),
                    ];
                }
                $surveys->saveStructure($survey, $groups, null);
                $this->info('Đã tạo khảo sát #'.$survey->ks_id.' (Nháp). Vào Định hướng nghề nghiệp để chỉnh câu hỏi rồi phát hành.');
            }
        }

        if (CareerMajor::withTrashed()->exists()) {
            $this->warn('Đã có ngành gợi ý: không thêm ngành khởi đầu.');
        } else {
            $this->line(($dry ? '[thử] ' : '').'Thêm '.count(self::MAJORS).' ngành khởi đầu.');
            if (! $dry) {
                foreach (self::MAJORS as $order => [$name, $code, $description]) {
                    $career->saveMajor(null, ['name' => $name, 'code' => $code, 'description' => $description, 'order' => ($order + 1) * 10, 'active' => true], null);
                }
                $this->info('Đã thêm ngành khởi đầu. Vào Định hướng nghề nghiệp > Nhóm & ngành nghề để thay bằng ngành thực tế của trường.');
            }
        }

        return self::SUCCESS;
    }
}
