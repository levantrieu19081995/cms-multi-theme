<?php

namespace App\Enums;

use App\Dto\OptionDto;

enum OptionEnum: string
{
    case WEB_TITLE = 'web_title';
    case WEB_DESCRIPTION = 'web_description';
    case WEB_LOGO = 'web_logo';
    case WEB_ADDRESS = 'web_address';
    case WEB_LOCATION = 'web_location';
    case WEB_PHONE = 'web_phone';
    case WEB_EMAIL = 'web_email';
    case ENABLE_GUEST_REGISTER = 'enable_guest_register';
    case CATEGORY_DEFAULT = 'category_default';
    case POST_FORMAT_DEFAULT = 'post_format_default';
    case WEB_CATEGORY_SHOW_HOMEPAGE = 'web_category_show_homepage';
    case WEB_CATEGORY_SHOW_HOMEPAGE_COL_NUM = 'web_category_show_homepage_col_num';
    case WEB_ZALO = 'web_zalo';
    case WEB_FACEBOOK = 'web_facebook';
    case WEB_TWITTER = 'web_twitter';
    case WEB_GOOGLE_PLUS = 'web_google_plus';
    case WEB_INSTAGRAM = 'web_instagram';

        //
        // case WEB_ZALO = 'web_zalo';
    case WEB_TIKTOK = 'web_tiktok';
    case WEB_YOUTUBE = 'web_youtube';
    case WEB_THEARDS = 'web_threads';
    case WEB_LINKEDIN = 'web_linkedin';
        //

    case WEB_CATEGORY_HOME = 'web_category_home';
    case WEB_CATEGORY_TUYEN_SINH = 'web_category_tuyen_sinh';
    case WEB_CATEGORY_THONG_TIN_TIN_TUC_TUYEN_SINH = 'web_category_thong_tin_tin_tuc_tuyen_sinh';
    case WEB_TITLE_COLOR = 'web_title_color';
    case SCHOOL_LIST = 'school_list';




    public function label(): string
    {
        return match ($this) {
            static::WEB_TITLE => 'Tiêu đề trang web',
            static::WEB_DESCRIPTION => 'Dòng mô tả',
            static::WEB_LOGO => 'Biểu tượng trang web',
            static::WEB_ADDRESS => '128 đường 30/4',
            static::WEB_LOCATION => 'facebook.com',
            static::WEB_PHONE => '094559554',
            static::WEB_EMAIL => 'Cho phép đăng ký',
            static::ENABLE_GUEST_REGISTER => 'Cho phép đăng ký',
            static::CATEGORY_DEFAULT => 'Chuyên mục mặc định',
            static::POST_FORMAT_DEFAULT => 'Định dạng bài viết mặc định',
            static::WEB_CATEGORY_SHOW_HOMEPAGE => 'Chuyên mục hiển thị tại trang chủ',
            static::WEB_CATEGORY_SHOW_HOMEPAGE_COL_NUM => 'Số cột hiển thị(1-4)',
            static::WEB_ZALO => 'Zalo OA',
            static::WEB_FACEBOOK => 'Fanpage Facebook',
            static::WEB_TWITTER => 'Twitter',
            static::WEB_GOOGLE_PLUS => 'google_plus',
            static::WEB_INSTAGRAM => 'Instagram',

            //
            static::WEB_ZALO => 'Zalo',
            static::WEB_TIKTOK => 'TikTok',
            static::WEB_YOUTUBE => 'YouTube',
            static::WEB_THEARDS => 'Threads',
            static::WEB_LINKEDIN => 'LinkedIn',
            //

            static::WEB_CATEGORY_HOME => 'Chuyên mục Thông tin hiển thị tại trang chủ',
            static::WEB_CATEGORY_TUYEN_SINH => 'Chuyên mục Thông tin hiển thị tại trang tuyển sinh',
            static::WEB_CATEGORY_THONG_TIN_TIN_TUC_TUYEN_SINH => 'Chuyên mục Thông tin tin tức trang tuyển sinh',
            static::WEB_TITLE_COLOR => 'Màu tiêu đề trang web',
            static::SCHOOL_LIST => 'Danh sách các trường THPT',
        };
    }

    public function value(): OptionDto
    {
        return match ($this) {
            static::WEB_TITLE => new OptionDto($this->value, '', 'Tiêu đề mặc định'),
            static::WEB_DESCRIPTION => new OptionDto($this->value, '', 'Dòng mô tả trang web'),
            static::WEB_LOGO => new OptionDto($this->value, '', ''),
            static::WEB_ADDRESS => new OptionDto($this->value, '', ''),
            static::WEB_LOCATION => new OptionDto($this->value, '', ''),
            static::WEB_PHONE => new OptionDto($this->value, '', ''),
            static::WEB_EMAIL => new OptionDto($this->value, '', ''),
            static::ENABLE_GUEST_REGISTER => new OptionDto($this->value, '', '0'),
            static::CATEGORY_DEFAULT => new OptionDto($this->value, '', '0'),
            static::POST_FORMAT_DEFAULT => new OptionDto($this->value, '', '0'),
            static::WEB_CATEGORY_SHOW_HOMEPAGE => new OptionDto($this->value, '', '[]'),
            static::WEB_CATEGORY_SHOW_HOMEPAGE_COL_NUM => new OptionDto($this->value, '', '3'),
            static::WEB_ZALO => new OptionDto($this->value, '', '#'),
            static::WEB_FACEBOOK => new OptionDto($this->value, '', ''),
            static::WEB_TWITTER => new OptionDto($this->value, '', ''),
            static::WEB_GOOGLE_PLUS => new OptionDto($this->value, '', ''),
            static::WEB_INSTAGRAM => new OptionDto($this->value, '', ''),
            //
            static::WEB_ZALO => new OptionDto($this->value, '', ''),
            static::WEB_TIKTOK => new OptionDto($this->value, '', ''),
            static::WEB_YOUTUBE => new OptionDto($this->value, '', ''),
            static::WEB_THEARDS => new OptionDto($this->value, '', ''),
            static::WEB_LINKEDIN => new OptionDto($this->value, '', ''),
            //
            static::WEB_CATEGORY_HOME => new OptionDto($this->value, '', ''),
            static::WEB_CATEGORY_TUYEN_SINH => new OptionDto($this->value, '', ''),
            static::WEB_CATEGORY_THONG_TIN_TIN_TUC_TUYEN_SINH => new OptionDto($this->value, '', ''),
            static::WEB_TITLE_COLOR => new OptionDto($this->value, '', '#233767'), // mặc định màu đen
            static::SCHOOL_LIST => new OptionDto($this->value, '', '[]'),
        };
    }
}
