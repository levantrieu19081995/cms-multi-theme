<?php

namespace Modules\CmsBackend\Enums;

/**
 * Permission dùng riêng cho Modules CaoDangNghe
 */
enum PermissionsEnum: string
{
    case DICHVU_MANAGER = 'dichvu-manager'; // parent
    case DICHVU_VIEW = 'dichvu-view';
    case DICHVU_CREATE = 'dichvu-create';
    case DICHVU_UPDATE = 'dichvu-update';
    case DICHVU_DELETE = 'dichvu-delete';

    case SUKIEN_MANAGER = 'sukien-manager'; // parent
    case SUKIEN_VIEW = 'sukien-view';
    case SUKIEN_CREATE = 'sukien-create';
    case SUKIEN_UPDATE = 'sukien-update';
    case SUKIEN_DELETE = 'sukien-delete';

    case GUONG_TIEU_BIEU_MANAGER = 'guong-tieu-bieu-manager'; // GƯƠNG TIÊU BIỂU // quyền cha (parent)
    case GUONG_TIEU_BIEU_VIEW = 'guong-tieu-bieu-view';
    case GUONG_TIEU_BIEU_CREATE = 'guong-tieu-bieu-create';
    case GUONG_TIEU_BIEU_UPDATE = 'guong-tieu-bieu-update';
    case GUONG_TIEU_BIEU_DELETE = 'guong-tieu-bieu-delete';

    case DOITAC_MANAGER = 'doitac-manager'; // parent
    case DOITAC_VIEW = 'doitac-view';
    case DOITAC_CREATE = 'doitac-create';
    case DOITAC_UPDATE = 'doitac-update';
    case DOITAC_DELETE = 'doitac-delete';

    case VANBAN_MANAGER = 'vanban-manager'; // parent
    case VANBAN_VIEW = 'vanban-view';
    case VANBAN_CREATE = 'vanban-create';
    case VANBAN_UPDATE = 'vanban-update';
    case VANBAN_DELETE = 'vanban-delete';

    case LOAIVANBAN_MANAGER = 'loaivanban-manager'; // parent
    case LOAIVANBAN_VIEW = 'loaivanban-view';
    case LOAIVANBAN_CREATE = 'loaivanban-create';
    case LOAIVANBAN_UPDATE = 'loaivanban-update';
    case LOAIVANBAN_DELETE = 'loaivanban-delete';

    case NHOMVANBAN_MANAGER = 'nhomvanban-manager'; // parent
    case NHOMVANBAN_VIEW = 'nhomvanban-view';
    case NHOMVANBAN_CREATE = 'nhomvanban-create';
    case NHOMVANBAN_UPDATE = 'nhomvanban-update';
    case NHOMVANBAN_DELETE = 'nhomvanban-delete';

    case MARQUEE_MANAGER = 'marquee-manager'; // parent
    case MARQUEE_VIEW = 'marquee-view';
    case MARQUEE_CREATE = 'marquee-create';
    case MARQUEE_UPDATE = 'marquee-update';
    case MARQUEE_DELETE = 'marquee-delete';
    case MARQUEE_APPROVE = 'marquee-approve';

    case HOIDAP_MANAGER = 'hoidap-manager'; // parent
    case HOIDAP_VIEW = 'hoidap-view';
    case HOIDAP_UPDATE = 'hoidap-update';
    case HOIDAP_DELETE = 'hoidap-delete';
    case HOIDAP_APPROVE = 'hoidap-approve';

    case LIENHE_MANAGER = 'lienhe-manager'; // parent
    case LIENHE_VIEW = 'lienhe-view';
    case LIENHE_DELETE = 'lienhe-delete';

    case SONOIBAT_MANAGER = 'sonoibat-manager'; // parent
    case SONOIBAT_VIEW = 'sonoibat-view';
    case SONOIBAT_UPDATE = 'sonoibat-update';

    case BAIVIET_MANAGER = 'baiviet-manager'; // parent
    case BAIVIET_VIEW = 'baiviet-view';
    case BAIVIET_CREATE = 'baiviet-create';
    case BAIVIET_UPDATE = 'baiviet-update';
    case BAIVIET_DELETE = 'baiviet-delete';
    case BAIVIET_PUBLISHING = 'baiviet-publishing';
    case BAIVIET_APPROVE = 'baiviet-approve';

