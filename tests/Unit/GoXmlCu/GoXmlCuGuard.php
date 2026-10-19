<?php

namespace Tests\Unit\GoXmlCu;

/**
 * Quet ma nguon tim tham chieu toi module XML da go (4210, Qd130). Mot ky hieu song lai -
 * route, class, ten lenh - la mot cho se nem loi khi chay, nen chan ngay o test.
 */
trait GoXmlCuGuard
{
    /** Ky hieu cua module XML4210 (ten ma chung khong hau to). */
    protected static $KY_HIEU_4210 = [
        '/\bBHYTController\b/',
        '/Models\\\\BHYT\\\\XML[1-5]\b/',
        '/\bXmlErrorCheck\b/',
        '/Models\\\\BHYT\\\\XmlErrorCatalog\b/',
        '/\bXMLService\b/',
        '/\bXml[123]Checker\b/',
        '/\bXmlErrorService\b/',
        '/\bCheckXmlErrorsJob\b/',
        '/\bXML4210Import\b/',
        '/\bUploadXML\b/',
        '/\bBHYTKiemTraHoSo\b/',
        '/App\\\\XML4210\b/',
        '/[\'"]bhyt\.(index|detailxml|check-card|search|get-xml|process-check-card|kcb-trai-tuyen|dvkt-co-dieu-kien|thuoc-co-dieu-kien)[\'"]/',
        '/[\'"]xml\.delete[\'"]/',
        '/[\'"]system\.(do-)?upload-xml[\'"]/',
        '/xml4210import:day/',
        '/disk\(\s*[\'"]xml4210/',
        '/fetch-xml-error-catalog\b/',
        '/(dvkt|dmt)CoDieuKien\b/',
        '/danh-muc\.(update-)?(dvkt|dm-thuoc)-co-dieu-kien/',
    ];

    /** Thu muc quet (tuong doi base_path). */
    protected static $THU_MUC_QUET = ['app', 'routes', 'config', 'resources/views', 'tests'];

    /**
     * Cau hinh rieng tung may (.gitignore) - sua o day khong len prod; khoa thua vo hai khi
     * khong con ma doc. Van hanh tu don theo checklist trien khai.
     */
    protected static $BO_QUA = ['config/filesystems.php', 'config/organization.php', 'config/database.php'];

    /**
     * @param string[] $kyHieu Regex
     * @param string[] $thuMuc Thu muc quet them ngoai $THU_MUC_QUET
     * @return string[] 'duong/dan:dong: ky hieu'
     */
    protected function quetViPham(array $kyHieu, array $thuMuc = []): array
    {
        $viPham = [];
        $goc = str_replace('\\', '/', base_path()) . '/';

        foreach (array_merge(self::$THU_MUC_QUET, $thuMuc) as $tm) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($tm), \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                $duong = str_replace('\\', '/', $file->getPathname());
                $tuongDoi = substr($duong, strlen($goc));
                if (strpos($tuongDoi, 'tests/Unit/GoXmlCu/') === 0 || in_array($tuongDoi, self::$BO_QUA, true) || !preg_match('/\.(php|bat|sh|js)$/', $tuongDoi)) {
                    continue;
                }
                foreach (file($duong) as $i => $dong) {
                    foreach ($kyHieu as $re) {
                        if (preg_match($re, $dong)) {
                            $viPham[] = $tuongDoi . ':' . ($i + 1) . ': ' . trim($dong);
                            break;
                        }
                    }
                }
            }
        }

        return $viPham;
    }
}
