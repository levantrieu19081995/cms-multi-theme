<?php

namespace Modules\CmsFrontend\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Modules\CmsBackend\Exceptions\SurveyRuleException;
use Modules\CmsBackend\Support\SurveyCluster;
use Modules\CmsFrontend\Http\Requests\SurveyStartRequest;
use Modules\CmsFrontend\Http\Requests\SurveySubmitRequest;
use Modules\CmsFrontend\Models\KhaoSat;
use Modules\CmsFrontend\Services\SurveyPublicService;

/**
 * Trang công khai làm khảo sát — một controller dùng chung cho mọi theme. View đi qua cmsfrontend::screens.* nên mỗi theme
 * có thể ghi đè phần trình bày; thiếu thì dùng giao diện mặc định. Route khai báo trong routes/public.php (bật/tắt do Feature Registry: cms.feature:survey; theme chỉ đổi giao diện).
 */
class KhaoSatPublicController extends Controller
{
    private const VIEW = 'cmsfrontend::screens.pages.khao-sat.';

    public function __construct(private SurveyPublicService $service) {}

    /** Danh sách khảo sát đang mở. Chức năng tắt → 404 do middleware cms.feature:survey. */
    public function index()
    {
        return response()->view(self::VIEW.'danh-sach', ['surveys' => $this->service->openList(SurveyCluster::current())]);
    }

    /** Giới thiệu + bắt đầu. */
    public function show(Request $request, string $slug)
    {
        $survey = $this->service->find($slug);
        if ($survey === null) {
            return $this->unavailable('not_found', null, 404);
        }
        if (($other = $this->wrongCluster($survey, $request)) !== null) {
            return $other;
        }
        $state = $this->service->availability($survey);
        if ($state !== SurveyPublicService::OPEN) {
            return $this->unavailable($state, $survey);
        }
        $ctx = $this->service->clientContext($request);
        $resuming = $this->service->activeParticipation($survey, $request->session()->get($this->sessionKey($survey)), $ctx) !== null;

        return $this->withClientCookie(response()->view(self::VIEW.'gioi-thieu', [
            'survey' => $survey,
            'resuming' => $resuming,
            'introHtml' => $this->service->introHtml($survey),
            'identity' => $this->identityMode($survey),
            'questionCount' => $this->service->form($survey)['question_count'],
            'schools' => $this->service->schools($survey),
        ]), $ctx);
    }

    public function start(SurveyStartRequest $request, string $slug): RedirectResponse
    {
        $survey = $this->service->find($slug);
        if ($survey === null) {
            abort(404);
        }
        if (($other = $this->wrongCluster($survey, $request)) !== null) {
            return $other;
        }
        $ctx = $this->service->clientContext($request);
        try {
            $participation = $this->service->start($survey, $request->safe()->only(['ho_ten', 'so_dien_thoai', 'email', 'an_danh', 'truong']), $ctx);
        } catch (SurveyRuleException $e) {
            return $this->withClientCookie(redirect()->route(SurveyCluster::name('cms.frontend.khaosat.show'), $slug)->withErrors(['survey' => $e->getMessage()]), $ctx);
        }
        $request->session()->put($this->sessionKey($survey), $participation->ksu_ma);

        return $this->withClientCookie(redirect()->route(SurveyCluster::name('cms.frontend.khaosat.form'), $slug), $ctx);
    }

    /** Các phần câu hỏi. Cần lượt đang làm dở của đúng trình duyệt này. */
    public function form(Request $request, string $slug)
    {
        $survey = $this->service->find($slug);
        if ($survey === null) {
            return $this->unavailable('not_found', null, 404);
        }
        if (($other = $this->wrongCluster($survey, $request)) !== null) {
            return $other;
        }
        $state = $this->service->availability($survey);
        if ($state !== SurveyPublicService::OPEN) {
            return $this->unavailable($state, $survey);
        }
        $ctx = $this->service->clientContext($request);
        $participation = $this->service->activeParticipation($survey, $request->session()->get($this->sessionKey($survey)), $ctx);
        if ($participation === null) {
            return redirect()->route(SurveyCluster::name('cms.frontend.khaosat.show'), $slug);
        }

        return $this->withClientCookie(response()->view(self::VIEW.'lam-bai', [
            'survey' => $survey,
            'form' => $this->service->form($survey),
            'preview' => false,
            'participation' => $participation,
            'identity' => $this->identityMode($survey),
        ]), $ctx);
    }