    case KHAOSAT_MANAGER = 'khaosat-manager'; // parent
    case KHAOSAT_VIEW = 'khaosat-view';
    case KHAOSAT_CREATE = 'khaosat-create';
    case KHAOSAT_UPDATE = 'khaosat-update';
    case KHAOSAT_PUBLISH = 'khaosat-publish';
    case KHAOSAT_DELETE = 'khaosat-delete';
    case KHAOSAT_RESPONSE_VIEW = 'khaosat-response-view';
    case KHAOSAT_RESPONSE_DELETE = 'khaosat-response-delete';
    case KHAOSAT_STATISTICS_VIEW = 'khaosat-statistics-view';
    case KHAOSAT_EXPORT = 'khaosat-export';

    case SHARED_FOLDER_MANAGER = 'shared-folder-manager';

    // Truyền thông đa kênh (Facebook / Zalo). Quyền sửa bài viết không kéo theo các quyền này.
    case SOCIAL_MANAGER = 'social-manager'; // parent
    case SOCIAL_PUBLICATION_VIEW = 'social-publication-view';
    case SOCIAL_PUBLICATION_CREATE = 'social-publication-create';
    case SOCIAL_PUBLICATION_PUBLISH = 'social-publication-publish';
    case SOCIAL_PUBLICATION_REMOVE = 'social-publication-remove';
    case SOCIAL_PUBLICATION_RETRY = 'social-publication-retry';
    case SOCIAL_ACCOUNT_MANAGE = 'social-account-manage';
    case SOCIAL_STATISTICS_VIEW = 'social-statistics-view';

