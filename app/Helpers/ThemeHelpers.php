<?php

namespace App\Helpers;

class ThemeHelpers
{
    public function getThemePath()
    {
        return config('view.theme_path') . '/' . config('view.theme');
    }

    public function asset($asset)
    {
        $strict = config('view.strict');
        if (is_array($asset)) {
            $assets = [];
            foreach ($asset as $path) {
                if ($strict || file_exists(base_path() . '/' . $this->getThemePath() . '/' . $path)) {
                    $assets[] = $this->getThemePath() . '/' . $path;
                } else {
                  AlertHelpers::danger('@Vite Không tìm thấy <b>' . $this->getThemePath() . '/' . $path . ' </b>');
                }
            }
            return $assets;
        }
        if ($strict || file_exists(base_path() . '/' . $this->getThemePath() . '/' . $asset)) {
          return $this->getThemePath() . '/' . $asset;
        } else {
          AlertHelpers::danger('@Vite Không tìm thấy <b>' . $this->getThemePath() . '/' . $asset . ' </b>');
        }
    }
}
