<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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

class ImportCatalogBHXHFromFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'importCatalogBHXH:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data from files in a specific directory';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        do {
            try {

                $this->info($this->description);
                $this->importFilesFromDisk('CatalogBHXH');

                sleep(10);
            } catch (\Exception $e) {
                $this->info($e->getMessage());
                Log::error('ImportCatalogBHXHFromFiles - Error occurred: ' . $e->getMessage());
            }
        } while (true);

        $this->info($this->description);
    }

    private function importFilesFromDisk($disk)
    {
        // Sử dụng disk từ cấu hình
        $files = Storage::disk($disk)->files();

        foreach ($files as $file) {
            // Kiểm tra cấu trúc file
            $extension = pathinfo($file, PATHINFO_EXTENSION);

            if ($extension == 'xls' || $extension == 'xlsx') {
                $this->importExcelFile($disk, $file);
                // Xóa file sau khi import thành công
                Storage::disk($disk)->delete($file);
            } else {
                $this->error('Unsupported file type: ' . $file);
            }
        }
    }

    /**
     * Import Excel file.
     *
     * @param string $disk
     * @param string $file
     */
    private function importExcelFile($disk, $file)
    {
        $path = Storage::disk($disk)->path($file);
        $data = Excel::toCollection(null, $path)->first();

        if ($data->isEmpty()) {
            $this->error('No data found in file: ' . $file);
            return;
        }

        $expectedMedicineColumns = [
            "STT", "MA_THUOC", "TEN_HOAT_CHAT", "TEN_THUOC", "DON_VI_TINH",
            "HAM_LUONG", "DUONG_DUNG", "MA_DUONG_DUNG", "DANG_BAO_CHE", "SO_DANG_KY",
            "SO_LUONG", "DON_GIA", "DON_GIA_BH", "QUY_CACH", "NHA_SX", "NUOC_SX",
            "NHA_THAU", "TT_THAU", "TU_NGAY", "DEN_NGAY", "MA_CSKCB", "LOAI_THUOC",
            "LOAI_THAU", "HT_THAU", "MA_DVKT", "TCCL", "BO_PHAN_VT", "TEN_KHOA_HOC",
            "NGUON_GOC", "PP_CHEBIEN", "MA_DL_NHAP", "MA_DL_CB", "TLHH_CB", "TLHH_BQ",
            "DENNGAY", "ID"
        ];

        $expectedSupplyColumns = [
            "STT", "MA_VAT_TU", "NHOM_VAT_TU", "TEN_VAT_TU", "MA_HIEU",
            "QUY_CACH", "HANG_SX", "NUOC_SX", "DON_VI_TINH", "DON_GIA",
            "DON_GIA_BH", "TYLE_TT_BH", "SO_LUONG", "DINH_MUC", "NHA_THAU",
            "TT_THAU", "TU_NGAY", "DEN_NGAY_HD", "MA_CSKCB", "LOAI_THAU",
            "HT_THAU", "DENNGAY", "ID"
        ];

        $expectedServiceColumns = [
            "STT", "MA_DICH_VU", "TEN_DICH_VU", "DON_GIA", "QUY_TRINH",
            "CSKCB_CGKT", "CSKCB_CLS", "TUNGAY", "DENNGAY", "ID"
        ];

        $expectedStaffColumns = [
            "STT", "MA_LOAI_KCB", "MA_KHOA", "TEN_KHOA", "MA_BHXH",
            "HO_TEN", "GIOI_TINH", "CHUCDANH_NN", "VI_TRI", "MACCHN",
            "NGAYCAP_CCHN", "NOICAP_CCHN", "PHAMVI_CM", "PHAMVI_CMBS", "DVKT_KHAC",
            "VB_PHANCONG", "THOIGIAN_DK", "THOIGIAN_NGAY", "THOIGIAN_TUAN", "CSKCB_KHAC",
            "CSKCB_CGKT", "QD_CGKT", "TU_NGAY", "DEN_NGAY", "ID"
        ];

        $expectedDepartmentColumns = [
            "STT", "MA_LOAI_KCB", "MA_KHOA", "TEN_KHOA", "BAN_KHAM",
            "GIUONG_PD", "GIUONG_2015", "GIUONG_TK", "GIUONG_HSTC", "GIUONG_HSCC",
            "LDLK", "LIEN_KHOA", "DEN_NGAY", "ID"
        ];

        $expectedEquipmentColumns = [
            "STT", "TEN_TB", "KY_HIEU", "CONGTY_SX", "NUOC_SX",
            "NAM_SX", "NAM_SD", "MA_MAY", "SO_LUU_HANH", "HD_TU",
            "HD_DEN", "TU_NGAY", "DEN_NGAY", "ID"
        ];

        $expectedAdministrativeUnitsColumns = [
            "Tỉnh Thành Phố", "Mã TP", "Quận Huyện", "Mã QH", "Phường Xã",
            "Mã PX", "Cấp", "Tên Tiếng Anh"
        ];

        $expectedMedicalOrganizationColumns = [
            "STT", "Mã", "Tên", "Tuyến CMKT", "Hạng bệnh viện",
            "Địa chỉ"
        ];

        $firstRow = $data->first()->values()->toArray();

        switch (true) {
            case $firstRow === $expectedMedicineColumns:
                $data = $data->slice(1); // Bỏ qua dòng đầu tiên
                foreach ($data as $row) {
                    if (empty($row[1]) || empty($row[5]) || empty($row[9]) || empty($row[17]) || empty($row[2]) || empty($row[3]) || empty($row[4]) || empty($row[6]) || empty($row[7]) || empty($row[8]) || empty($row[11]) || empty($row[12])) {
                        continue;
                    }
                    try {
                        MedicineCatalog::updateOrCreate(
                            [
                                'ma_thuoc' => $row[1],
                                'ten_thuoc' => $row[3],
                                'ham_luong' => $row[5],
                                'so_dang_ky' => $row[9],
                                'tt_thau' => $row[17]
                            ],
                            [
                                'ten_hoat_chat' => $row[2],
                                'don_vi_tinh' => $row[4],
                                'duong_dung' => $row[6],
                                'ma_duong_dung' => $row[7],
                                'dang_bao_che' => $row[8],
                                'so_luong' => $row[10],
                                'don_gia' => $row[11],
                                'don_gia_bh' => $row[12],
                                'quy_cach' => $row[13],
                                'nha_sx' => $row[14],
                                'nuoc_sx' => $row[15],
                                'nha_thau' => $row[16],
                                'tu_ngay' => $row[18],
                                'den_ngay' => $row[19],
                                'ma_cskcb' => $row[20],
                                'loai_thuoc' => $row[21],
                                'loai_thau' => $row[22],
                                'ht_thau' => $row[23]
                            ]
                        );                        
                    } catch (Exception $e) {
                        \Log::error('Error updating or creating MedicineCatalog record', [
                            'error' => $e->getMessage(),
                            'row' => $row // Ghi lại dữ liệu của hàng bị lỗi nếu cần
                        ]);                        
                    }
                }
                
                break;

            case $firstRow === $expectedSupplyColumns:
                $data = $data->slice(1); // Bỏ qua dòng đầu tiên
                foreach ($data as $row) {
                    if (empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[15])) {
                        continue;
                    }
                    try {
                        MedicalSupplyCatalog::updateOrCreate(
                            [
                                'ma_vat_tu' => $row[1],
                                'tt_thau' => $row[15]
                            ],
                            [
                                'nhom_vat_tu' => $row[2],
                                'ten_vat_tu' => $row[3],
                                'ma_hieu' => $row[4],
                                'quy_cach' => $row[5],
                                'hang_sx' => $row[6],
                                'nuoc_sx' => $row[7],
                                'don_vi_tinh' => $row[8],
                                'don_gia' => $row[9],
                                'don_gia_bh' => $row[10],
                                'tyle_tt_bh' => $row[11],
                                'so_luong' => $row[12],
                                'dinh_muc' => $row[13],
                                'nha_thau' => $row[14],
                                'tu_ngay' => $row[16],
                                'den_ngay_hd' => $row[17],
                                'ma_cskcb' => $row[18],
                                'loai_thau' => $row[19],
                                'ht_thau' => $row[20],
                                'den_ngay' => $row[21]
                            ]
                        );                        
                    } catch (Exception $e) {
                        \Log::error('Error updating or creating MedicalSupplyCatalog record', [
                            'error' => $e->getMessage(),
                            'row' => $row // Ghi lại dữ liệu của hàng bị lỗi nếu cần
                        ]);   
                    }
                }
                
                break;
            case $firstRow === $expectedServiceColumns:
                $data = $data->slice(1); // Bỏ qua dòng đầu tiên
                foreach ($data as $row) {
                    if (empty($row[1]) || empty($row[2]) || empty($row[3]) || empty($row[4]) || empty($row[7])) {
                        continue;
                    }
                    try {
                        ServiceCatalog::updateOrCreate(
                            [
                                'ma_dich_vu' => $row[1],
                                'ten_dich_vu' => $row[2],
                                'don_gia' => $row[3],
                                'quy_trinh' => $row[4],
                                'tu_ngay' => $row[7],
                            ],
                            [
                                'cskcb_cgkt' => $row[5],
                                'cskcb_cls' => $row[6],
                                'den_ngay' => $row[8]
                            ]
                        );                        
                    } catch (Exception $e) {
                        \Log::error('Error updating or creating ServiceCatalog record', [
                            'error' => $e->getMessage(),
                            'row' => $row // Ghi lại dữ liệu của hàng bị lỗi nếu cần
                        ]);  
                    }
                }
                
                break;

            case $firstRow === $expectedStaffColumns:
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
                    } catch (Exception $e) {
                        \Log::error('Error updating or creating MedicalStaff record', [
                            'error' => $e->getMessage(),
                            'row' => $row // Ghi lại dữ liệu của hàng bị lỗi nếu cần
                        ]);  
                    }

                }
                
                break;

            case $firstRow === $expectedDepartmentColumns:
                $data = $data->slice(1); // Bỏ qua dòng đầu tiên
                foreach ($data as $row) {
                    if (empty($row[2]) || empty($row[3])) {
                        continue;
                    }
                    try {
                        DepartmentBedCatalog::updateOrCreate(
                            [
                                'ma_khoa' => $row[2]
                            ],
                            [
                                'ma_loai_kcb' => $row[1],
                                'ten_khoa' => $row[3],
                                'ban_kham' => $row[4],
                                'giuong_pd' => $row[5],
                                'giuong_2015' => $row[6],
                                'giuong_tk' => $row[7],
                                'giuong_hstc' => $row[8],
                                'giuong_hscc' => $row[9],
                                'ldlk' => $row[10],
                                'lien_khoa' => $row[11],
                                'den_ngay' => $row[12]
                            ]
                        );                        
                    } catch (Exception $e) {
                        \Log::error('Error updating or creating DepartmentBedCatalog record', [
                            'error' => $e->getMessage(),
                            'row' => $row // Ghi lại dữ liệu của hàng bị lỗi nếu cần
                        ]);  
                    }
                }
                
                break;

            case $firstRow === $expectedEquipmentColumns:
                $data = $data->slice(1); // Bỏ qua dòng đầu tiên
                foreach ($data as $row) {
                    if (empty($row[1]) || empty($row[2]) || empty($row[8])
                        || empty($row[3]) || empty($row[4]) || empty($row[5])
                        || empty($row[6]) || empty($row[7])
                    ) {
                        continue;
                    }
                    try {
                        EquipmentCatalog::updateOrCreate(
                            [
                                'ma_may' => $row[7]
                            ],
                            [
                                'ten_tb' => $row[1],
                                'ky_hieu' => $row[2],
                                'congty_sx' => $row[3],
                                'nuoc_sx' => $row[4],
                                'nam_sx' => $row[5],
                                'nam_sd' => $row[6],
                                'so_luu_hanh' => $row[8],
                                'hd_tu' => $row[9],
                                'hd_den' => $row[10],
                                'tu_ngay' => $row[11],
                                'den_ngay' => $row[12]
                            ]
                        );                        
                    } catch (Exception $e) {
                        \Log::error('Error updating or creating EquipmentCatalog record', [
                            'error' => $e->getMessage(),
                            'row' => $row // Ghi lại dữ liệu của hàng bị lỗi nếu cần
                        ]); 
                    }
                }
                
                break;

            case $firstRow === $expectedAdministrativeUnitsColumns:
                
                // Deactivate all existing active records
                AdministrativeUnit::where('is_active', true)->update(['is_active' => false]);

                $data = $data->slice(1); // Skip the first row
                foreach ($data as $row) {
                    // Continue if any of the required fields are empty
                    if (empty($row[0]) || empty($row[1]) || empty($row[2])
                        || empty($row[3]) || empty($row[4]) || empty($row[5])
                    ) {
                        continue;
                    }

                    try {
                        // Update or create the record and set it as active
                        AdministrativeUnit::updateOrCreate(
                            [
                                'commune_code' => $row[5]
                            ],
                            [
                                'province_code' => $row[1],
                                'province_name' => $row[0],
                                'district_code' => $row[3],
                                'district_name' => $row[2],
                                'commune_name' => $row[4],
                                'is_active' => true,
                            ]
                        );                        
                    } catch (Exception $e) {
                        \Log::error('Error updating or creating AdministrativeUnit record', [
                            'error' => $e->getMessage(),
                            'row' => $row // Ghi lại dữ liệu của hàng bị lỗi nếu cần
                        ]); 
                    }
                }

                // Delete the processed file from storage
                
                break;

            case $firstRow === $expectedMedicalOrganizationColumns:
                
                // Deactivate all existing active records
                MedicalOrganization::where('is_active', true)->update(['is_active' => false]);

                $data = $data->slice(1); // Skip the first row
                foreach ($data as $row) {
                    // Continue if any of the required fields are empty
                    if (empty($row[0]) || empty($row[1]) || empty($row[2])
                        || empty($row[3]) || empty($row[4]) || empty($row[5])
                    ) {
                        continue;
                    }
                    try {
                        // Update or create the record and set it as active
                        MedicalOrganization::updateOrCreate(
                            [
                                'ma_cskcb' => $row[1]
                            ],
                            [
                                'ten_cskcb' => $row[2],
                                'dia_chi_cskcb' => $row[5],
                                'is_active' => true,
                            ]
                        );                        
                    } catch (Exception $e) {
                        \Log::error('Error updating or creating MedicalOrganization record', [
                            'error' => $e->getMessage(),
                            'row' => $row // Ghi lại dữ liệu của hàng bị lỗi nếu cần
                        ]); 
                    }
                }

                // Delete the processed file from storage
                
                break;

            default:
                $this->error('Unrecognized file structure: ' . $file);
                \Log::error('Unrecognized file structure: ' . $file);
                break;
        }
    }
}