    public function submit(SurveySubmitRequest $request, string $slug): RedirectResponse
    {
        $survey = $this->service->find($slug);
        if ($survey === null) {
            abort(404);
        }
        if (($other = $this->wrongCluster($survey, $request)) !== null) {
            return $other;
        }
        // Ô bẫy cho bot (ẩn với người dùng): có giá trị thì bỏ qua, không lưu gì.
        if ($request->filled('sv_website')) {
            return redirect()->route(SurveyCluster::name('cms.frontend.khaosat.show'), $slug);
        }
        $ctx = $this->service->clientContext($request);
        $code = (string) $request->session()->get($this->sessionKey($survey), '');
        try {
            $result = $this->service->submit($survey, $code, $request->answers(), $ctx);
        } catch (SurveyRuleException $e) {
            return $this->withClientCookie(redirect()->route(SurveyCluster::name('cms.frontend.khaosat.form'), $slug)->withInput()->withErrors(['survey' => $e->getMessage()]), $ctx);
        }
        // Lượt đã hoàn thành: xóa mã làm dở, đánh dấu để trang cảm ơn biết (không lộ ID).
        $request->session()->forget($this->sessionKey($survey));
        $request->session()->put($this->sessionKey($survey, 'done'), $result['participation']->ksu_ma);

        return $this->withClientCookie(redirect()->route(SurveyCluster::name('cms.frontend.khaosat.thanks'), $slug), $ctx);
    }

    public function thanks(Request $request, string $slug)
    {
        $survey = $this->service->find($slug);
        if ($survey === null) {
            return $this->unavailable('not_found', null, 404);
        }
        if (($other = $this->wrongCluster($survey, $request)) !== null) {
            return $other;
        }
        if (! $request->session()->has($this->sessionKey($survey, 'done'))) {
            return redirect()->route(SurveyCluster::name('cms.frontend.khaosat.show'), $slug);
        }

        return response()->view(self::VIEW.'cam-on', [
            'survey' => $survey,
            'result' => $this->service->careerResult($survey, (string) $request->session()->get($this->sessionKey($survey, 'done'))),
        ]);
    }

    // ------------------------------------------------------------------

    /**
     * Khảo sát loại career chỉ có ở /dinh-huong-nghe-nghiep, các loại khác chỉ ở /khao-sat. Mở nhầm cụm bằng GET (liên kết cũ) thì chuyển
     * sang cùng trang của cụm đúng; ghi dữ liệu nhầm cụm trả 404.
     */
    private function wrongCluster(KhaoSat $survey, Request $request): ?RedirectResponse
    {
        $owner = SurveyCluster::of($survey);
        if ($owner === SurveyCluster::current($request)) {
            return null;
        }
        if ($request->isMethod('GET')) {
            return redirect()->route(SurveyCluster::name((string) $request->route()->getName(), $owner), $request->route()->parameters());
        }

        abort(404);
    }

    private function unavailable(string $state, ?KhaoSat $survey, int $status = 200)
    {
        return response()->view(self::VIEW.'khong-kha-dung', ['state' => $state, 'survey' => $survey], $status);
    }

    private function sessionKey(KhaoSat $survey, string $kind = 'active'): string
    {
        return 'survey.'.$kind.'.'.$survey->ks_id;
    }

    /** @return array{collect: bool, anonymous_allowed: bool} */
    private function identityMode(KhaoSat $survey): array
    {
        return ['collect' => (bool) $survey->ks_yeu_cau_thong_tin, 'anonymous_allowed' => (bool) $survey->ks_cho_phep_an_danh];
    }

    private function withClientCookie($response, array $ctx)
    {
        $response->headers->setCookie(Cookie::make(SurveyPublicService::COOKIE, $ctx['token'], 60 * 24 * 365, '/', null, request()->isSecure(), true, false, 'lax'));

        return $response;
    }
}
