<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group">
            <label>Thông tin chung - XML1</label>
        </div>
        
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Mã LK</label>
            </div>
            <div class="col-sm-9">
                <input class="form-control" type="text" value="{{ $xml1->ma_lk }}" disabled="">    
            </div>
            
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>STT</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ $xml1->stt }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Mã BN</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ $xml1->ma_bn }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Họ tên</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ $xml1->ho_ten }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Ngày sinh</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ strtodate($xml1->ngay_sinh) }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Giới tính</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ config('__tech.gioi_tinh')[$xml1->gioi_tinh] }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Địa chỉ</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ $xml1->dia_chi }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Mã thẻ</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ $xml1->ma_the }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Mã ĐKBĐ</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ $xml1->ma_dkbd }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>GT từ</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="<?php foreach (explode(';',$xml1->gt_the_tu) as $key => $value): ?>{{strtodate($value)}};<?php endforeach ?>" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>GT đến</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="<?php foreach (explode(';',$xml1->gt_the_den) as $key => $value): ?>{{strtodate($value)}};<?php endforeach ?>" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Miễn CCT</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ $xml1->mien_cung_ct }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Mã bệnh</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ $xml1->ma_benh }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Tên bệnh</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ $xml1->ten_benh }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Bệnh khác</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ $xml1->ma_benhkhac }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Cân nặng</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ $xml1->can_nang }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Lý do VV</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ config('__tech.ly_do_vvien')[$xml1->ma_lydo_vvien] }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Nơi chuyển</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ $xml1->ma_noi_chuyen }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Tai nạn</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ $xml1->ma_tai_nan }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Ngày vào</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ strtodate($xml1->ngay_vao) }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Ngày ra</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ strtodate($xml1->ngay_ra) }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Số ngày</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ $xml1->so_ngay_dtri }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Kết quả</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ config('__tech.ket_qua_dtri')[$xml1->ket_qua_dtri] }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Tình trạng</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ config('__tech.tinh_trang_rv')[$xml1->tinh_trang_rv] }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Ngày T.Toán</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ strtodate($xml1->ngay_ttoan) }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Tổng tiền</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ number_format($xml1->t_tongchi) }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Tiền thuốc</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ number_format($xml1->t_thuoc) }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Tiền VTYT</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ number_format($xml1->t_vtyt) }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Tự trả</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ number_format($xml1->t_bntt) }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Cùng CT</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ number_format($xml1->t_bncct) }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>BH T.Toán</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ number_format($xml1->t_bhtt) }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Nguồn khác</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ number_format($xml1->t_nguonkhac) }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Ngoài DS</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ number_format($xml1->t_ngoaids) }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Năm QT</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ $xml1->nam_qt }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Tháng QT</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ $xml1->trang_qt }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Loại KCB</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ config('__tech.loai_kcb')[$xml1->ma_loai_kcb] }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Khoa ĐT</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ $xml1->department->TEN_KHOA }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Mã CSKCB</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ $xml1->ma_cskcb }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>Mã K.Vực</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ $xml1->ma_khuvuc }}" disabled="">    
            </div>
        </div>
        <div class="col-sm-3">
            <div class="col-sm-3">
                <label>PTTT Q.Tế</label>    
            </div>
            <div class="col-sm-9">
                <input class="form-control type="text" value="{{ $xml1->ma_pttt_qt }}" disabled="">    
            </div>
        </div>

    </div>
</div>