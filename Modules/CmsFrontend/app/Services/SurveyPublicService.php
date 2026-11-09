<?php

namespace Modules\CmsFrontend\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\CmsBackend\DataProviders\SurveyDataProvider;
use Modules\CmsBackend\DataProviders\SurveyResponseDataProvider;
use Modules\CmsBackend\Enums\SurveyQuestionType;
use Modules\CmsBackend\Enums\SurveyStatus;
use Modules\CmsBackend\Exceptions\SurveyRuleException;
use Modules\CmsBackend\Services\SurveyFormService;
use Modules\CmsBackend\Services\CareerService;
use Modules\CmsBackend\Services\SurveyService;
use Modules\CmsBackend\Support\Riasec;
use Modules\CmsBackend\Support\SurveyHtmlSanitizer;
use Modules\CmsFrontend\Models\KhaoSat;
use Modules\CmsFrontend\Models\KhaoSatUser;

/**
 * Nghiệp vụ làm khảo sát ở trang công khai. Dùng chung cho mọi theme; theme chỉ khác phần trình bày.
 * Mọi quyết định (trạng thái/thời gian, giới hạn gửi, chống gửi lặp, kiểm tra đáp án) nằm ở đây, không ở view hay JS.
 */
class SurveyPublicService
{
    public const COOKIE = 'ks_client';

    public const RESUME_HOURS = 24;

    public const OPEN = 'open';

    public const PAUSED = 'paused';

    public const NOT_STARTED = 'not_started';

    public const ENDED = 'ended';

    public function __construct(
        private SurveyDataProvider $surveys,
        private SurveyResponseDataProvider $responses,
        private SurveyFormService $forms,
        private SurveyHtmlSanitizer $sanitizer,
        private CareerService $career,
    ) {}

    // ------------------------------------------------------------------ truy cập

    /** Khảo sát công khai theo mã. Nháp và khảo sát đã xóa coi như không tồn tại (không lộ bản chưa phát hành). */
    public function find(string $slug): ?KhaoSat
    {
        if (preg_match('/^[A-Za-z0-9\-_]{1,120}$/D', $slug) !== 1) {
            return null;
        }
        $survey = $this->surveys->findBySlug($slug);
        if ($survey === null || $survey->status() === SurveyStatus::Draft) {
            return null;
        }

        // Khảo sát nghề nghiệp ngừng hoạt động khi chức năng "career" bị tắt (dữ liệu giữ nguyên).
        return $survey->ks_loai === SurveyService::CAREER && ! $this->career->enabled() ? null : $survey;
    }

    /** Khảo sát đang mở cho trang danh sách công khai (xem SurveyDataProvider::openForPublic). */
    public function openList(): \Illuminate\Support\Collection
    {
        $surveys = $this->surveys->openForPublic(now());

        return $this->career->enabled() ? $surveys : $surveys->reject(fn (KhaoSat $s) => $s->ks_loai === SurveyService::CAREER)->values();
    }

    /** @return string self::OPEN|PAUSED|NOT_STARTED|ENDED */
    public function availability(KhaoSat $survey): string
    {
        $now = now();

        return match (true) {
            $survey->status() === SurveyStatus::Ended, $survey->ks_ket_thuc !== null && $survey->ks_ket_thuc < $now => self::ENDED,
            $survey->status() === SurveyStatus::Paused => self::PAUSED,
            $survey->ks_bat_dau !== null && $survey->ks_bat_dau > $now => self::NOT_STARTED,
            default => self::OPEN,
        };
    }

    /**
     * HTML giới thiệu an toàn để in ra. Lọc lại ở thời điểm hiển thị (kể cả dữ liệu lưu trước khi có bộ lọc allow-list);
     * kết quả được cache theo nội dung.
     */
    public function introHtml(KhaoSat $survey): ?string
    {
        $raw = (string) $survey->ks_gioi_thieu;
        if (trim($raw) === '') {
            return null;
        }

        return \Illuminate\Support\Facades\Cache::remember('survey.intro.'.md5($raw), 3600, fn () => $this->sanitizer->clean($raw));
    }

    /** @return array{sections: array<int, array<string, mixed>>, question_count: int} */
    public function form(KhaoSat $survey): array
    {
        return $this->forms->schema($survey);
    }

