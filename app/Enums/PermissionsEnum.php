<?php

namespace App\Enums;

enum PermissionsEnum: String
{
    case USER_MANAGER = 'user-manager';
    case ROLE_MANAGER = 'role-manager';
    case CATEGORY_MANAGER = 'category-manager';
        //Bài viết
    case POST_MANAGER = 'post-manager'; // parent
    case POST_VIEW = 'post-view';
    case POST_CREATE = 'post-create';
    case POST_APPROVE = 'post-approve';
    case POST_UPDATE = 'post-update';
    case POST_PUBLISHING = 'post-publishing';
    case POST_DELETE = 'post-delete';


        //trang
    case PAGE_MANAGER = 'page-manager'; // parent
    case PAGE_VIEW = 'page-view';
    case PAGE_CREATE = 'page-create';
    case PAGE_APPROVE = 'page-approve';
    case PAGE_UPDATE = 'page-update';
    case PAGE_PUBLISHING = 'page-publishing';
    case PAGE_DELETE = 'page-delete';

        //comment
    case COMMENT_MANAGER = 'comment-manager';
    case COMMENT_VIEW = 'comment-view';
        // case COMMENT_CREATE = 'comment-create';
    case COMMENT_APPROVE = 'comment-approve';
        // case COMMENT_UPDATE = 'comment-update';
    case COMMENT_REJECT = 'comment-reject';
        // case COMMENT_PUBLISHING = 'comment-publishing';
    case COMMENT_DELETE = 'comment-delete';

        // Quản lý Menu
    case MENU_MANAGER = 'menu-manager';
        // Cài đặt trang chủ
    case HOMEPAGE_MANAGER = 'homepage-manager';
        // Trang tuyển sinh
    case TUYENSINH_MANAGER    = 'tuyensinh-manager';
        // Cài đặt chung
    case ABOUT_MANAGER    = 'about-manager';

        // Quản lý chuyên mục
    case TERM_MANAGER    = 'term-manager';
        // Quản lý liên kết
    case LINK_MANAGER    = 'link-manager';
        // Quản lý banner  
    case BANNER_MANAGER = 'banner-manager'; // parent
    case BANNER_VIEW = 'banner-view';
    case BANNER_CREATE = 'banner-create';
    case BANNER_APPROVE = 'banner-approve';
    case BANNER_UPDATE = 'banner-update';
    case BANNER_PUBLISHING = 'banner-publishing';
    case BANNER_DELETE = 'banner-delete';

        // Quản video
    case VIDEO_MANAGER    = 'video-manager';
        // Quản lý thư viện - hình ảnh
    case MEDIA_MANAGER    = 'media-manager';

    public function label(): string
    {
        return match ($this) {
            static::USER_MANAGER => 'Quản lý người dùng',
            static::ROLE_MANAGER => 'Quản lý vai trò',
            static::CATEGORY_MANAGER => 'Quản lý vai trò',

            static::POST_MANAGER => 'Quản lý bài viết',
            static::POST_VIEW => 'Xem bài viết',
            static::POST_CREATE => 'Thêm bài viết',
            static::POST_APPROVE => 'Duyệt bài viết',
            static::POST_UPDATE => 'Cập nhật bài viết',
            static::POST_PUBLISHING => 'Xuất bản bài viết',
            static::POST_DELETE => 'Xóa bài viết',
            static::PAGE_MANAGER => 'Quản lý trang',
            static::PAGE_VIEW => 'Xem trang',
            static::PAGE_CREATE => 'Thêm trang',
            static::PAGE_APPROVE => 'Duyệt trang',
            static::PAGE_UPDATE => 'Cập nhật trang',
            static::PAGE_PUBLISHING => 'Xuất bản trang',
            static::PAGE_DELETE => 'Xóa trang',

            static::COMMENT_MANAGER    => 'Quản lý bình luận',
            // static::COMMENT_PUBLISHING => 'Xuất bản bình luận',
            static::COMMENT_DELETE     => 'Xóa bình luận',
            // static::COMMENT_VIEW => 'Xem bình luận',
            static::COMMENT_VIEW => 'Trả lời bình luận',
            // static::COMMENT_CREATE => 'Tạo bình luận',
            static::COMMENT_APPROVE => 'Duyệt bình luận',
            // static::COMMENT_UPDATE => 'Cập nhật bình luận',
            static::COMMENT_REJECT => 'Không duyệt bình luận',



            // BỔ SUNG MỚI
            static::MENU_MANAGER => 'Quản lý menu',
            static::HOMEPAGE_MANAGER => 'Quản lý trang chủ',
            static::TUYENSINH_MANAGER => 'Quản lý trang tuyển sinh',
            static::ABOUT_MANAGER => 'Cài đặt chung',


            static::TERM_MANAGER => 'Quản lý chuyên mục',
            static::LINK_MANAGER => 'Quản lý liên kết Website',

            static::BANNER_MANAGER => 'Quản lý banner',
            static::BANNER_VIEW => 'Xem banner',
            static::BANNER_CREATE => 'Thêm banner',
            static::BANNER_APPROVE => 'Duyệt banner',
            static::BANNER_UPDATE => 'Cập nhật banner',
            static::BANNER_PUBLISHING => 'Xuất bản banner',
            static::BANNER_DELETE => 'Xóa banner',

            static::VIDEO_MANAGER => 'Quản lý video',
            static::MEDIA_MANAGER => 'Quản lý hư viện - hình ảnh',
        };
    }

    public static function parentChildren(): array
    {
        return [
            static::POST_MANAGER->label() => [
                static::POST_VIEW->label(),
                static::POST_CREATE->label(),
                static::POST_UPDATE->label(),
                static::POST_PUBLISHING->label(),
                static::POST_APPROVE->label(),
                static::POST_DELETE->label(),
            ],

            static::PAGE_MANAGER->label() => [
                static::PAGE_VIEW->label(),
                static::PAGE_CREATE->label(),
                static::PAGE_UPDATE->label(),
                static::PAGE_PUBLISHING->label(),
                static::PAGE_APPROVE->label(),
                static::PAGE_DELETE->label(),
            ],

            static::COMMENT_MANAGER->label() => [
                static::COMMENT_VIEW->label(),
                // static::COMMENT_CREATE->label(),
                // static::COMMENT_UPDATE->label(),
                // static::COMMENT_PUBLISHING->label(),
                static::COMMENT_REJECT->label(),
                static::COMMENT_APPROVE->label(),
                static::COMMENT_DELETE->label(),
            ],

            static::BANNER_MANAGER->label() => [
                static::BANNER_VIEW->label(),
                static::BANNER_CREATE->label(),
                static::BANNER_UPDATE->label(),
                static::BANNER_PUBLISHING->label(),
                static::BANNER_APPROVE->label(),
                static::BANNER_DELETE->label(),
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
