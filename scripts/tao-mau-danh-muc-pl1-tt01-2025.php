<?php

/**
 * Sinh tep mau danh muc benh Phu luc I Thong tu 01/2025/TT-BYT de nhap qua man Nhap danh muc.
 *
 * NGUON: ban PDF co chu ky so cua Thong tu 01/2025/TT-BYT, Phu luc I (trang 19-25), doi
 * chieu voi ban go lai tai blogbhxh.com. Ky hieu † sau ma (A17.0†, B42.0†, M32.1†) la ky
 * hieu phan loai kep cua ICD-10, khong thuoc ma - da bo.
 *
 * Quy uoc tach dong (spec muc 5.2):
 *   - moi ma, hoac moi ma 3 ky tu trong mot khoang, la mot dong BAO_GOM cung STT;
 *   - moi ma tru la mot dong TRU cung STT;
 *   - dong 44 van ban in I51.2, ma ICD-10 dung cua benh la L51.2 - ghi ca hai;
 *   - TUOI_DUOI = 18 cho moi dong cua STT 22, 30, 31, 32, 58.
 *
 * Chay: php scripts/tao-mau-danh-muc-pl1-tt01-2025.php
 */

require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$NGAY = 'Người bệnh được hưởng quyền lợi ngay trong lượt khám bệnh, chữa bệnh có kết quả chẩn đoán xác định mắc bệnh.';
$KDH  = 'Không áp dụng đối với trường hợp đã được chẩn đoán xác định nhưng không có chỉ định điều trị đặc hiệu.';
$NANG = 'Tình trạng tiến triển nặng theo hướng dẫn chẩn đoán, điều trị.';

/** Cac ma 3 ky tu tu $tu den $den cung chu cai, vd khoang('C', 0, 97). */
function khoang($chu, $tu, $den)
{
    $ra = [];
    for ($i = $tu; $i <= $den; $i++) {
        $ra[] = sprintf('%s%02d', $chu, $i);
    }
    return $ra;
}

