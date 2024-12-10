<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Models\BHYT\MedicineCatalog;
use App\Models\BHYT\MedicalSupplyCatalog;
use App\Models\BHYT\ServiceCatalog;
use App\Models\BHYT\MedicalStaff;
use App\Models\BHYT\DepartmentBedCatalog;
use App\Models\BHYT\EquipmentCatalog;
use App\Models\BHYT\AdministrativeUnit;
use App\Models\BHYT\MedicalOrganization;

class CatalogImportService
{
    public function import($filePath)
    {
        $data = Excel::toCollection(null, $filePath)->first();

        if ($data->isEmpty()) {
            throw new \Exception('File không chứa dữ liệu');
        }

        $firstRow = $data->first()->values()->toArray();

        switch (true) {
            case $firstRow === $this->expectedMedicineColumns():
                return $this->importMedicineCatalog($data);

            case $firstRow === $this->expectedSupplyColumns():
                return $this->importMedicalSupplyCatalog($data);

            case $firstRow === $this->expectedServiceColumns():
                return $this->importServiceCatalog($data);

            case $firstRow === $this->expectedStaffColumns():
                return $this->importMedicalStaff($data);

            case $firstRow === $this->expectedDepartmentColumns():
                return $this->importDepartmentBedCatalog($data);

            case $firstRow === $this->expectedEquipmentColumns():
                return $this->importEquipmentCatalog($data);

            case $firstRow === $this->expectedAdministrativeUnitsColumns():
                return $this->importAdministrativeUnits($data);

            case $firstRow === $this->expectedMedicalOrganizationColumns():
                return $this->importMedicalOrganization($data);

            default:
                throw new \Exception('Cấu trúc file không hợp lệ');
        }
    }

    private function expectedMedicineColumns()
    {
        return [
            "STT", "MA_THUOC", "TEN_HOAT_CHAT", "TEN_THUOC", "DON_VI_TINH",
            "HAM_LUONG", "DUONG_DUNG", "MA_DUONG_DUNG", "DANG_BAO_CHE", "SO_DANG_KY",
            "SO_LUONG", "DON_GIA", "DON_GIA_BH", "QUY_CACH", "NHA_SX", "NUOC_SX",
            "NHA_THAU", "TT_THAU", "TU_NGAY", "DEN_NGAY", "MA_CSKCB", "LOAI_THUOC",
            "LOAI_THAU", "HT_THAU", "MA_DVKT", "TCCL", "BO_PHAN_VT", "TEN_KHOA_HOC",
            "NGUON_GOC", "PP_CHEBIEN", "MA_DL_NHAP", "MA_DL_CB", "TLHH_CB", "TLHH_BQ",
            "DENNGAY", "ID"
        ];
    }

    private function expectedSupplyColumns()
    {
        return [
            "STT", "MA_VAT_TU", "NHOM_VAT_TU", "TEN_VAT_TU", "MA_HIEU",
            "QUY_CACH", "HANG_SX", "NUOC_SX", "DON_VI_TINH", "DON_GIA",
            "DON_GIA_BH", "TYLE_TT_BH", "SO_LUONG", "DINH_MUC", "NHA_THAU",
            "TT_THAU", "TU_NGAY", "DEN_NGAY_HD", "MA_CSKCB", "LOAI_THAU",
            "HT_THAU", "DENNGAY", "ID"
        ];
    }

    private function expectedServiceColumns()
    {
        return [
            "STT", "MA_TUONG_DUONG", "TEN_DVKT_PHEDUYET", "TEN_DVKT_GIA", "PHAN_LOAI_PTTT", "DON_GIA", "GHI_CHU",
            "QUYET_DINH", "TUNGAY", "DENNGAY"
        ];
    }

    private function expectedStaffColumns()
    {
        return [
            "STT", "MA_LOAI_KCB", "MA_KHOA", "TEN_KHOA", "MA_BHXH",
            "HO_TEN", "GIOI_TINH", "CHUCDANH_NN", "VI_TRI", "MACCHN",
            "NGAYCAP_CCHN", "NOICAP_CCHN", "PHAMVI_CM", "PHAMVI_CMBS", "DVKT_KHAC",
            "VB_PHANCONG", "THOIGIAN_DK", "THOIGIAN_NGAY", "THOIGIAN_TUAN", "CSKCB_KHAC",
            "CSKCB_CGKT", "QD_CGKT", "TU_NGAY", "DEN_NGAY", "ID"
        ];
    }