    // ------------------------------------------------------------------ ngữ cảnh trình duyệt

    /**
     * Dấu vết ẩn danh của trình duyệt: HMAC của cookie ngẫu nhiên và của IP bằng APP_KEY. Không lưu IP/cookie gốc.
     *
     * @return array{token: string, fingerprint: string, ip_hash: string, user_agent: ?string}
     */
    public function clientContext(Request $request): array
    {
        $token = (string) $request->cookie(self::COOKIE);
        if (preg_match('/^[A-Za-z0-9]{40}$/D', $token) !== 1) {
            $token = Str::random(40);
        }
        $key = (string) config('app.key');

        return [
            'token' => $token,
            'fingerprint' => hash_hmac('sha256', $token, $key),
            'ip_hash' => hash_hmac('sha256', (string) $request->ip(), $key),
            'user_agent' => $request->userAgent() ? Str::limit($request->userAgent(), 250, '') : null,
        ];
    }

    // ------------------------------------------------------------------ bắt đầu

    /**
     * @param  array{ho_ten?: ?string, so_dien_thoai?: ?string, email?: ?string, an_danh?: mixed}  $identity  đã qua SurveyStartRequest
     * @param  array{fingerprint: string, ip_hash: string, user_agent: ?string}  $ctx
     */
    public function start(KhaoSat $survey, array $identity, array $ctx): KhaoSatUser
    {
        $collect = (bool) $survey->ks_yeu_cau_thong_tin;
        $anonymous = ! $collect || ($survey->ks_cho_phep_an_danh && ! empty($identity['an_danh']));
        $attributes = [
            'ksu_an_danh' => $anonymous,
            // cột cũ NOT NULL: chuỗi rỗng = không khai
            'ksu_ho_ten' => $anonymous ? '' : (string) ($identity['ho_ten'] ?? ''),
            'ksu_so_dien_thoai' => $anonymous ? '' : (string) ($identity['so_dien_thoai'] ?? ''),
            'ksu_email' => $anonymous ? null : ($identity['email'] ?? null),
        ];
        if ($survey->ks_loai === SurveyService::CAREER) {
            $school = $this->career->school($identity['truong'] ?? null);
            $attributes['school_key'] = $school['key'] ?? null;
            $attributes['school_name'] = $school['name'] ?? null;
        }

        return $this->responses->transaction(function () use ($survey, $ctx, $attributes) {
            // Khóa dòng khảo sát: mọi bắt đầu/gửi của CÙNG khảo sát được tuần tự hóa, nên kiểm tra giới hạn và ghi là một thao tác
            // nguyên tử (không hai request song song nào cùng thấy "chưa chạm giới hạn"). Đọc lại khảo sát sau khi giữ khóa.
            $survey = $this->responses->lockSurvey($survey->ks_id) ?? $survey;
            $this->assertOpen($survey);
            $this->assertWithinLimit($survey, $ctx['fingerprint']);
            $existing = $this->responses->findResumable($survey->ks_id, $ctx['fingerprint'], now()->subHours(self::RESUME_HOURS));
            if ($existing !== null) {
                return $this->responses->updateParticipation($existing, $attributes);
            }

            return $this->responses->createParticipation($attributes + [
                'ks_id' => $survey->ks_id,
                'ksu_ma' => (string) Str::uuid(),
                'ksu_trang_thai' => KhaoSatUser::STATUS_IN_PROGRESS,
                'ksu_dau_vet' => $ctx['fingerprint'],
                'ksu_ip_hash' => $ctx['ip_hash'],
                'ksu_user_agent' => $ctx['user_agent'],
            ]);
        });
    }

    /** Lượt đang làm dở thuộc đúng trình duyệt này (mã từ phiên, đối chiếu dấu vết). */
    public function activeParticipation(KhaoSat $survey, ?string $code, array $ctx): ?KhaoSatUser
    {
        if ($code === null || $code === '') {
            return null;
        }
        $participation = $this->responses->findParticipationByCode($survey->ks_id, $code);

        return $participation !== null
            && ! $participation->isCompleted()
            && hash_equals((string) $participation->ksu_dau_vet, $ctx['fingerprint'])
            ? $participation
            : null;
    }

    // ------------------------------------------------------------------ kiểm tra dữ liệu gửi