    public function label(): string
    {
        return match ($this) {
            static::DICHVU_MANAGER => 'Quản lý dịch vụ',
            static::DICHVU_VIEW => 'Xem dịch vụ',
            static::DICHVU_CREATE => 'Thêm dịch vụ',
            static::DICHVU_UPDATE => 'Cập nhật dịch vụ',
            static::DICHVU_DELETE => 'Xóa dịch vụ',

            static::SUKIEN_MANAGER => 'Quản lý sự kiện',
            static::SUKIEN_VIEW => 'Xem sự kiện',
            static::SUKIEN_CREATE => 'Thêm sự kiện',
            static::SUKIEN_UPDATE => 'Cập nhật sự kiện',
            static::SUKIEN_DELETE => 'Xóa sự kiện',

            static::GUONG_TIEU_BIEU_MANAGER => 'Quản lý gương tiêu biểu',
            static::GUONG_TIEU_BIEU_VIEW => 'Xem gương tiêu biểu',
            static::GUONG_TIEU_BIEU_CREATE => 'Thêm gương tiêu biểu',
            static::GUONG_TIEU_BIEU_UPDATE => 'Cập nhật gương tiêu biểu',
            static::GUONG_TIEU_BIEU_DELETE => 'Xóa gương tiêu biểu',

            static::KHAOSAT_MANAGER => 'Quản lý khảo sát',
            static::KHAOSAT_VIEW => 'Xem kết quả khảo sát',
            static::KHAOSAT_CREATE => 'Thêm khảo sát',
            static::KHAOSAT_UPDATE => 'Cập nhật khảo sát',
            static::KHAOSAT_PUBLISH => 'Phát hành khảo sát',
            static::KHAOSAT_DELETE => 'Xóa khảo sát',
            static::KHAOSAT_RESPONSE_VIEW => 'Xem phản hồi khảo sát',
            static::KHAOSAT_RESPONSE_DELETE => 'Xóa phản hồi khảo sát',
            static::KHAOSAT_STATISTICS_VIEW => 'Xem thống kê khảo sát',
            static::KHAOSAT_EXPORT => 'Xuất dữ liệu khảo sát',

            static::DOITAC_MANAGER => 'Quản lý đối tác',
            static::DOITAC_VIEW => 'Xem đối tác',
            static::DOITAC_CREATE => 'Thêm đối tác',
            static::DOITAC_UPDATE => 'Cập nhật đối tác',
            static::DOITAC_DELETE => 'Xóa đối tác',

            static::VANBAN_MANAGER => 'Quản lý văn bản',
            static::VANBAN_VIEW => 'Xem văn bản',
            static::VANBAN_CREATE => 'Thêm văn bản',
            static::VANBAN_UPDATE => 'Cập nhật văn bản',
            static::VANBAN_DELETE => 'Xóa văn bản',

            static::LOAIVANBAN_MANAGER => 'Quản lý loại văn bản',
            static::LOAIVANBAN_VIEW => 'Xem loại văn bản',
            static::LOAIVANBAN_CREATE => 'Thêm loại văn bản',
            static::LOAIVANBAN_UPDATE => 'Cập nhật loại văn bản',
            static::LOAIVANBAN_DELETE => 'Xóa loại văn bản',

            static::NHOMVANBAN_MANAGER => 'Quản lý nhóm văn bản',
            static::NHOMVANBAN_VIEW => 'Xem nhóm văn bản',
            static::NHOMVANBAN_CREATE => 'Thêm nhóm văn bản',
            static::NHOMVANBAN_UPDATE => 'Cập nhật nhóm văn bản',
            static::NHOMVANBAN_DELETE => 'Xóa nhóm văn bản',

            static::MARQUEE_MANAGER => 'Quản lý marquee',
            static::MARQUEE_VIEW => 'Xem marquee',
            static::MARQUEE_CREATE => 'Thêm marquee',
            static::MARQUEE_UPDATE => 'Cập nhật marquee',
            static::MARQUEE_DELETE => 'Xóa marquee',
            static::MARQUEE_APPROVE => 'Duyệt marquee',

            static::HOIDAP_MANAGER => 'Quản lý hỏi đáp',
            static::HOIDAP_VIEW => 'Xem hỏi đáp',
            static::HOIDAP_UPDATE => 'Cập nhật hỏi đáp',
            static::HOIDAP_DELETE => 'Xóa hỏi đáp',
            static::HOIDAP_APPROVE => 'Duyệt hỏi đáp',

            static::LIENHE_MANAGER => 'Quản lý liên hệ',
            static::LIENHE_VIEW => 'Xem liên hệ',
            static::LIENHE_DELETE => 'Xóa liên hệ',

            static::SONOIBAT_MANAGER => 'Quản lý số liệu nổi bật',
            static::SONOIBAT_VIEW => 'Xem số liệu nổi bật',
            static::SONOIBAT_UPDATE => 'Cập nhật số liệu nổi bật',

            static::BAIVIET_MANAGER => 'Quản lý bài viết',
            static::BAIVIET_VIEW => 'Xem bài viết',
            static::BAIVIET_CREATE => 'Thêm bài viết',
            static::BAIVIET_UPDATE => 'Cập nhật bài viết',
            static::BAIVIET_DELETE => 'Xóa bài viết',
            static::BAIVIET_PUBLISHING => 'Xuất bản bài viết',
            static::BAIVIET_APPROVE => 'Duyệt bài viết',
            static::SHARED_FOLDER_MANAGER => 'Quản lý thư mục chung',

            static::SOCIAL_MANAGER => 'Quản lý truyền thông đa kênh',
            static::SOCIAL_PUBLICATION_VIEW => 'Xem bài đăng mạng xã hội',
            static::SOCIAL_PUBLICATION_CREATE => 'Tạo bài đăng mạng xã hội',
            static::SOCIAL_PUBLICATION_PUBLISH => 'Đăng bài lên mạng xã hội',
            static::SOCIAL_PUBLICATION_REMOVE => 'Gỡ bài khỏi mạng xã hội',
            static::SOCIAL_PUBLICATION_RETRY => 'Đăng lại bài lên mạng xã hội',
            static::SOCIAL_ACCOUNT_MANAGE => 'Quản lý tài khoản mạng xã hội',
            static::SOCIAL_STATISTICS_VIEW => 'Xem thống kê tương tác mạng xã hội',


            // LƯỚI AN TOÀN: nếu sau này thêm case mà quên cập nhật, không bị crash
            default => $this->value,
        };
    }