    private function expectedDepartmentColumns()
    {
        return [
            "STT", "MA_LOAI_KCB", "MA_KHOA", "TEN_KHOA", "BAN_KHAM",
            "GIUONG_PD", "GIUONG_2015", "GIUONG_TK", "GIUONG_HSTC", "GIUONG_HSCC",
            "LDLK", "LIEN_KHOA", "DEN_NGAY", "ID"
        ];
    }

    private function expectedEquipmentColumns()
    {
        return [
            "STT", "TEN_TB", "KY_HIEU", "CONGTY_SX", "NUOC_SX",
            "NAM_SX", "NAM_SD", "MA_MAY", "SO_LUU_HANH", "HD_TU",
            "HD_DEN", "TU_NGAY", "DEN_NGAY", "ID"
        ];
    }

    private function expectedAdministrativeUnitsColumns()
    {
        return [
            "Tỉnh Thành Phố", "Mã TP", "Quận Huyện", "Mã QH", "Phường Xã",
            "Mã PX", "Cấp", "Tên Tiếng Anh"
        ];
    }

    private function expectedMedicalOrganizationColumns()
    {
        return [
            "STT", "Mã", "Tên", "Tuyến CMKT", "Hạng bệnh viện",
            "Địa chỉ"
        ];
    }

    private function importMedicineCatalog($data)
    {
        $data = $data->slice(1); // Bỏ qua dòng đầu tiên
        foreach ($data as $row) {
            // Kiểm tra các trường bắt buộc không được để trống
            if (empty($row[1]) || empty($row[5]) || empty($row[9]) || empty($row[17]) || empty($row[2]) || empty($row[3]) || empty($row[4]) || empty($row[6]) || empty($row[7]) || empty($row[8]) || empty($row[11]) || empty($row[12])) {
                continue; // Bỏ qua hàng nếu thiếu dữ liệu bắt buộc
            }

            try {
                // Thực hiện cập nhật hoặc tạo mới bản ghi trong `MedicineCatalog`
                MedicineCatalog::updateOrCreate(
                    [
                        'ma_thuoc' => $row[1],  // Mã thuốc
                        'ten_thuoc' => $row[3], // Tên thuốc
                        'ham_luong' => $row[5], // Hàm lượng
                        'so_dang_ky' => $row[9], // Số đăng ký
                        'don_gia_bh' => $row[12], // Đơn giá BHYT
                        'tt_thau' => $row[17], // Thông tin thầu
                        'tu_ngay' => $row[18], // Từ ngày
                    ],
                    [
                        'ten_hoat_chat' => $row[2],  // Tên hoạt chất
                        'don_vi_tinh' => $row[4],   // Đơn vị tính
                        'duong_dung' => $row[6],    // Đường dùng
                        'ma_duong_dung' => $row[7], // Mã đường dùng
                        'dang_bao_che' => $row[8],  // Dạng bào chế
                        'so_luong' => $row[10],     // Số lượng
                        'don_gia' => $row[11],      // Đơn giá
                        'quy_cach' => $row[13],     // Quy cách
                        'nha_sx' => $row[14],       // Nhà sản xuất
                        'nuoc_sx' => $row[15],      // Nước sản xuất
                        'nha_thau' => $row[16],     // Nhà thầu
                        'den_ngay' => $row[19],     // Đến ngày
                        'ma_cskcb' => $row[20],     // Mã cơ sở khám chữa bệnh
                        'loai_thuoc' => $row[21],   // Loại thuốc
                        'loai_thau' => $row[22],    // Loại thầu
                        'ht_thau' => $row[23],      // Hình thức thầu
                    ]
                );
            } catch (\Exception $e) {
                // Ghi lại lỗi nếu có
                Log::error('Error updating or creating MedicineCatalog record', [
                    'error' => $e->getMessage(),
                    'row' => $row // Ghi lại dữ liệu của hàng bị lỗi
                ]);
                continue; // Bỏ qua lỗi và tiếp tục với hàng tiếp theo
            }
        }
    }