    /**
     * Quy tắc validation cho `answers.*`, dựng từ chính cấu trúc khảo sát (mã UUID làm khóa).
     *
     * @return array{rules: array<string, mixed>, attributes: array<string, string>}
     */
    public function answerRules(KhaoSat $survey): array
    {
        $rules = ['answers' => ['nullable', 'array']];
        $attributes = [];
        foreach ($this->form($survey)['sections'] as $section) {
            foreach ($section['questions'] as $q) {
                $field = 'answers.'.$q['key'];
                $attributes[$field] = Str::limit($q['text'], 80);
                $required = $q['required'] ? 'required' : 'nullable';
                $type = SurveyQuestionType::from($q['type']);
                $optionKeys = array_column($q['options'], 'key');
                switch ($type) {
                    case SurveyQuestionType::Single:
                        $rules[$field] = [$required, 'string', Rule::in($optionKeys)];
                        break;
                    case SurveyQuestionType::Multiple:
                        $rules[$field] = $q['required'] ? ['required', 'array', 'min:1'] : ['nullable', 'array'];
                        $rules[$field.'.*'] = ['string', Rule::in($optionKeys)];
                        $attributes[$field.'.*'] = $attributes[$field];
                        break;
                    case SurveyQuestionType::YesNo:
                        $rules[$field] = [$required, Rule::in(['yes', 'no'])];
                        break;
                    case SurveyQuestionType::Scale:
                        $rules[$field] = [$required, 'integer', 'between:'.(int) ($q['config']['min'] ?? 1).','.(int) ($q['config']['max'] ?? 5)];
                        break;
                    case SurveyQuestionType::Text:
                        $rules[$field] = [$required, 'string', 'max:255'];
                        break;
                    case SurveyQuestionType::Textarea:
                        $rules[$field] = [$required, 'string', 'max:5000'];
                        break;
                }
            }
        }

        return ['rules' => $rules, 'attributes' => $attributes];
    }

    // ------------------------------------------------------------------ gửi

    /**
     * Lưu câu trả lời và hoàn thành lượt. Idempotent: gửi lần hai (nhấp đúp, F5, gửi lại) không tạo thêm dữ liệu.
     *
     * @param  array<string, mixed>  $answers  khóa = mã câu hỏi (UUID)
     * @return array{participation: KhaoSatUser, already: bool}
     */
    public function submit(KhaoSat $survey, string $code, array $answers, array $ctx): array
    {
        return $this->responses->transaction(function () use ($survey, $code, $answers, $ctx) {
            // Thứ tự khóa cố định: khảo sát → lượt (tránh deadlock). Xem ghi chú ở start().
            $survey = $this->responses->lockSurvey($survey->ks_id) ?? $survey;
            $participation = $this->responses->findParticipationByCode($survey->ks_id, $code, lock: true);
            if ($participation === null || ! hash_equals((string) $participation->ksu_dau_vet, $ctx['fingerprint'])) {
                throw new SurveyRuleException(__('survey.error.session_lost'));
            }
            if ($participation->isCompleted()) {
                return ['participation' => $participation, 'already' => true];
            }
            $this->assertOpen($survey);
            $this->assertWithinLimit($survey, $ctx['fingerprint']);

            $rows = $this->buildAnswerRows($survey, $answers);
            $this->responses->insertAnswers($participation, $rows);
            $now = now();
            $this->responses->updateParticipation($participation, [
                'ksu_trang_thai' => KhaoSatUser::STATUS_COMPLETED,
                'ksu_hoan_thanh_at' => $now,
                'ksu_thoi_luong' => max(0, (int) $participation->ksu_created_at?->diffInSeconds($now, true)),
            ] + $this->careerAttributes($survey, $answers));

            return ['participation' => $participation, 'already' => false];
        });
    }