    public static function parentChildren(): array
    {
        return [
            static::DICHVU_MANAGER->label() => [
                static::DICHVU_VIEW->label(),
                static::DICHVU_CREATE->label(),
                static::DICHVU_UPDATE->label(),
                static::DICHVU_DELETE->label()
            ],
            static::SUKIEN_MANAGER->label() => [
                static::SUKIEN_VIEW->label(),
                static::SUKIEN_CREATE->label(),
                static::SUKIEN_UPDATE->label(),
                static::SUKIEN_DELETE->label()
            ],

            static::GUONG_TIEU_BIEU_MANAGER->label() => [
                static::GUONG_TIEU_BIEU_VIEW->label(),
                static::GUONG_TIEU_BIEU_CREATE->label(),
                static::GUONG_TIEU_BIEU_UPDATE->label(),
                static::GUONG_TIEU_BIEU_DELETE->label(),
            ],

            static::KHAOSAT_MANAGER->label() => [
                static::KHAOSAT_VIEW->label(),
                static::KHAOSAT_CREATE->label(),
                static::KHAOSAT_UPDATE->label(),
                static::KHAOSAT_PUBLISH->label(),
                static::KHAOSAT_DELETE->label(),
                static::KHAOSAT_RESPONSE_VIEW->label(),
                static::KHAOSAT_RESPONSE_DELETE->label(),
                static::KHAOSAT_STATISTICS_VIEW->label(),
                static::KHAOSAT_EXPORT->label(),
            ],
            static::DOITAC_MANAGER->label() => [
                // static::DOITAC_VIEW->label(),
                static::DOITAC_CREATE->label(),
                static::DOITAC_UPDATE->label(),
                static::DOITAC_DELETE->label()
            ],
            static::VANBAN_MANAGER->label() => [
                static::VANBAN_VIEW->label(),
                static::VANBAN_CREATE->label(),
                static::VANBAN_UPDATE->label(),
                static::VANBAN_DELETE->label()
            ],
            static::LOAIVANBAN_MANAGER->label() => [
                static::LOAIVANBAN_VIEW->label(),
                static::LOAIVANBAN_CREATE->label(),
                static::LOAIVANBAN_UPDATE->label(),
                static::LOAIVANBAN_DELETE->label()
            ],
            static::NHOMVANBAN_MANAGER->label() => [
                static::NHOMVANBAN_VIEW->label(),
                static::NHOMVANBAN_CREATE->label(),
                static::NHOMVANBAN_UPDATE->label(),
                static::NHOMVANBAN_DELETE->label()
            ],
            static::MARQUEE_MANAGER->label() => [
                static::MARQUEE_VIEW->label(),
                static::MARQUEE_CREATE->label(),
                static::MARQUEE_UPDATE->label(),
                static::MARQUEE_DELETE->label(),
                static::MARQUEE_APPROVE->label()
            ],
            static::HOIDAP_MANAGER->label() => [
                static::HOIDAP_VIEW->label(),
                static::HOIDAP_UPDATE->label(),
                static::HOIDAP_DELETE->label(),
                // static::HOIDAP_APPROVE->label()
            ],
            static::LIENHE_MANAGER->label() => [
                static::LIENHE_VIEW->label(),
                static::LIENHE_DELETE->label(),
            ],
            static::SONOIBAT_MANAGER->label() => [
                static::SONOIBAT_VIEW->label(),
                static::SONOIBAT_UPDATE->label(),
            ],
            static::BAIVIET_MANAGER->label() => [
                static::BAIVIET_VIEW->label(),
                static::BAIVIET_CREATE->label(),
                static::BAIVIET_UPDATE->label(),
                static::BAIVIET_DELETE->label(),
                static::BAIVIET_APPROVE->label(),
                static::BAIVIET_PUBLISHING->label()
            ],
            // SOCIAL_PUBLICATION_PUBLISH / _REMOVE / _RETRY are deliberately NOT children: they act on Facebook/Zalo, so the
            // parent must never grant them (the middleware and SocialPermissions both honour only an explicit permission).
            static::SOCIAL_MANAGER->label() => [
                static::SOCIAL_PUBLICATION_VIEW->label(),
                static::SOCIAL_PUBLICATION_CREATE->label(),
                static::SOCIAL_ACCOUNT_MANAGE->label(),
                static::SOCIAL_STATISTICS_VIEW->label(),
            ],

        ];
    }

    public static function getParent(string $permission): ?string
    {
        foreach (self::parentChildren() as $parent => $children) {
            if (in_array($permission, $children)) {
                return $parent;
            }
        }
        return null;
    }
}
