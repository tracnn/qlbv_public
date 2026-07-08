<?php

namespace App\Services\GiaoBan;

/**
 * Làm sạch HTML ghi chú giao ban: chỉ giữ định dạng cơ bản an toàn (chống XSS).
 * Dùng HTMLPurifier. Cache tắt để không cần thư mục ghi.
 */
class NoteSanitizer
{
    public static function clean($html)
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return '';
        }
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.Allowed', 'p,br,b,strong,i,em,u,ul,ol,li,span[style],div[style],h3,h4');
        $config->set('CSS.AllowedProperties', 'color,font-size,text-align,font-weight,font-style,text-decoration');
        $config->set('AutoFormat.RemoveEmpty', true);
        $purifier = new \HTMLPurifier($config);
        return $purifier->purify($html);
    }
}
