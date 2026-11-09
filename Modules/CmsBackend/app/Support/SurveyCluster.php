<?php

declare(strict_types=1);

namespace Modules\CmsBackend\Support;

use Illuminate\Http\Request;
use Modules\CmsBackend\Enums\PermissionsEnum;
use Modules\CmsFrontend\Models\KhaoSat;

/**
 * Hai cụm chức năng dùng CHUNG bộ máy khảo sát (model, service, trình dựng, trang làm bài, phản hồi, thống kê, xuất):
 *  - 'survey' = Khảo sát            : /cms/khao-sat, /khao-sat, quyền khaosat-*, chức năng `survey`
 *  - 'career' = Định hướng nghề nghiệp: /cms/dinh-huong-nghe-nghiep, /dinh-huong-nghe-nghiep, quyền nghenghiep-*, chức năng `career`
 * Cụm được suy ra từ TÊN ROUTE đang chạy (cms.nghe-nghiep.* hoặc cms.frontend.nghenghiep.* là career), còn một khảo sát thuộc cụm nào
 * thì do loại của nó (ks_loai = career). Mã dùng chung chỉ gọi name()/route()/can() nên không viết cứng tên route hay quyền của cụm nào.
 */
final class SurveyCluster
{
    public const SURVEY = 'survey';

    public const CAREER = 'career';

    /** Tiền tố tên route quản trị của từng cụm. */
    private const ADMIN = [self::SURVEY => 'cms.khao-sat', self::CAREER => 'cms.nghe-nghiep'];

    /** Tiền tố tên route công khai của từng cụm. */
    private const PUBLIC = [self::SURVEY => 'cms.frontend.khaosat.', self::CAREER => 'cms.frontend.nghenghiep.'];

    public static function current(?Request $request = null): string
    {
        $name = (string) (($request ?? request())->route()?->getName() ?? '');

        return str_starts_with($name, 'cms.nghe-nghiep') || str_starts_with($name, self::PUBLIC[self::CAREER]) ? self::CAREER : self::SURVEY;
    }

    public static function isCareer(?Request $request = null): bool
    {
        return self::current($request) === self::CAREER;
    }

    /** Cụm của một khảo sát theo loại. */
    public static function of(KhaoSat $survey): string
    {
        return $survey->ks_loai === 'career' ? self::CAREER : self::SURVEY;
    }

    /**
     * Tên route của cụm: nhận tên theo cụm Khảo sát (cms.khao-sat.edit, cms.frontend.khaosat.show) và trả tên tương ứng của cụm cần dùng.
     */
    public static function name(string $name, ?string $cluster = null): string
    {
        $cluster ??= self::current();
        $name = self::normalize($name);
        if ($cluster === self::SURVEY) {
            return $name;
        }
        if (str_starts_with($name, self::PUBLIC[self::SURVEY])) {
            return self::PUBLIC[$cluster].substr($name, strlen(self::PUBLIC[self::SURVEY]));
        }

        return self::ADMIN[$cluster].substr($name, strlen(self::ADMIN[self::SURVEY]));
    }

    /** Đưa tên route của bất kỳ cụm nào về dạng cụm Khảo sát. */
    private static function normalize(string $name): string
    {
        foreach ([self::ADMIN, self::PUBLIC] as $map) {
            if (str_starts_with($name, $map[self::CAREER])) {
                return $map[self::SURVEY].substr($name, strlen($map[self::CAREER]));
            }
        }

        return $name;
    }

    /** @param array<string, mixed> $parameters */
    public static function route(string $name, mixed $parameters = [], ?string $cluster = null): string
    {
        return route(self::name($name, $cluster), $parameters);
    }

    public static function other(string $cluster): string
    {
        return $cluster === self::CAREER ? self::SURVEY : self::CAREER;
    }

    /** Tiền tố hằng số quyền của cụm: KHAOSAT hoặc NGHENGHIEP. */
    public static function permissionPrefix(?string $cluster = null): string
    {
        return ($cluster ?? self::current()) === self::CAREER ? 'NGHENGHIEP' : 'KHAOSAT';
    }

    /** Quyền của cụm theo hậu tố (VIEW, CREATE, UPDATE, PUBLISH, DELETE, RESPONSE_VIEW, RESPONSE_DELETE, STATISTICS_VIEW, EXPORT, MANAGER). */
    public static function permission(string $suffix, ?string $cluster = null): PermissionsEnum
    {
        return constant(PermissionsEnum::class.'::'.self::permissionPrefix($cluster).'_'.$suffix);
    }

    /** Tên hiển thị của cụm (tiêu đề trang, liên kết quay lại). */
    public static function title(?string $cluster = null): string
    {
        return ($cluster ?? self::current()) === self::CAREER ? (string) __('career.admin.cluster_title') : (string) __('admin.survey.title');
    }

    /** Mã chức năng trong Feature Registry của cụm. */
    public static function feature(?string $cluster = null): string
    {
        return ($cluster ?? self::current()) === self::CAREER ? 'career' : 'survey';
    }
}
