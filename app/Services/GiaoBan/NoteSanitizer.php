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

    /** Do dai toi da cua mot chi tieu nhap tay kieu chuoi. */
    const MAX_PLAIN = 5000;

    /**
     * Lam sach VAN BAN THUAN cho chi tieu nhap tay kieu chuoi (danh sach benh nhan...).
     *
     * Khac clean() o cho khong giu bat ky the nao: clean() dung HTMLPurifier nen vua escape
     * dau < cua chi so lam sang ("Hb < 8") vua GIU <b> thanh the that — nua no nua kia,
     * do vao textarea se hien lan lon.
     *
     * Ma hoa toan bo bang htmlspecialchars: gia tri trong DB khong bao gio chay duoc du
     * hien thi bang .html() hay innerHTML. Hong thi hong theo huong an toan.
     *
     * Doi lai: khi nap vao textarea PHAI giai ma nguoc (htmlspecialchars_decode / DOM),
     * neu khong se ma hoa kep dan qua tung lan sua.
     */
    public static function cleanPlain($s)
    {
        $s = (string) $s;
        if ($s === '') {
            return '';
        }
        // chuan hoa xuong dong ve \n
        $s = str_replace(array("\r\n", "\r"), "\n", $s);
        // bo ky tu dieu khien, giu lai \n (0x0A) va \t (0x09)
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s);
        $s = htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return mb_substr($s, 0, self::MAX_PLAIN);
    }
}