    private function importMedicalSupplyCatalog($data)
    {
        $data = $data->slice(1); // Bỏ qua dòng đầu tiên
        foreach ($data as $row) {
            // Kiểm tra các trường bắt buộc không được để trống
            if (empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[15]) || empty($row[9]) || empty($row[10]) || empty($row[16]) || empty($row[11])) {
                continue; // Bỏ qua hàng nếu thiếu dữ liệu bắt buộc
            }

            try {
                // Thực hiện cập nhật hoặc tạo mới bản ghi trong `MedicalSupplyCatalog`
                MedicalSupplyCatalog::updateOrCreate(
                    [
                        'ma_vat_tu' => $row[1],  // Mã vật tư
                        'tt_thau' => $row[15],   // Thông tin thầu
                        'don_gia_bh' => $row[10],// Đơn giá BHYT
                        'tu_ngay' => $row[16],   // Từ ngày
                    ],
                    [
                        'nhom_vat_tu' => $row[2],    // Nhóm vật tư
                        'ten_vat_tu' => $row[3],     // Tên vật tư
                        'ma_hieu' => $row[4],        // Mã hiệu
                        'quy_cach' => $row[5],       // Quy cách
                        'hang_sx' => $row[6],        // Hãng sản xuất
                        'nuoc_sx' => $row[7],        // Nước sản xuất
                        'don_vi_tinh' => $row[8],    // Đơn vị tính
                        'don_gia' => $row[9],        // Đơn giá
                        'tyle_tt_bh' => $row[11],    // Tỷ lệ thanh toán BHYT
                        'so_luong' => $row[12],      // Số lượng
                        'dinh_muc' => empty($row[13]) ? null : $row[13],  // Định mức
                        'nha_thau' => $row[14],      // Nhà thầu
                        'den_ngay_hd' => $row[17],   // Đến ngày hợp đồng
                        'ma_cskcb' => $row[18],      // Mã cơ sở khám chữa bệnh
                        'loai_thau' => $row[19],     // Loại thầu
                        'ht_thau' => empty($row[20]) ? null : $row[20],   // Hình thức thầu
                        'den_ngay' => $row[21],      // Đến ngày
                    ]
                );
            } catch (\Exception $e) {
                // Ghi lại lỗi nếu có
                Log::error('Error updating or creating MedicalSupplyCatalog record', [
                    'error' => $e->getMessage(),
                    'row' => $row // Ghi lại dữ liệu của hàng bị lỗi
                ]);
                continue; // Bỏ qua lỗi và tiếp tục với hàng tiếp theo
            }
        }
    }

    private function importServiceCatalog($data)
    {
        $data = $data->slice(1); // Bỏ qua dòng đầu tiên
        foreach ($data as $row) {
            // Loại bỏ ký tự đặc biệt trong cột 'Tên dịch vụ'
            if (isset($row[2])) {
                $row[2] = preg_replace('/[^\p{L}\p{N}\s]/u', '', $row[2]);
            }

            // Kiểm tra các trường bắt buộc không được để trống
            if (empty($row[1]) || empty($row[2]) || empty($row[5]) || empty($row[7]) || empty($row[8])) {
                continue; // Bỏ qua hàng nếu thiếu dữ liệu bắt buộc
            }

            try {
                // Thực hiện cập nhật hoặc tạo mới bản ghi trong `ServiceCatalog`
                ServiceCatalog::updateOrCreate(
                    [
                        'ma_dich_vu' => $row[1],  // Mã dịch vụ
                        'ten_dich_vu' => $row[2], // Tên dịch vụ
                        'don_gia' => $row[5],     // Đơn giá
                        'quy_trinh' => $row[7],   // Quy trình
                        'tu_ngay' => $row[8],     // Từ ngày
                    ],
                    [
                        'cskcb_cgkt' => null,  // Cơ sở khám chữa bệnh có công nghệ kỹ thuật
                        'cskcb_cls' => null,   // Cơ sở khám chữa bệnh có cận lâm sàng
                        'den_ngay' => $row[9],    // Đến ngày
                    ]
                );
            } catch (\Exception $e) {
                // Ghi lại lỗi nếu có
                Log::error('Error updating or creating ServiceCatalog record', [
                    'error' => $e->getMessage(),
                    'row' => $row // Ghi lại dữ liệu của hàng bị lỗi
                ]);
                continue; // Bỏ qua lỗi và tiếp tục với hàng tiếp theo
            }
        }
    }

    private function importMedicalStaff($data)
    {
        $data = $data->slice(1); // Bỏ qua dòng đầu tiên

        foreach ($data as $row) {
            if (empty($row[2]) || empty($row[3]) || empty($row[4]) || empty($row[5]) || empty($row[6]) || empty($row[7]) || empty($row[9]) || empty($row[10]) || empty($row[11]) || empty($row[16]) || empty($row[17]) || empty($row[18]) || empty($row[22])) {
                continue;
            }

            // Chuyển đổi định dạng ngày NGAYCAP_CCHN về dạng text YYYYMMDD
            $ngaycap_cchn = $row[10];
            if (is_numeric($ngaycap_cchn)) {
                $ngaycap_cchn = Carbon::instance(Date::excelToDateTimeObject($ngaycap_cchn))->format('Ymd');
            } else {
                $ngaycap_cchn = Carbon::createFromFormat('m/d/Y H:i', $ngaycap_cchn)->format('Ymd');
            }

            try {
                MedicalStaff::updateOrCreate(
                    [
                        'ma_bhxh' => $row[4]
                    ],
                    [
                        'ma_loai_kcb' => $row[1],
                        'ma_khoa' => $row[2],
                        'ten_khoa' => $row[3],
                        'ho_ten' => $row[5],
                        'gioi_tinh' => $row[6],
                        'chucdanh_nn' => $row[7],
                        'vi_tri' => $row[8],
                        'macchn' => $row[9],
                        'ngaycap_cchn' => $ngaycap_cchn,
                        'noicap_cchn' => $row[11],
                        'phamvi_cm' => $row[12],
                        'phamvi_cmbs' => $row[13],
                        'dvkt_khac' => $row[14],
                        'vb_phancong' => $row[15],
                        'thoigian_dk' => $row[16],
                        'thoigian_ngay' => $row[17],
                        'thoigian_tuan' => $row[18],
                        'cskcb_khac' => $row[19],
                        'cskcb_cgkt' => $row[20],
                        'qd_cgkt' => $row[21],
                        'tu_ngay' => $row[22],
                        'den_ngay' => $row[23]
                    ]
                );
            } catch (\Exception $e) {
                Log::error('Error importing medical staff', [
                    'error' => $e->getMessage(), 
                    'row' => $row
                ]);
                continue; // Bỏ qua lỗi và tiếp tục với hàng tiếp theo
            }
        }
    }

    private function importDepartmentBedCatalog($data)
    {
        $data = $data->slice(1); // Bỏ qua dòng đầu tiên
        foreach ($data as $row) {
            // Kiểm tra các trường bắt buộc không được để trống
            if (empty($row[2]) || empty($row[3])) {
                continue; // Bỏ qua hàng nếu thiếu dữ liệu bắt buộc
            }

            try {
                // Thực hiện cập nhật hoặc tạo mới bản ghi trong `DepartmentBedCatalog`
                DepartmentBedCatalog::updateOrCreate(
                    [
                        'ma_khoa' => $row[2],  // Mã khoa
                    ],
                    [
                        'ma_loai_kcb' => $row[1],  // Mã loại KCB
                        'ten_khoa' => $row[3],     // Tên khoa
                        'ban_kham' => $row[4],     // Bàn khám
                        'giuong_pd' => $row[5],    // Giường phân điều
                        'giuong_2015' => $row[6],  // Giường tiêu chuẩn năm 2015
                        'giuong_tk' => $row[7],    // Giường tính kế
                        'giuong_hstc' => $row[8],  // Giường hồi sức tích cực
                        'giuong_hscc' => $row[9],  // Giường hồi sức cấp cứu
                        'ldlk' => $row[10],        // Liên đơn lẻ khoa
                        'lien_khoa' => $row[11],   // Liên khoa
                        'den_ngay' => $row[12],    // Đến ngày
                    ]
                );
            } catch (\Exception $e) {
                // Ghi lại lỗi nếu có
                Log::error('Error updating or creating DepartmentBedCatalog record', [
                    'error' => $e->getMessage(),
                    'row' => $row // Ghi lại dữ liệu của hàng bị lỗi
                ]);
                continue; // Bỏ qua lỗi và tiếp tục với hàng tiếp theo
            }
        }
    }

    private function importEquipmentCatalog($data)
    {
        $data = $data->slice(1); // Bỏ qua dòng đầu tiên
        foreach ($data as $row) {
            // Kiểm tra các trường bắt buộc không được để trống
            if (empty($row[1]) || empty($row[2]) || empty($row[7]) || empty($row[3]) || empty($row[4]) || empty($row[5]) || empty($row[6]) || empty($row[8])) {
                continue; // Bỏ qua hàng nếu thiếu dữ liệu bắt buộc
            }

            try {
                // Thực hiện cập nhật hoặc tạo mới bản ghi trong `EquipmentCatalog`
                EquipmentCatalog::updateOrCreate(
                    [
                        'ma_may' => $row[7],  // Mã máy
                    ],
                    [
                        'ten_tb' => $row[1],        // Tên thiết bị
                        'ky_hieu' => $row[2],       // Ký hiệu thiết bị
                        'congty_sx' => $row[3],     // Công ty sản xuất
                        'nuoc_sx' => $row[4],       // Nước sản xuất
                        'nam_sx' => $row[5],        // Năm sản xuất
                        'nam_sd' => $row[6],        // Năm sử dụng
                        'so_luu_hanh' => $row[8],   // Số lưu hành
                        'hd_tu' => $row[9],         // Hợp đồng từ
                        'hd_den' => $row[10],       // Hợp đồng đến
                        'tu_ngay' => $row[11],      // Từ ngày
                        'den_ngay' => $row[12],     // Đến ngày
                    ]
                );
            } catch (\Exception $e) {
                // Ghi lại lỗi nếu có
                Log::error('Error updating or creating EquipmentCatalog record', [
                    'error' => $e->getMessage(),
                    'row' => $row // Ghi lại dữ liệu của hàng bị lỗi
                ]);
                continue; // Bỏ qua lỗi và tiếp tục với hàng tiếp theo
            }
        }
    }

    private function importAdministrativeUnits($data)
    {
        // Deactivate all existing active records
        AdministrativeUnit::where('is_active', true)->update(['is_active' => false]);

        $data = $data->slice(1); // Bỏ qua dòng đầu tiên
        foreach ($data as $row) {
            // Kiểm tra các trường bắt buộc không được để trống
            if (empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[4]) || empty($row[5])) {
                continue; // Bỏ qua hàng nếu thiếu dữ liệu bắt buộc
            }

            try {
                // Thực hiện cập nhật hoặc tạo mới bản ghi trong `AdministrativeUnit`
                AdministrativeUnit::updateOrCreate(
                    [
                        'commune_code' => $row[5],  // Mã phường/xã
                    ],
                    [
                        'province_code' => $row[1],   // Mã tỉnh/thành phố
                        'province_name' => $row[0],   // Tên tỉnh/thành phố
                        'district_code' => $row[3],   // Mã quận/huyện
                        'district_name' => $row[2],   // Tên quận/huyện
                        'commune_name' => $row[4],    // Tên phường/xã
                        'is_active' => true,          // Kích hoạt bản ghi
                    ]
                );
            } catch (\Exception $e) {
                // Ghi lại lỗi nếu có
                Log::error('Error updating or creating AdministrativeUnit record', [
                    'error' => $e->getMessage(),
                    'row' => $row // Ghi lại dữ liệu của hàng bị lỗi
                ]);
                continue; // Bỏ qua lỗi và tiếp tục với hàng tiếp theo
            }
        }
    }

    private function importMedicalOrganization($data)
    {
        // Deactivate all existing active records
        MedicalOrganization::where('is_active', true)->update(['is_active' => false]);

        $data = $data->slice(1); // Bỏ qua dòng đầu tiên
        foreach ($data as $row) {
            // Kiểm tra các trường bắt buộc không được để trống
            if (empty($row[0]) || empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[4]) || empty($row[5])) {
                continue; // Bỏ qua hàng nếu thiếu dữ liệu bắt buộc
            }

            try {
                // Thực hiện cập nhật hoặc tạo mới bản ghi trong `MedicalOrganization`
                MedicalOrganization::updateOrCreate(
                    [
                        'ma_cskcb' => $row[1],  // Mã cơ sở khám chữa bệnh
                    ],
                    [
                        'ten_cskcb' => $row[2],        // Tên cơ sở khám chữa bệnh
                        'tuyen_cmkt' => $row[3],       // Tuyến chuyên môn kỹ thuật
                        'hang_benh_vien' => $row[4],   // Hạng bệnh viện
                        'dia_chi_cskcb' => $row[5],    // Địa chỉ cơ sở khám chữa bệnh
                        'is_active' => true,           // Kích hoạt bản ghi
                    ]
                );
            } catch (\Exception $e) {
                // Ghi lại lỗi nếu có
                Log::error('Error updating or creating MedicalOrganization record', [
                    'error' => $e->getMessage(),
                    'row' => $row // Ghi lại dữ liệu của hàng bị lỗi
                ]);
                continue; // Bỏ qua lỗi và tiếp tục với hàng tiếp theo
            }
        }
    }

}