    /**
     * Chuyển câu trả lời thành các dòng ks_ket_qua. Chỉ chấp nhận mã thuộc khảo sát này; câu bắt buộc thiếu → lỗi
     * (kiểm tra lần hai sau FormRequest).
     *
     * @return array<int, array<string, mixed>>
     */
    /**
     * Khảo sát định hướng nghề nghiệp: cộng điểm theo 6 nhóm RIASEC và lấy 3 nhóm cao nhất làm mã Holland (ghi vào ksu_ket_qua).
     * Khảo sát khác không có thuộc tính thêm. Gọi sau buildAnswerRows() nên cấu trúc đã được nạp.
     *
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>
     */
    private function careerAttributes(KhaoSat $survey, array $answers): array
    {
        if ($survey->ks_loai !== SurveyService::CAREER) {
            return [];
        }
        $questions = $survey->nhom->flatMap->cauHoi->concat($survey->cauHoi)->filter(fn ($q) => $q->ch_trang_thai && $q->ch_ma !== null);
        $scores = Riasec::score($questions, $answers);

        return [
            'ksu_ket_qua' => Riasec::holland($scores),
            'ksu_diem' => json_encode(array_map(fn (array $row) => ['pct' => $row['pct'], 'answered' => $row['answered'], 'questions' => $row['questions']], $scores)),
        ];
    }

    /** Danh sách trường để chọn ở trang giới thiệu (chỉ khảo sát định hướng nghề nghiệp). @return array<int, array{key: string, name: string}> */
    public function schools(KhaoSat $survey): array
    {
        return $survey->ks_loai === SurveyService::CAREER ? $this->career->schools() : [];
    }

    /** Kết quả định hướng nghề nghiệp của lượt vừa hoàn thành (theo mã lượt trong phiên); null nếu không phải khảo sát nghề nghiệp. */
    public function careerResult(KhaoSat $survey, string $code): ?array
    {
        if ($survey->ks_loai !== SurveyService::CAREER || $code === '') {
            return null;
        }
        $participation = $this->responses->findParticipationByCode($survey->ks_id, $code);

        return $participation !== null && $participation->isCompleted() ? $this->career->resultFor($participation) : null;
    }

    private function buildAnswerRows(KhaoSat $survey, array $answers): array
    {
        $this->surveys->withStructure($survey);
        $questions = $survey->nhom->flatMap->cauHoi->concat($survey->cauHoi)->filter(fn ($q) => $q->ch_trang_thai && $q->ch_ma !== null)->keyBy('ch_ma');
        $rows = [];
        foreach ($questions as $key => $q) {
            $type = $q->kieu();
            $raw = $answers[$key] ?? null;
            $empty = $raw === null || $raw === '' || $raw === [];
            if ($empty) {
                if ($q->ch_bat_buoc && ($type->hasOptions() ? $q->traLoi->where('trl_trang_thai', true)->isNotEmpty() : true)) {
                    throw new SurveyRuleException(__('survey.error.required_missing', ['question' => Str::limit($q->ch_noidung, 80)]));
                }

                continue;
            }
            if ($type->hasOptions()) {
                $options = $q->traLoi->where('trl_trang_thai', true)->keyBy('trl_ma');
                foreach (array_values(array_unique((array) $raw)) as $optionKey) {
                    $option = $options->get((string) $optionKey);
                    if ($option === null) {
                        throw new SurveyRuleException(__('survey.error.invalid_answer'));
                    }
                    $rows[] = ['ch_id' => $q->ch_id, 'trl_id' => $option->trl_id, 'kskq_trl_value' => $option->trl_value];
                    if ($type === SurveyQuestionType::Single) {
                        break;
                    }
                }
            } elseif ($type->isFreeText()) {
                $text = trim((string) $raw);
                $rows[] = ['ch_id' => $q->ch_id, 'kskq_trl_value' => Str::limit($text, 250, ''), 'kskq_van_ban' => $text];
            } else { // scale, yesno
                $rows[] = ['ch_id' => $q->ch_id, 'kskq_trl_value' => (string) $raw];
            }
        }

        return $rows;
    }

    // ------------------------------------------------------------------ kiểm tra chung

    private function assertOpen(KhaoSat $survey): void
    {
        if ($this->availability($survey) !== self::OPEN) {
            throw new SurveyRuleException(__('survey.error.closed'));
        }
    }

    private function assertWithinLimit(KhaoSat $survey, string $fingerprint): void
    {
        $limit = (int) $survey->ks_so_lan_gui;
        if ($limit > 0 && $this->responses->completedCountByFingerprint($survey->ks_id, $fingerprint) >= $limit) {
            throw new SurveyRuleException(__('survey.error.limit_reached'));
        }
    }
}