// [STT, TEN_BENH, [ma BAO_GOM], [ma TRU], TUOI_DUOI, DIEU_KIEN]
$pl1 = [
    [1, 'Viêm màng não do lao (G01*)', ['A17.0'], [], null, ''],
    [2, 'U lao màng não (G07*)', ['A17.1'], [], null, ''],
    [3, 'Lao khác của hệ thần kinh', ['A17.8'], [], null, ''],
    [4, 'Lao hệ thần kinh, không xác định (G99.8*)', ['A17.9'], [], null, ''],
    [5, 'Nhiễm mycobacteria ở phổi', ['A31.0'], [], null, ''],
    [6, 'Nhiễm histoplasma capsulatum ở phổi cấp tính', ['B39.0'], [], null, ''],
    [7, 'Nhiễm nấm blastomyces ở phổi cấp tính', ['B40.0'], [], null, ''],
    [8, 'Nhiễm nấm paracoccidioides ở phổi', ['B41.0'], [], null, ''],
    [9, 'Nhiễm sporotrichum ở phổi (J99.8*)', ['B42.0'], [], null, ''],
    [10, 'Nhiễm aspergillus ở phổi xâm lấn', ['B44.0'], [], null, ''],
    [11, 'Nhiễm cryptococcus ở phổi', ['B45.0'], [], null, ''],
    [12, 'Nhiễm mucor ở phổi', ['B46.0'], [], null, ''],
    [13, 'Nhiễm mucor lan toả', ['B46.4'], [], null, ''],
    [14, 'U ác tụy', ['C25'], [], null, $NGAY],
    [15, 'U ác tuyến ức', ['C37'], [], null, $NGAY],
    [16, 'U ác của tim, trung thất và màng phổi', ['C38'], ['C38.4'], null, $NGAY],
    [17, 'U ác của xương và sụn khớp ở vị trí khác và không xác định', ['C41'], [], null, $NGAY],
    [18, 'U ác của màng não', ['C70'], [], null, $NGAY],
    [19, 'U ác của não', ['C71'], [], null, $NGAY],
    [20, 'U ác của tủy sống, dây thần kinh sọ và các phần khác của hệ thần kinh trung ương', ['C72'], [], null, $NGAY],
    [21, 'U ác thứ phát của não và màng não', ['C79.3'], [], null, $NGAY],
    [22, 'Nhóm u ác tính', khoang('C', 0, 97), [], 18,
        'Có đủ 02 điều kiện sau đây: - Người dưới 18 tuổi. - ' . $KDH],
    [23, 'U ác của hệ lympho, hệ tạo máu và các mô liên quan',
        array_merge(khoang('C', 81, 86), khoang('C', 90, 96)), ['C83.5'], null, $KDH],
    [24, 'Hội chứng loạn sản tủy xương', ['D46'], [], null, $KDH],
    [25, 'Các thể suy tủy xương khác', ['D61'], ['D61.9'], null, $KDH],
    [26, 'Bệnh tăng đông máu khác (Hội chứng kháng phospho lipid)', ['D68.6'], [], null, ''],
    [27, 'Hội chứng thực bào tế bào máu liên quan đến nhiễm trùng', ['D76.2'], [], null, ''],
    [28, 'Bệnh đái tháo đường phụ thuộc insuline (Có đa biến chứng)', ['E10.7'], [], null,
        'Có biến chứng loét bàn chân độ 2 hoặc có bệnh thận mạn giai đoạn 3 trở lên hoặc có ít nhất 02 trong số các biến chứng: tim mạch, mắt, thần kinh, mạch máu.'],
    [29, 'Bệnh đái tháo đường không phụ thuộc insuline (Có đa biến chứng)', ['E11.7'], [], null,
        'Có biến chứng loét bàn chân độ 2 hoặc có bệnh thận mạn giai đoạn 3 trở lên.'],
    [30, 'Rối loạn chuyển hóa acid amin thơm', ['E70'], [], 18, 'Người dưới 18 tuổi.'],
    [31, 'Rối loạn chuyển hóa acid amin chuỗi nhánh và rối loạn chuyển hóa acid béo', ['E71'], [], 18, 'Người dưới 18 tuổi.'],
    [32, 'Các rối loạn khác của chuyển hóa acid amin', ['E72'], [], 18, 'Người dưới 18 tuổi.'],
    [33, 'Nhóm rối loạn dự trữ thể tiêu bào (Bệnh Pompe, bệnh MPS, Bệnh Gaucher, Bệnh Fabry)', ['E74', 'E75', 'E76'], [], null,
        'Áp mã theo ICD-10 của WHO cập nhật năm 2021. ' . $NGAY],
    [34, 'Rối loạn chuyển hóa đồng (bao gồm cả bệnh Wilson)', ['E83.0'], [], null,
        'Bệnh Wilson có biến chứng (có một trong các biến chứng của xơ gan, suy gan cấp, tối cấp, suy thận cấp, rối loạn vận động, rối loạn vận ngôn, rối loạn tâm thần, sa sút trí tuệ, động kinh bệnh cơ tim, rối loạn nhịp tim).'],
    [35, 'Thoái hóa dạng bột', ['E85'], [], null, $KDH],
    [36, 'Rối loạn trầm cảm tái diễn', ['F33'], [], null, '- Kháng thuốc. - ' . $NGAY],
    [37, 'Rối loạn ám ảnh nghi thức', ['F42'], [], null, ''],
    [38, 'Viêm não, viêm tủy và viêm não-tủy', ['G04'], ['G04.2'], null, ''],
    [39, 'Xơ cứng rải rác', ['G35'], [], null, ''],
    [40, 'Viêm tủy thị thần kinh [Devic]', ['G36.0'], [], null, ''],
    [41, 'Nhược cơ', ['G70.0'], [], null, '- Trường hợp phải lọc máu, suy hô hấp. - ' . $NGAY],
    [42, 'Bệnh lý võng mạc của trẻ đẻ non', ['H35.1'], [], null, $NGAY],
    [43, 'Suy tim', ['I50'], [], null, 'Đã có kết luận chẩn đoán giai đoạn 3, giai đoạn 4.'],
    [44, 'Hoại tử thượng bì nhiễm độc (Lyell/Steven Johnson)', ['I51.2', 'L51.2'], [], null,
        '[Ghi chú danh mục] Văn bản in mã I51.2; mã ICD-10 đúng của bệnh là L51.2 - danh mục ghi cả hai.'],
    [45, 'Hội chứng sau mổ tim', ['I97.0'], [], null, ''],
    [46, 'Rối loạn chức năng khác sau phẫu thuật tim', ['I97.1'], [], null, ''],
    [47, 'Bệnh phổi mô kẽ khác', ['J84'], [], null, ''],
    [48, 'Áp xe phổi và trung thất', ['J85'], [], null, $NANG],
    [49, 'Mủ lồng ngực (nhiễm trùng nặng ở phổi)', ['J86'], [], null, $NANG],
    [50, 'Bệnh Crohn (viêm ruột từng vùng)', ['K50'], [], null,
        'Mức độ nặng theo thang điểm CDAI từ 450 điểm trở lên, hoặc có biến chứng như rò, thủng, áp xe trong ổ bụng, suy dinh dưỡng nặng.'],
    [51, 'Pemphigus', ['L10'], [], null,
        'Một trong các điều kiện sau đây: - Tổn thương da >10% diện tích cơ thể. - Tình trạng tiến triển bệnh nặng theo hướng dẫn chẩn đoán và điều trị. - Á u.'],
    [52, 'Viêm mạch mạng lưới', ['L95.0'], [], null, ''],
    [53, 'Bệnh da tăng bạch cầu trung tính có sốt [Hội chứng Sweet]', ['L98.2'], [], null, ''],
    [54, 'Bệnh Lupus ban đỏ hệ thống có tổn thương phủ tạng', ['M32.1'], [], null,
        '- Tổn thương tim hoặc phổi hoặc thận nặng, tiến triển, đe dọa tính mạng. - ' . $NGAY],
    [55, 'Đái tháo đường sơ sinh', ['P70.2'], [], null, $NGAY],
    [56, 'Dị tật bẩm sinh khác của não', ['Q04'], [], null, $NGAY],
    [57, 'Các dị tật bẩm sinh khác của tủy sống', ['Q06'], [], null, $NGAY],
    [58, 'Nhóm các dị tật bẩm sinh của hệ thống tuần hoàn', khoang('Q', 20, 28), [], 18,
        'Người dưới 18 tuổi thuộc một trong 02 trường hợp sau đây: - Phẫu thuật/can thiệp loại đặc biệt. - 03 phẫu thuật/can thiệp đồng thời trở lên.'],
    [59, 'Biến dạng bẩm sinh của khớp háng', ['Q65'], [], null, 'Có chỉ định thay khớp.'],
    [60, 'Kháng (các) thuốc chống lao', ['U84.3'], [], null, ''],
    [61, 'Di chứng của hoạt động chiến tranh (Di chứng do vết thương chiến tranh)', ['Y89.1'], [], null,
        'Áp dụng đối với thương binh, bệnh binh, người có công với cách mạng.'],
    [62, 'Tình trạng của mảnh ghép cơ quan và tổ chức', ['Z94'], [], null,
        'Áp dụng đối với người bệnh có ghép tạng và điều trị sau ghép tạng.'],
];

