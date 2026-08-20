<?php

namespace Tests\Support;

use App\Services\XMLSignService;

/**
 * Lop ky gia, ke thua lop that de giu dung chu ky phuong thuc.
 * KHONG dung createMock(): PHPUnit 6 sinh deprecation ReflectionType voi lop co kieu tra ve.
 */
class FakeXMLSignService extends XMLSignService
{
    public $ketQua = ['isSigned' => true, 'data' => '<DAKY/>', 'method' => 'USB Token'];
    public $xmlNhanDuoc = null;
    public $soLanGoi = 0;

    public function __construct()
    {
        // Bo qua constructor cha de khong khoi tao Guzzle/Config that.
    }

    public function signXml($xmlContent)
    {
        $this->soLanGoi++;
        $this->xmlNhanDuoc = $xmlContent;

        return $this->ketQua;
    }
}
