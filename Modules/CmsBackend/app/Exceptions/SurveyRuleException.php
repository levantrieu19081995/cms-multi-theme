<?php

namespace Modules\CmsBackend\Exceptions;

use RuntimeException;

/** Vi phạm quy tắc nghiệp vụ khảo sát. getMessage() là nội dung hiển thị cho người dùng (đã dịch). */
class SurveyRuleException extends RuntimeException
{
    public static function key(string $key, array $replace = []): self
    {
        return new self(__('admin.survey.error.'.$key, $replace));
    }
}