$wb = new Spreadsheet();
$ws = $wb->getActiveSheet();
$ws->setTitle('PL1_TT01_2025');
$ws->fromArray(['STT', 'TEN_BENH', 'MA_ICD', 'LOAI', 'TUOI_DUOI', 'DIEU_KIEN'], null, 'A1');

$r = 2;
foreach ($pl1 as $d) {
    list($stt, $ten, $baoGom, $tru, $tuoi, $dieuKien) = $d;

    foreach ([['BAO_GOM', $baoGom], ['TRU', $tru]] as $nhom) {
        foreach ($nhom[1] as $ma) {
            $ws->setCellValue('A' . $r, $stt);
            $ws->setCellValueExplicit('B' . $r, $ten, DataType::TYPE_STRING);
            $ws->setCellValueExplicit('C' . $r, $ma, DataType::TYPE_STRING);
            $ws->setCellValueExplicit('D' . $r, $nhom[0], DataType::TYPE_STRING);
            if ($tuoi !== null) {
                $ws->setCellValue('E' . $r, $tuoi);
            }
            $ws->setCellValueExplicit('F' . $r, $dieuKien, DataType::TYPE_STRING);
            $r++;
        }
    }
}

$dich = __DIR__ . '/../docs/0000 - Danh muc/PL1_TT01_2025.xlsx';
(new Xlsx($wb))->save($dich);

printf("so_dong=%d tep=%s\n", $r - 2, realpath($dich));
