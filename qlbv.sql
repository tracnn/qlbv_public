-- phpMyAdmin SQL Dump
-- version 5.1.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 27, 2024 at 05:19 AM
-- Server version: 10.4.34-MariaDB
-- PHP Version: 7.4.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `qlbv`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `log_name` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `causer_id` int(11) DEFAULT NULL,
  `causer_type` varchar(255) DEFAULT NULL,
  `properties` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cat_cond_pharmas`
--

CREATE TABLE `cat_cond_pharmas` (
  `id` int(10) NOT NULL,
  `pharma_code` varchar(255) NOT NULL,
  `pharma_description` varchar(1024) DEFAULT NULL,
  `pharma_status` int(10) DEFAULT NULL,
  `pharma_val` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `cat_cond_pharmas`
--

INSERT INTO `cat_cond_pharmas` (`id`, `pharma_code`, `pharma_description`, `pharma_status`, `pharma_val`, `created_at`, `updated_at`) VALUES
(1, '40.64', 'BHYT  thanh toán: thoái hoá khớp hông hoặc gối', 1, 'M17.0; M17.1; M17.2; M17.3; M17.4; M17.5; M17.6; M17.7; M17.8; M17.9; M16.0; M16.1; M16.2; M16.3; M16.4; M16.5 ; M16.6; M16.7; M16.8; M16.9; M17; M16', NULL, NULL),
(2, '40.67', 'BHYT  thanh toán: phù nề sau phẫu thuật, chấn thương, bỏng', NULL, NULL, NULL, '2019-06-28 13:05:11'),
(3, '40.156', 'BHYT  thanh toán: viêm tai giữa hoặc viêm phổi cộng đồng', 1, 'H65; H65.0; H65.1; H65.2; H65.3; H65.4; H65.5; H65.6; H65.7; H65.8; H65.9; H66; H66.0; H66.1; H66.2; H66.3; H66.4; H66.5; H66.6; H66.7; H66.8; H66.9; H67; H67.0; H67.1; H67.2; H67.3; H67.4; H67.5; H67.6; H67.7; H67.8; H67.9', NULL, NULL),
(4, '40.336', 'BHYT  thanh toán:dự phòng cơn đau nửa đầu trong trường hợp các biện pháp điều trị khác không có hiệu quả hoặc kém dung nạp', NULL, NULL, NULL, NULL),
(5, '40.455', 'BHYT  thanh toán: Nồng độ Albumin máu ≤ 2.5g/dl hoặc sốc  hoặc  hội chứng suy hô hấp tiến triển; thanh toán 70%', NULL, NULL, NULL, NULL),
(6, '40.481', 'BHYT  thanh toán: điều trị triệu chứng ở người bệnh đau thắt ngực ổn định không được kiểm soát đầy đủ hoặc người bệnh không dung nạp với các liệu pháp khác', NULL, NULL, NULL, NULL),
(7, '40.565', '', NULL, NULL, NULL, NULL),
(8, '40.572', 'BHYT  thanh toán : xuất huyết màng não do phình mạch não hoặc do chấn thương', NULL, NULL, NULL, NULL),
(9, '40.637', 'BHYT  thanh toán: tiêm tĩnh mạch trong chụp chiếu gan', NULL, NULL, NULL, NULL),
(10, '40.677', 'BHYT  thanh toán: theo chỉ định trong tờ HDSD thuốc kèm theo Hồ sơ đăng ký thuốc đã được cấp phép và chỉ định dự phòng loét dạ dày tá tràng, xuất huyết tiêu hoá tại dạ dày tá tràng do stress ở bệnh nhân hồi sức tích cực', NULL, NULL, NULL, NULL),
(11, '40.678', 'BHYT  thanh toán: theo chỉ định trong tờ HDSD thuốc kèm theo Hồ sơ đăng ký thuốc đã được cấp phép và chỉ định dự phòng loét dạ dày tá tràng, xuất huyết tiêu hoá tại dạ dày tá tràng do stress ở bệnh nhân hồi sức tích cực', NULL, NULL, NULL, NULL),
(12, '40.679', 'BHYT  thanh toán: theo chỉ định trong tờ HDSD thuốc kèm theo Hồ sơ đăng ký thuốc đã được cấp phép và chỉ định dự phòng loét dạ dày tá tràng, xuất huyết tiêu hoá tại dạ dày tá tràng do stress ở bệnh nhân hồi sức tích cực', NULL, NULL, NULL, NULL),
(13, '40.680', 'BHYT  thanh toán: theo chỉ định trong tờ HDSD thuốc kèm theo Hồ sơ đăng ký thuốc đã được cấp phép và chỉ định dự phòng loét dạ dày tá tràng, xuất huyết tiêu hoá tại dạ dày tá tràng do stress ở bệnh nhân hồi sức tích cực', NULL, NULL, NULL, NULL),
(14, '40.765', 'BHYT  không thanh toán:trường hợp tiêm trong dịch kính, tiêm nội nhãn', NULL, NULL, NULL, NULL),
(15, '40.842', 'BHYT  thanh toán: co cứng cơ sau đột quỵ', NULL, NULL, NULL, NULL),
(16, '40.940', 'BHYT  thanh toán: rối loạn cảm giác do bệnh viêm đa dây thần kinh đái tháo đường', NULL, NULL, NULL, NULL),
(17, '40.561', 'BHYT  thanh toán: 50% trong các trường hợp', NULL, NULL, NULL, NULL),
(18, '40.574', 'BHYT  thanh toán: 50% trong các trường hợp', NULL, NULL, NULL, NULL),
(19, '40.1043', 'BHYT  thanh toán: thiếu máu hồng cầu khổng lồ, bệnh lý thần kinh ngoại biên do thiếu vitamin B12', NULL, NULL, NULL, NULL),
(20, '40.576', 'BHYT  thanh toán: giật rung cơ có nguồn gốc vỏ não', NULL, NULL, NULL, NULL),
(21, '40.580', 'BHYT  thanh toán: triệu chứng thần kinh của sa sút trí tuệ do nguyên nhân mạch', NULL, NULL, NULL, NULL),
(22, '40.1013', 'BHYT  thanh toán:thanh toán 50% trong trường hợp bệnh nặng không nuôi dưỡng được bằng đường tiêu hoá hoặc qua ống xông mà phải nuôi dưỡng đường tĩnh mạch trong: hồi sức, cấp cứu, ung thư, bệnh đường tiêu hoá, suy dinh dưỡng nặng', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cat_cond_services`
--

CREATE TABLE `cat_cond_services` (
  `id` int(10) NOT NULL,
  `service_code` varchar(255) NOT NULL,
  `cond_status` int(4) DEFAULT NULL,
  `cond_des` varchar(1024) DEFAULT NULL,
  `cond_val` varchar(255) DEFAULT NULL,
  `limit_day_status` int(4) DEFAULT NULL,
  `limit_day_des` varchar(1024) DEFAULT NULL,
  `limit_day_val` int(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `cat_cond_services`
--

INSERT INTO `cat_cond_services` (`id`, `service_code`, `cond_status`, `cond_des`, `cond_val`, `limit_day_status`, `limit_day_des`, `limit_day_val`, `created_at`, `updated_at`) VALUES
(1, '23.0042.1482', 1, '- Chỉ định đối với những trường hợp: Hoạt độ CK (CPK) toàn phần tăng: Bệnh tai biến mạch não cấp; Nhồi máu cơ tim; Chấn thương não, đụng giập cơ; Sau phẫu thuật tim; Viêm da và cơ; Viêm cơ, Tiêu cơ vân; Nhồi máu phổi \r\n- Hoạt độ CK (CPK) toàn phần giảm: Bệnh addison; Giảm khối lượng cơ; Bệnh lý gan; Giảm tiết của thùy trước tuyến yên', 'J01; J02;', NULL, NULL, NULL, NULL, '2019-06-28 09:22:36'),
(2, '23.0043.1478', 1, 'Chỉ định với những trường hợp: Nhồi máu cơ tim cấp', 'I20; I21', NULL, NULL, NULL, NULL, '2019-06-28 09:10:31'),
(3, '23.0003.1494', 1, 'Chỉ định đối với những trường hợp: Cơn đau quặn thận, thận ứ nước, suy thận không xác định được nguồn gốc, viêm khớp, đau khớp, Gout, theo dõi các bệnh máu, thiếu máu do tan máu, bệnh nhân nghiện rượu, bệnh nhân điều trị hóa trị liệu, xạ trị, nhiễm độc thai nghén, nhiễm độc chì, thủy ngân…). Ngoại trú: giảm trừ với những trường hợp khoảng cách dưới 90 ngày với các bệnh lý mãn tính', NULL, NULL, NULL, NULL, NULL, '2019-06-28 12:49:39'),
(4, '22.0014.1242', 1, 'Chỉ đinh đối với những trường hợp: Sàng lọc bất thường đông cầm máu, đông máu rải rác trong lòng mạch, tiêu sợi huyết tiên phát; BN thực hiện các PT nặng như mổ tim, ghép tạng, cắt ruột, cắt lách, viêm tụy cấp, thai chết lưu, suy gan, hôn mê gan; nhiễm khuẩn nặng (BN thuộc khoa Hồi sức cấp cứu).', NULL, NULL, NULL, NULL, NULL, NULL),
(5, '23.0083.1523', 1, 'Thực hiện tối thiểu sau mỗi 3 tháng để đánh giá kết quả điều trị bệnh đái tháo đường.', NULL, NULL, NULL, NULL, NULL, NULL),
(6, '23.0041.1506', 1, 'Chỉ định đối với những trường hợp: vàng da tắc mật, RLCH lipid, ĐTĐ, THA, viêm thận, HCTH, Nhược giáp, cường giáp, Cushing, thiếu máu, vữa xơ động mạch. Ngoại trú: giảm trừ với những trường hợp khoảng cách dưới 90 ngày với các bệnh lý mãn tính', NULL, NULL, NULL, NULL, NULL, NULL),
(7, '23.0158.1506', 1, 'Được chỉ định đối với các trường hợp: THA, ĐTĐ, Viêm tụy cấp, xơ gan rượu, RL Lipoprotein máu, Bện thận, Suy giáp, cường giáp, Nhồi máu cơ tim, nhồi máu não, Gout, COPD.  Ngoại trú: giảm trừ với những trường hợp khoảng cách dưới 90 ngày với các bệnh lý mãn tính', 'J01;J02;J03', NULL, NULL, NULL, NULL, NULL),
(8, '23.0112.1506', 1, 'Được chỉ định đối với các trường hợp: Xơ vữa động mạch, bệnh tim mạch\r\n Ngoại trú: giảm trừ với những trường hợp khoảng cách dưới 90 ngày với các bệnh lý mãn tính', NULL, NULL, NULL, NULL, NULL, NULL),
(9, '23.0084.1506', 1, 'Được chỉ định đối với các trường hợp: Xơ vữa động mạch, bệnh tim mạch\r\n Ngoại trú: giảm trừ với những trường hợp khoảng cách dưới 90 ngày với các bệnh lý mãn tính', NULL, NULL, NULL, NULL, NULL, NULL),
(10, '23.0028.1466', 1, 'Được chỉ định đối với trường hợp: theo dõi suy tim (mã Bệnh suy tim) trên bệnh nhân có suy thận (mã Bệnh suy thận)', NULL, NULL, NULL, NULL, NULL, NULL),
(11, '23.0058.1487', 1, 'Giảm trừ điện giải đồ khi làm cùng thời điểm với khí máu', NULL, NULL, NULL, NULL, NULL, NULL),
(12, '24.0117.1646', 1, 'Được chỉ định đối với các trường hợp có bệnh lý gan. Không được chỉ định trong các trường hợp trước phẫu thuật, bệnh nhân đã được chẩn đoán xác định viêm gan B, không có bệnh lý gan,', NULL, NULL, NULL, NULL, NULL, NULL),
(13, '24.0144.1621', 1, 'Chỉ định HCV Ab test nhanh tiền phẫu, không có bệnh lý gan là không được thanh toán', NULL, NULL, NULL, NULL, NULL, NULL),
(14, '23.0130.1549', 1, '1. Chẩn đoán và theo dõi tình trạng nhiễm trùng nặng khi có một trong những dấu hiệu sau đây:\r\n– Điểm suy đa tạng (SOFA) > 2;\r\n– Nghi ngờ có ổ nhiễm trùng và có 2 trong 3 tiêu chuẩn: Nhịp thở> 22 lần/phút; Huyết áp tâm thu < 100 mmHg; Glassgow < 13 điểm.\r\n2. Đối với trẻ em:\r\n– Chẩn đoán và theo dõi các trường hợp nhiễm trùng huyết;\r\n– Theo dõi và tiên lượng suy đa tạng khi có rối loạn chức năng từ 2 cơ quan trở lên.', NULL, NULL, NULL, NULL, NULL, NULL),
(15, '21.0040.1777', 1, 'Chỉ định với những trường hợp: Bệnh động kinh, nghi ngờ tổn thương não, chẩn đoán chết não.', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `check_by_dates`
--

CREATE TABLE `check_by_dates` (
  `id` int(11) NOT NULL,
  `MA_LOI` varchar(255) NOT NULL,
  `LOAI_LOI` varchar(255) NOT NULL,
  `SO_LUONG` int(4) NOT NULL,
  `NGAY_DL` varchar(255) NOT NULL,
  `MO_TA` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `check_hein_cards`
--

CREATE TABLE `check_hein_cards` (
  `id` int(10) UNSIGNED NOT NULL,
  `ma_lk` varchar(100) NOT NULL,
  `ma_tracuu` varchar(10) NOT NULL,
  `ma_kiemtra` varchar(10) NOT NULL,
  `ma_ketqua` varchar(255) NOT NULL,
  `ghi_chu` text DEFAULT NULL,
  `ma_the` varchar(255) DEFAULT NULL,
  `ho_ten` varchar(255) DEFAULT NULL,
  `ngay_sinh` varchar(100) DEFAULT NULL,
  `dia_chi` varchar(255) DEFAULT NULL,
  `ma_the_cu` varchar(255) DEFAULT NULL,
  `ma_the_moi` varchar(255) DEFAULT NULL,
  `ma_dkbd` varchar(255) DEFAULT NULL,
  `cq_bhxh` varchar(255) DEFAULT NULL,
  `gioi_tinh` varchar(255) DEFAULT NULL,
  `gt_the_tu` varchar(255) DEFAULT NULL,
  `gt_the_den` varchar(255) DEFAULT NULL,
  `ma_kv` varchar(100) DEFAULT NULL,
  `ngay_du5nam` varchar(100) DEFAULT NULL,
  `maso_bhxh` varchar(255) DEFAULT NULL,
  `gt_the_tumoi` varchar(100) DEFAULT NULL,
  `gt_the_denmoi` varchar(100) DEFAULT NULL,
  `ma_dkbd_moi` varchar(100) DEFAULT NULL,
  `ten_dkbd_moi` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `check_insurances`
--

CREATE TABLE `check_insurances` (
  `id` int(10) UNSIGNED NOT NULL,
  `examine_number` varchar(255) NOT NULL,
  `insurance_number` varchar(255) NOT NULL,
  `patient_code` varchar(255) NOT NULL,
  `patient_name` varchar(255) NOT NULL,
  `birthday` varchar(255) NOT NULL,
  `date_examine` timestamp NULL DEFAULT NULL,
  `clinic_code` varchar(255) NOT NULL,
  `result_code` varchar(255) NOT NULL,
  `check_code` varchar(255) NOT NULL,
  `note` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `check_treatment_records`
--

CREATE TABLE `check_treatment_records` (
  `id` int(11) NOT NULL,
  `active` int(4) NOT NULL,
  `treatment_code` varchar(255) NOT NULL,
  `number` varchar(255) DEFAULT NULL,
  `department_id` int(4) NOT NULL,
  `department_name` varchar(255) NOT NULL,
  `check_code` varchar(255) DEFAULT NULL,
  `search_code` varchar(255) DEFAULT NULL,
  `old_number` varchar(255) DEFAULT NULL,
  `new_number` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`id`, `code`, `name`, `created_at`, `updated_at`) VALUES
(1, '606', 'Đắc Lắc', '2018-08-27 08:01:51', '0000-00-00 00:00:00'),
(2, '105', 'Hà Nội', '2018-08-27 08:01:51', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `clinics`
--

CREATE TABLE `clinics` (
  `id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `name` varchar(254) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `clinics`
--

INSERT INTO `clinics` (`id`, `code`, `name`, `created_at`, `updated_at`) VALUES
(1, '01', 'Khám nội', '2018-09-03 07:52:04', '0000-00-00 00:00:00'),
(2, '14', 'Khám ngoại', '2018-09-03 07:52:12', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `companies`
--

CREATE TABLE `companies` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `schema` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `companies`
--

INSERT INTO `companies` (`id`, `name`, `address`, `schema`, `created_at`, `updated_at`) VALUES
(1, 'Công ty test', 'Số 01 Thường Tín', 't_', '2017-11-22 17:00:00', '2017-11-22 17:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `consultants`
--

CREATE TABLE `consultants` (
  `id` int(11) NOT NULL,
  `icd_code` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `consultants`
--

INSERT INTO `consultants` (`id`, `icd_code`, `content`, `created_at`, `updated_at`) VALUES
(1, 'A91', 'HƯỚNG DẪN\r\nBỆNH NHÂN SỐT DENGUE XUẤT HUYẾT THEO DÕI TẠI NHÀ\r\nLƯU Ý:\r\nHạ sốt: Chườm ấm tích cực. Khi cần hạ sốt bằng thuốc chỉ sử dụng Paracetamol. Không được dùng Aspirin hoặc Ibuprofen.\r\nĂn: Thức ăn lỏng, dễ tiêu (hạn chế dầu, mỡ…). Tránh dùng thức ăn, nước uống có màu đen, đỏ (khó khăn trong đánh giá xuất huyết tiêu hóa).  \r\nUống: Uống nhiều nước đề bù lượng nước mất do sốt cao, ăn uống kém. Có thể uống các loại nước dinh dưỡng từ trái cây, sữa để cung cấp thêm vitamin, chất khoáng, năng lượng\r\nTái khám: theo lịch hẹn của bác sĩ hoặc ngay khi có dấu hiệu trở nặng.\r\nDẤU HIỆU BỆNH DENGUE XUẤT HUYẾT TRỞ NẶNG \r\n(hay gặp vào ngày thứ 4-7 của bệnh, khi giảm hoặc hết sốt)\r\n\r\n- Đau bụng, buồn nôn hoặc nôn.\r\n- Có dấu hiệu xuất huyết: Chảy máu cam, chảy máu chân răng, nôn máu, tiểu đỏ. Đại tiện phân đen, kinh nguyệt bất thường.\r\n- Chân tay lạnh, hết sốt nhưng vẫn mệt, ở trẻ em cần chú ý dấu hiệu li bì\r\n- Thay đổi tri giác (kích thích, lơ mơ, co giật)\r\nKhi có các dấu hiệu trở nặng cần liên hệ với bác sĩ đề được tư vấn hoặc đến cơ sở y tế gần nhất để được tiếp nhận và điều trị sớmHƯỚNG DẪN\r\nBỆNH NHÂN SỐT DENGUE XUẤT HUYẾT THEO DÕI TẠI NHÀ\r\nLƯU Ý:\r\nHạ sốt: Chườm ấm tích cực. Khi cần hạ sốt bằng thuốc chỉ sử dụng Paracetamol. Không được dùng Aspirin hoặc Ibuprofen.\r\nĂn: Thức ăn lỏng, dễ tiêu (hạn chế dầu, mỡ…). Tránh dùng thức ăn, nước uống có màu đen, đỏ (khó khăn trong đánh giá xuất huyết tiêu hóa).  \r\nUống: Uống nhiều nước đề bù lượng nước mất do sốt cao, ăn uống kém. Có thể uống các loại nước dinh dưỡng từ trái cây, sữa để cung cấp thêm vitamin, chất khoáng, năng lượng\r\nTái khám: theo lịch hẹn của bác sĩ hoặc ngay khi có dấu hiệu trở nặng.\r\nDẤU HIỆU BỆNH DENGUE XUẤT HUYẾT TRỞ NẶNG \r\n(hay gặp vào ngày thứ 4-7 của bệnh, khi giảm hoặc hết sốt)\r\n\r\n- Đau bụng, buồn nôn hoặc nôn.\r\n- Có dấu hiệu xuất huyết: Chảy máu cam, chảy máu chân răng, nôn máu, tiểu đỏ. Đại tiện phân đen, kinh nguyệt bất thường.\r\n- Chân tay lạnh, hết sốt nhưng vẫn mệt, ở trẻ em cần chú ý dấu hiệu li bì\r\n- Thay đổi tri giác (kích thích, lơ mơ, co giật)\r\nKhi có các dấu hiệu trở nặng cần liên hệ với bác sĩ đề được tư vấn hoặc đến cơ sở y tế gần nhất để được tiếp nhận và điều trị sớm\r\n', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `ID` int(10) NOT NULL,
  `MA_KHOA` varchar(100) NOT NULL,
  `TEN_KHOA` varchar(255) NOT NULL,
  `ACTIVE` int(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`ID`, `MA_KHOA`, `TEN_KHOA`, `ACTIVE`, `created_at`, `updated_at`) VALUES
(1, 'K11', 'Khoa Truyền nhiễm', 1, NULL, NULL),
(2, 'K01', 'Khoa Khám bệnh', 1, NULL, NULL),
(3, 'K35', 'Khoa Lọc máu (thận nhân tạo)', 1, '2019-06-20 17:00:00', '2019-06-20 17:00:00'),
(4, 'K16', 'Khoa Y học cổ truyền', 1, NULL, NULL),
(5, 'K24', 'Khoa Chấn thương chỉnh hình', 1, NULL, NULL),
(6, 'K03', 'Khoa Nội tổng hợp', 1, NULL, NULL),
(7, 'K18', 'Khoa Nhi', 1, NULL, NULL),
(8, 'K19', 'Khoa Ngoại tổng hợp', 1, NULL, NULL),
(9, 'K31', 'Khoa Vật lý trị liệu - Phục hồi chức năng', 1, NULL, NULL),
(10, 'K33', 'Khoa Ung bướu (điều trị tia xạ)', 1, NULL, NULL),
(11, 'K48', 'Khoa Hồi sức tích cực', 1, NULL, NULL),
(12, 'K26', 'Khoa Phẫu thuật - Gây mê hồi sức', 1, NULL, NULL),
(13, 'K27', 'Khoa Phụ sản', 1, NULL, NULL),
(14, 'K28', 'Khoa Tai - Mũi - Họng', 1, NULL, NULL),
(15, 'K30', 'Khoa Mắt', 1, NULL, NULL),
(16, 'K0249', 'Khoa Hồi sức cấp cứu; Khoa Chống độc', 1, NULL, NULL),
(17, 'K29', 'Khoa Răng - Hàm - Mặt', 1, NULL, NULL),
(18, 'K02', 'Khoa Hồi sức cấp cứu', 1, NULL, NULL),
(19, 'K04', 'Khoa Nội tim mạch', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `department_bed_catalogs`
--

CREATE TABLE `department_bed_catalogs` (
  `id` int(10) UNSIGNED NOT NULL,
  `ma_loai_kcb` int(11) DEFAULT NULL,
  `ma_khoa` varchar(255) NOT NULL,
  `ten_khoa` varchar(255) NOT NULL,
  `ban_kham` int(11) DEFAULT NULL,
  `giuong_pd` int(11) DEFAULT NULL,
  `giuong_2015` int(11) DEFAULT NULL,
  `giuong_tk` int(11) DEFAULT NULL,
  `giuong_hstc` int(11) DEFAULT NULL,
  `giuong_hscc` int(11) DEFAULT NULL,
  `ldlk` int(11) DEFAULT NULL,
  `lien_khoa` int(11) DEFAULT NULL,
  `den_ngay` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `districts`
--

CREATE TABLE `districts` (
  `id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `ma_tinh` varchar(10) NOT NULL,
  `name` varchar(254) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `districts`
--

INSERT INTO `districts` (`id`, `code`, `ma_tinh`, `name`, `created_at`, `updated_at`) VALUES
(1, '10521', '105', 'Huyện Thường Tín', '2018-08-29 14:38:19', '0000-00-00 00:00:00'),
(2, '10501', '105', 'Quận Hà Đông', '2018-08-31 01:04:57', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `email_receive_reports`
--

CREATE TABLE `email_receive_reports` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `active` int(4) NOT NULL,
  `bcaobhxh` int(4) NOT NULL,
  `bcaoqtri` int(4) NOT NULL,
  `bcaoadmin` int(4) NOT NULL,
  `qtri_tckt` int(4) NOT NULL,
  `qtri_hsdt` int(4) NOT NULL,
  `qtri_dvkt` int(4) NOT NULL,
  `qtri_canhbao` int(4) NOT NULL,
  `khoa_san` int(4) NOT NULL,
  `dinh_duong` int(4) NOT NULL,
  `period` int(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `equipment_catalogs`
--

CREATE TABLE `equipment_catalogs` (
  `id` int(10) UNSIGNED NOT NULL,
  `ten_tb` varchar(255) NOT NULL,
  `ky_hieu` varchar(255) NOT NULL,
  `congty_sx` varchar(255) NOT NULL,
  `nuoc_sx` varchar(255) NOT NULL,
  `nam_sx` int(11) NOT NULL,
  `nam_sd` int(11) NOT NULL,
  `ma_may` varchar(255) NOT NULL,
  `so_luu_hanh` varchar(255) NOT NULL,
  `hd_tu` varchar(255) DEFAULT NULL,
  `hd_den` varchar(255) DEFAULT NULL,
  `tu_ngay` varchar(255) DEFAULT NULL,
  `den_ngay` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `insurance_cards`
--

CREATE TABLE `insurance_cards` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `maKetQua` varchar(255) NOT NULL,
  `ghiChu` text NOT NULL,
  `maThe` varchar(255) DEFAULT NULL,
  `hoTen` varchar(255) DEFAULT NULL,
  `ngaySinh` varchar(255) DEFAULT NULL,
  `gioiTinh` varchar(255) DEFAULT NULL,
  `diaChi` varchar(255) DEFAULT NULL,
  `maDKBD` varchar(255) DEFAULT NULL,
  `cqBHXH` varchar(255) DEFAULT NULL,
  `gtTheTu` varchar(255) DEFAULT NULL,
  `gtTheDen` varchar(255) DEFAULT NULL,
  `maKV` varchar(255) DEFAULT NULL,
  `ngayDu5Nam` varchar(255) DEFAULT NULL,
  `maSoBHXH` varchar(255) DEFAULT NULL,
  `maTheCu` varchar(255) DEFAULT NULL,
  `maTheMoi` varchar(255) DEFAULT NULL,
  `gtTheTuMoi` varchar(255) DEFAULT NULL,
  `gtTheDenMoi` varchar(255) DEFAULT NULL,
  `maDKBDMoi` varchar(255) DEFAULT NULL,
  `tenDKBDMoi` varchar(255) DEFAULT NULL,
  `dsLichSuKCB2018` longtext DEFAULT NULL,
  `dsLichSuKT2018` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_staffs`
--

CREATE TABLE `medical_staffs` (
  `id` int(10) UNSIGNED NOT NULL,
  `ma_loai_kcb` int(11) NOT NULL,
  `ma_khoa` varchar(255) NOT NULL,
  `ten_khoa` varchar(255) NOT NULL,
  `ma_bhxh` varchar(255) NOT NULL,
  `ho_ten` varchar(255) NOT NULL,
  `gioi_tinh` tinyint(4) NOT NULL,
  `chucdanh_nn` int(11) NOT NULL,
  `vi_tri` int(11) DEFAULT NULL,
  `macchn` varchar(255) NOT NULL,
  `ngaycap_cchn` varchar(255) NOT NULL,
  `noicap_cchn` varchar(255) NOT NULL,
  `phamvi_cm` varchar(255) DEFAULT NULL,
  `phamvi_cmbs` varchar(255) DEFAULT NULL,
  `dvkt_khac` varchar(255) DEFAULT NULL,
  `vb_phancong` varchar(255) DEFAULT NULL,
  `thoigian_dk` int(11) NOT NULL,
  `thoigian_ngay` varchar(255) NOT NULL,
  `thoigian_tuan` varchar(255) NOT NULL,
  `cskcb_khac` varchar(255) DEFAULT NULL,
  `cskcb_cgkt` varchar(255) DEFAULT NULL,
  `qd_cgkt` varchar(255) DEFAULT NULL,
  `tu_ngay` varchar(255) NOT NULL,
  `den_ngay` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_supply_catalogs`
--

CREATE TABLE `medical_supply_catalogs` (
  `id` int(10) UNSIGNED NOT NULL,
  `ma_vat_tu` varchar(255) NOT NULL,
  `nhom_vat_tu` varchar(255) NOT NULL,
  `ten_vat_tu` varchar(255) NOT NULL,
  `ma_hieu` varchar(255) DEFAULT NULL,
  `quy_cach` varchar(255) DEFAULT NULL,
  `hang_sx` varchar(255) DEFAULT NULL,
  `nuoc_sx` varchar(255) DEFAULT NULL,
  `don_vi_tinh` varchar(255) DEFAULT NULL,
  `don_gia` decimal(18,2) DEFAULT NULL,
  `don_gia_bh` decimal(18,2) DEFAULT NULL,
  `tyle_tt_bh` decimal(18,2) DEFAULT NULL,
  `so_luong` int(11) DEFAULT NULL,
  `dinh_muc` int(11) DEFAULT NULL,
  `nha_thau` varchar(255) DEFAULT NULL,
  `tt_thau` varchar(255) NOT NULL,
  `tu_ngay` varchar(255) DEFAULT NULL,
  `den_ngay_hd` varchar(255) DEFAULT NULL,
  `ma_cskcb` varchar(255) DEFAULT NULL,
  `loai_thau` int(11) DEFAULT NULL,
  `ht_thau` int(11) DEFAULT NULL,
  `den_ngay` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicine_catalogs`
--

CREATE TABLE `medicine_catalogs` (
  `id` int(10) UNSIGNED NOT NULL,
  `ma_thuoc` varchar(255) NOT NULL,
  `ten_hoat_chat` varchar(255) NOT NULL,
  `ten_thuoc` varchar(255) NOT NULL,
  `don_vi_tinh` varchar(255) NOT NULL,
  `ham_luong` text NOT NULL,
  `duong_dung` varchar(255) NOT NULL,
  `ma_duong_dung` varchar(255) NOT NULL,
  `dang_bao_che` varchar(255) NOT NULL,
  `so_dang_ky` varchar(255) NOT NULL,
  `so_luong` int(11) DEFAULT NULL,
  `don_gia` decimal(18,2) DEFAULT NULL,
  `don_gia_bh` decimal(18,2) DEFAULT NULL,
  `quy_cach` varchar(255) DEFAULT NULL,
  `nha_sx` varchar(255) DEFAULT NULL,
  `nuoc_sx` varchar(255) DEFAULT NULL,
  `nha_thau` varchar(255) DEFAULT NULL,
  `tt_thau` varchar(255) DEFAULT NULL,
  `tu_ngay` varchar(255) DEFAULT NULL,
  `den_ngay` varchar(255) DEFAULT NULL,
  `ma_cskcb` varchar(255) DEFAULT NULL,
  `loai_thuoc` varchar(255) DEFAULT NULL,
  `loai_thau` varchar(255) DEFAULT NULL,
  `ht_thau` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicine_searchs`
--

CREATE TABLE `medicine_searchs` (
  `id` int(4) NOT NULL,
  `ma_thuoc` varchar(100) NOT NULL,
  `ten_thuoc` varchar(1000) NOT NULL,
  `ma_hoat_chat` varchar(100) NOT NULL,
  `ten_hoat_chat` varchar(1000) NOT NULL,
  `ma_duong_dung` varchar(100) NOT NULL,
  `ten_duong_dung` varchar(1000) NOT NULL,
  `ham_luong` varchar(100) NOT NULL,
  `so_dang_ky` varchar(100) NOT NULL,
  `nhom_thuoc` varchar(100) NOT NULL,
  `don_vi_tinh` varchar(100) NOT NULL,
  `don_gia` decimal(10,0) NOT NULL,
  `so_luong` decimal(10,0) NOT NULL,
  `hang_san_xuat` varchar(1000) NOT NULL,
  `nuoc_san_xuat` varchar(1000) NOT NULL,
  `nha_thau` varchar(1000) NOT NULL,
  `quyet_dinh` varchar(100) NOT NULL,
  `cong_bo` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicine_searchs`
--

INSERT INTO `medicine_searchs` (`id`, `ma_thuoc`, `ten_thuoc`, `ma_hoat_chat`, `ten_hoat_chat`, `ma_duong_dung`, `ten_duong_dung`, `ham_luong`, `so_dang_ky`, `nhom_thuoc`, `don_vi_tinh`, `don_gia`, `so_luong`, `hang_san_xuat`, `nuoc_san_xuat`, `nha_thau`, `quyet_dinh`, `cong_bo`, `created_at`, `updated_at`) VALUES
(1, '', 'Prednisolon 16mg', 'Methyl prednisolon', 'Methyl prednisolon', '1.01', 'Uống', '16mg', 'VD-24314-16', '3', 'Viên', '630', '50000', 'Chi nhánh công ty cổ phần dược phẩm trung ương Vidipha tại Bình Dương', 'Việt nam', 'CÔNG TY CỔ PHẦN DƯỢC PHẨM TRUNG ƯƠNG VIDIPHA', '191/QĐ-BV', '20200212', NULL, NULL),
(2, '', 'Fabapoxim', 'Cefpodoxim', 'Cefpodoxim', '1.01', 'Uống', '300mg/30ml', 'VD-16591-12', '3', 'lọ', '40000', '1500', 'CT CPDP Trung Ương I -Pharbaco', 'Việt nam', 'Công ty Cổ phần dược phẩm An Khang', '191/QĐ-BV', '20200212', NULL, NULL),
(3, '', 'Aspirin -100', 'Acetylsalicylic acid', 'Acetylsalicylic acid', '1.01', 'Uống', '100 mg', 'VD-20058-13', '3', 'viên', '450', '40000', 'Công ty cổ phần Traphaco', 'Việt nam', 'Công ty Cổ phần Traphaco', '191/QĐ-BV', '20200212', NULL, NULL),
(4, '', 'Kali clorid Kabi 10%', 'Kali clorid', 'Kali clorid', '2.10', 'Tiêm', '10% 10ml', 'VD-19566-13', '3', 'Ống', '1838', '10000', 'Fresenius Kabi', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Trung ương CPC1', '44/QĐ-BV', '20200110', NULL, NULL),
(5, '', 'Magnesi sulfat Kabi 15%', 'Magnesi sulfat', 'Magnesi sulfat', '2.15', 'Tiêm truyền', '15%/10ml', 'VD-19567-13', '3', 'Ống', '2415', '10000', 'Fresenius Kabi', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Trung ương CPC1', '44/QĐ-BV', '20200110', NULL, NULL),
(6, '', 'Levonor 1mg/ml', 'Nor-epinephrin (Nor- adrenalin)', 'Nor-epinephrin (Nor- adrenalin)', '2.10', 'Tiêm', '1mg/ml', 'VN-20116-16', '1', 'Ống', '35000', '5000', 'Warsaw', 'Ba Lan', 'Công ty Cổ phần Dược phẩm Trung ương CPC1', '44/QĐ-BV', '20200110', NULL, NULL),
(7, '', 'Tamiflu capsule 75mg', 'Oseltamivir', 'Oseltamivir', '1.01', 'Uống', '75mg', 'VN-16262-13', '1', 'Viên', '44877', '300', 'roche', '', 'Công ty TNHH MTV Dược liệu TW2', '44/QĐ-BV', '20200110', NULL, NULL),
(8, '', 'Lactated ringers', 'Ringer lactat', 'Ringer lactat', '2.15', 'Tiêm truyền', '500ml', 'VN-14668-12', '', 'Chai', '11000', '1992', 'Euromed', 'Philipin', 'Công ty cổ phần Dược phẩm trung ương CODUPHA', '44/QĐ-BV', '20200110', NULL, NULL),
(9, '', 'Glycerin Trinitrat-Hameln', 'Nitroglycerin', 'Nitroglycerin', '2.10', 'Tiêm', '10mg', 'VN-18845-15', '1', 'Ống', '80000', '100', 'Hamel', 'Đức', 'Công ty cổ phần Dược phẩm trung ương CODUPHA', '44/QĐ-BV', '20200110', NULL, NULL),
(10, '', 'Metoran', 'Metoclopramid', 'Metoclopramid', '2.10', 'Tiêm', '10mg/2ml', 'VD-25093-16', '3', 'Ống', '1365', '5000', 'Công ty Cổ phần Dược Danapha', 'Việt Nam', 'Công ty Cổ phần Dược Danapha', '44/QĐ-BV', '20200110', NULL, NULL),
(11, '', 'Acenocumarol', 'Acenocoumarol', 'Acenocoumarol', '1.01', 'Uống', '4mg', 'VD-22294-15', '3', 'Viên', '850', '10000', 'Công ty Cổ phần SPM', 'Việt Nam', 'Công ty cổ phần dược Đại Nam Hà Nội', '44/QĐ-BV', '20200110', NULL, NULL),
(12, '', 'Basethyrox', 'Propylthiouracil (PTU)', 'Propylthiouracil (PTU)', '1.01', 'Uống', '100mg', 'VD-21287-14', '3', 'Viên', '735', '5000', 'Công ty Cổ phần dược phẩm Hà Tây', 'Việt Nam', 'Công ty TNHH Dược phẩm Tân An', '44/QĐ-BV', '20200110', NULL, NULL),
(13, '', 'Cotrimstada', 'Sulfamethoxazol + trimethoprim', 'Sulfamethoxazol + trimethoprim', '1.01', 'Uống', '400mg + 80mg', 'VD-23965-15', '2', 'viên', '450', '5000', 'Chi nhánh Công ty TNHH LD Stada', 'Việt Nam', 'Công ty Cổ phần Dược và Thiết bị y tế T.N.T', '255/QĐ-BV', '20200221', NULL, NULL),
(14, '', 'Metformin Stada 500mg', 'Metformin', 'Metformin', '1.01', 'Uống', '500mg', 'VD-23976-15', '2', 'viên', '600', '200000', 'Chi nhánh Công ty TNHH LD Stada', 'Việt Nam', 'Công ty Cổ phần Dược và Thiết bị y tế T.N.T', '255/QĐ-BV', '20200221', NULL, NULL),
(15, '', 'Natri clorid 10%', 'Natri clorid', 'Natri clorid', '2.10', 'Tiêm', '10%/5ml', 'VD-20890-14', '2', 'Ống', '2310', '1000', 'Vinphaco', 'Việt Nam', 'Công ty cổ phần dược phẩm Vĩnh Phúc- Viphaco', '255/QĐ-BV', '20200221', NULL, NULL),
(16, '', 'Ciprofloxacin Kabi', 'Ciprofloxacin', 'Ciprofloxacin', '2.15', 'Tiêm truyền', '200mg/100ml', 'VD-20943-14', 'N2', 'Chai', '17575', '960', 'Công ty Cổ phần Fresenius Kabi Việt Nam', 'Việt Nam', 'Liên danh Sao Mai - Hà Tây', '14/QĐ-GĐB', '20190114', NULL, NULL),
(17, '', 'Levofloxacin Kabi', 'Levofloxacin*', 'Levofloxacin*', '2.15', 'Tiêm truyền', '500mg', 'VD-11241-10', 'N2', 'Chai', '19399', '12226', 'Công ty Cổ phần Fresenius Kabi Việt Nam', 'Việt Nam', 'Liên danh Sao Mai - Hà Tây', '14/QĐ-GĐB', '20190114', NULL, NULL),
(18, '', 'Levofloxacin Kabi', 'Levofloxacin', 'Levofloxacin', '2.15', 'Tiêm', '500mg', 'VD-29316-18', 'N2', 'Chai', '19399', '12226', 'Công ty Cổ phần Fresenius Kabi Việt Nam', 'Việt Nam', 'Liên danh Sao Mai - Hà Tây', '14/QĐ-GĐB', '20190114', NULL, NULL),
(19, '', 'Levofloxacin Kabi', 'Levofloxacin', 'Levofloxacin', '2.15', 'Tiêm', '500mg', 'VD-29316-18', 'N3', 'Chai', '19399', '0', 'Công ty Cổ phần Fresenius Kabi Việt Nam', 'Việt Nam', 'Liên danh Sao Mai - Hà Tây', '14/QĐ-GĐB', '20190114', NULL, NULL),
(20, '', 'Levofloxacin Kabi', 'Levofloxacin', 'Levofloxacin', '2.15', 'Tiêm', '500mg', 'VD-11241-10', 'N2', 'Chai', '19399', '0', 'Công ty Cổ phần Fresenius Kabi Việt Nam', 'Việt Nam', 'Liên danh Sao Mai - Hà Tây', '14/QĐ-GĐB', '20190114', NULL, NULL),
(21, '', 'Levofloxacin Kabi', 'Levofloxacin', 'Levofloxacin', '2.15', 'Tiêm', '500mg', 'VD-11241-10', 'N3', 'Chai', '19399', '0', 'Công ty Cổ phần Fresenius Kabi Việt Nam', 'Việt Nam', 'Liên danh Sao Mai - Hà Tây', '14/QĐ-GĐB', '20190114', NULL, NULL),
(22, '', 'Levofloxacin Kabi', 'Levofloxacin', 'Levofloxacin', '2.10', 'Tiêm', '500mg', 'VD-11241-10', '4', 'Chai', '19399', '5000', 'Công ty Cổ phần Fresenius Kabi Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Trung Ương CPC1', '351/QĐ-BV', '20200304', NULL, NULL),
(23, '', 'Vinphyton 1mg', 'Phytomenadion', 'Phytomenadion', '2.10', 'Tiêm', '1mg', 'VD-16307-12', '4', 'ống', '1260', '1000', 'Vinphaco', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Vĩnh Phúc', '351/QĐ-BV', '20200304', NULL, NULL),
(24, '', 'biracin E', 'Tobramycin', 'Tobramycin', '6.01', 'Nhỏ mắt', '15mg', 'VD-23135-15', '4', 'Lọ', '2982', '200', 'Bidiphar', 'Việt Nam', 'Chi nhánh Công Ty Cổ Phần Dược-Trang Thiết Bị Y Tế Bình Định (Bidiphar)', '351/QĐ-BV', '20200304', NULL, NULL),
(25, '', 'Tobidex', 'Tobramycin + dexamethason', 'Tobramycin + dexamethason', '6.01', 'Nhỏ mắt', '15mg + 5mg', 'VD-28242-17', '4', 'Lọ', '6195', '200', 'Bidiphar', 'Việt Nam', 'Chi nhánh Công Ty Cổ Phần Dược-Trang Thiết Bị Y Tế Bình Định (Bidiphar)', '351/QĐ-BV', '20200304', NULL, NULL),
(26, '', 'Ciprofloxacin Kabi', 'Ciprofloxacin', 'Ciprofloxacin', '2.10', 'Tiêm', '200mg/100ml', 'VD-20943-14', '4', 'Chai', '17575', '5000', 'Công ty Cổ phần Fresenius Kabi Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Trung Ương CPC1', '351/QĐ-BV', '20200304', NULL, NULL),
(27, '', 'Fentanyl 0,5mg/10ml', 'Fentanyl', 'Fentanyl', '2.10', 'Tiêm', '0,5/10ml', 'VN-17325-13', '1', 'ống', '24000', '2000', 'Siegfried Hameln', 'Đức', 'Công ty trách nhiệm hữu hạn một thành viên dược Sài Gòn', '37/QĐ-BV', '20200109', NULL, NULL),
(28, '', 'Fentanyl 0,1mg/2ml', 'Fentanyl', 'Fentanyl', '2.10', 'Tiêm', '0,1mg/2ml', 'VN-18441-14', '1', 'ống', '11800', '3000', 'Siegfried Hameln', 'Đức', 'Công ty trách nhiệm hữu hạn một thành viên dược Sài Gòn', '37/QĐ-BV', '20200109', NULL, NULL),
(29, '', 'Sodium Aescinate for Injection 10mg.', 'Aescin', 'Aescin', '2.10', 'Tiêm', '10mg', '2426/QLD-KD', '5', 'lọ', '88000', '5000', 'Wuhan Changlian Laifu Pharmaceutical Limited Liability Company', 'Trung Quốc', 'Công ty cổ phần Dược phẩm Trung ương CPC1', '1161/QĐ-BV', '20190911', NULL, NULL),
(30, '', 'Seretide Evohaler DC 25/50mcg.442', 'Salmeterol+ fluticason propionat', 'Salmeterol+ fluticason propionat', '5.02', 'Dạng hít', '25mcg + 50mcg/liều', 'VN-14684-12', '', 'Bình xịt', '191139', '2000', 'Glaxo Wellcome SA-Tây Ban Nha', 'Tây ban nha', 'Công ty trách nhiệm hữu hạn một thành viên dược liệu trung ương 2', '442/QĐ-BV', '20200323', NULL, NULL),
(31, '', 'Vigamox.442', 'Moxifloxacin', 'Moxifloxacin', '6.01', 'Nhỏ mắt', '5mg/ml', 'VN-22182-19', '', 'Lọ', '90000', '601', 'Alcon Research, Ltd.-Mỹ', 'Mỹ', 'Công ty trách nhiệm hữu hạn một thành viên dược liệu trung ương 2', '442/QĐ-BV', '20200323', NULL, NULL),
(32, '', 'Lovenox 60mg.442', 'Enoxaparin (natri)', 'Enoxaparin (natri)', '2.10', 'Tiêm', '60mg/ 0,6ml', 'QLSP-893-15', '', 'Bơm tiêm', '113163', '2000', 'Sanofi Winthrop Industrie - Pháp', 'Pháp', 'Công ty Cổ phần Dược phẩm thiết bị y tế Hà Nội', '442/QĐ-BV', '20200323', NULL, NULL),
(33, '', 'Lovenox 40mg.442', 'Enoxaparin (natri)', 'Enoxaparin (natri)', '2.10', 'Tiêm', '40mg/ 0,4ml', 'QLSP-892-15', '', 'Bơm tiêm', '85381', '2000', 'Sanofi Winthrop Industrie - Pháp', 'Pháp', 'Công ty Cổ phần Dược phẩm thiết bị y tế Hà Nội', '442/QĐ-BV', '20200323', NULL, NULL),
(34, '', 'Amaryl 1mg.442', 'Glimepirid', 'Glimepirid', '1.01', 'Uống', '1mg', 'VN-17391-13', '', 'Viên', '1984', '100000', 'PT Aventis Pharma - Indonesia', 'Indonesia', 'Công ty Cổ phần Dược phẩm thiết bị y tế Hà Nội', '442/QĐ-BV', '20200323', NULL, NULL),
(35, '', 'Amaryl 2mg.442', 'Glimepirid', 'Glimepirid', '1.01', 'Uống', '2mg', 'VD-28318-17', '', 'Viên', '3968', '100000', 'Công ty Cổ Phần Sanofi Việt Nam - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm thiết bị y tế Hà Nội', '442/QĐ-BV', '20200323', NULL, NULL),
(36, '', 'Aprovel .442', 'Irbesartan', 'Irbesartan', '1.01', 'Uống', '150 mg', 'VN-16719-13', '', 'Viên', '9561', '50000', 'Sanofi Winthrop Industrie - Pháp', 'Pháp', 'Công ty Cổ phần Dược phẩm thiết bị y tế Hà Nội', '442/QĐ-BV', '20200323', NULL, NULL),
(37, '', 'Nexium.442', 'Esomeprazol', 'Esomeprazol', '1.01', 'Uống', '40mg Esomeprazole', 'VN-15719-12', '', 'Lọ', '153560', '5000', 'AstraZeneca AB-Thụy Điển', 'Thụy điển', 'Công ty trách nhiệm hữu hạn một thành viên dược liệu trung ương 2', '442/QĐ-BV', '20200323', NULL, NULL),
(38, '', 'Seretide Evohaler DC 25/250mcg.442', 'Salmeterol+ fluticason propionat', 'Salmeterol+ fluticason propionat', '5.02', 'Dạng hít', '25mcg + 250mcg/liều', 'VN-14683-12', '', 'Bình xịt', '278090', '3000', 'Glaxo Wellcome S.A-Tây Ban Nha', 'Tây ban nha', 'Công ty trách nhiệm hữu hạn một thành viên dược liệu trung ương 2', '442/QĐ-BV', '20200323', NULL, NULL),
(39, '', 'Coversyl 5mg.442', 'Perindopril', 'Perindopril', '1.01', 'Uống', '5mg', 'VN-17087-13', '', 'Viên', '5650', '20000', 'Les Laboratoires Servier Industrie-Pháp', 'Pháp', 'Công ty trách nhiệm hữu hạn một thành viên dược liệu trung ương 2', '442/QĐ-BV', '20200323', NULL, NULL),
(40, '', 'Voluven 6%.442', 'Hydroxyethylstarch', 'Hydroxyethylstarch', '2.15', 'Tiêm truyền', '6%, 500ml', 'VN-19651-16', '', 'Túi', '110000', '2000', 'Fresenius Kabi Deutschland GmbH-Đức', 'Đức', 'Công ty trách nhiệm hữu hạn một thành viên dược liệu trung ương 2', '442/QĐ-BV', '20200323', NULL, NULL),
(41, '', 'Diprivan.442', 'Propofol', 'Propofol', '2.10', 'Tiêm', '10mg/ml', 'VN-15720-12', '', 'Ống', '118168', '5000', 'Corden Pharma S.P.A; đóng gói AstraZeneca UK Ltd.-CSSX: Ý, đóng gói: Anh', 'Anh', 'Công ty trách nhiệm hữu hạn một thành viên dược liệu trung ương 2', '442/QĐ-BV', '20200323', NULL, NULL),
(42, '', 'Diprivan.442', 'Propofol', 'Propofol', '2.10', 'Tiêm', '10mg/ml (1%)', 'VN-17251-13', '', 'Hộp', '375000', '2000', 'Corden Pharma S.P.A; đóng gói AstraZeneca UK Ltd.-CSSX: Ý, đóng gói: Anh', 'Anh', 'Công ty trách nhiệm hữu hạn một thành viên dược liệu trung ương 2', '442/QĐ-BV', '20200323', NULL, NULL),
(43, '', 'Esmeron.442', 'Rocuronium bromid', 'Rocuronium bromid', '2.10', 'Tiêm', '10 mg/ml x 5ml', 'VN-17751-14', '', 'Lọ', '104450', '2000', 'Siegfried Hameln GmbH; đóng gói & xuất xưởng: N.V. Organon-CSSX: Đức, đóng gói: Hà Lan', 'Hà Lan', 'Công ty trách nhiệm hữu hạn một thành viên dược liệu trung ương 2', '442/QĐ-BV', '20200323', NULL, NULL),
(44, '', 'Anaropin.442', 'Ropivacain hydroclorid', 'Ropivacain hydroclorid', '2.10', 'Tiêm', '2mg/ml', 'VN-19003-15', '', 'Ống', '63000', '2500', 'AstraZeneca AB-Thụy Điển', 'Thụy điển', 'Công ty trách nhiệm hữu hạn một thành viên dược liệu trung ương 2', '442/QĐ-BV', '20200323', NULL, NULL),
(45, '', 'No-Spa 40mg/2ml.442', 'Drotaverin clohydrat', 'Drotaverin clohydrat', '2.10', 'Tiêm', '40mg/ 2ml', 'VN-14353-11', '', 'Ống', '5306', '10000', 'Chinoin Pharmaceutical and Chemical Works Private Co.,Ltd. - Hungary', 'Hungarry', 'Công ty Cổ phần Dược phẩm thiết bị y tế Hà Nội', '442/QĐ-BV', '20200323', NULL, NULL),
(46, '', 'Lantus Solostar.442', 'Insulin tác dụng chậm, kéo dài (Slow-acting, Long-acting)', 'Insulin tác dụng chậm, kéo dài (Slow-acting, Long-acting)', '2.10', 'Tiêm', '300IU/3ml', 'QLSP-857-15', '', 'Bút tiêm', '277000', '1000', 'Sanofi-Aventis Deutschland GmbH - Đức', 'Đức', 'Công ty Cổ phần Dược phẩm thiết bị y tế Hà Nội', '442/QĐ-BV', '20200323', NULL, NULL),
(47, '', 'Depakine Chrono .442', 'Valproat natri + valproic acid', 'Valproat natri + valproic acid', '1.01', 'Uống', '333mg + 145mg', 'VN-16477-13', '', 'Viên', '6972', '10000', 'Sanofi Winthrop Industrie - Pháp', 'Pháp', 'Công ty Cổ phần Dược phẩm thiết bị y tế Hà Nội', '442/QĐ-BV', '20200323', NULL, NULL),
(48, '', 'Xenetix 300 (30g/100ml x 50ml).442', 'Iobitridol', 'Iobitridol', '2.10', 'Tiêm', '30g/100ml x 50ml', 'VN-16786-13', '', 'Lọ', '275000', '5000', 'Guerbet - Pháp', 'Pháp', 'Công ty Cổ phần Dược phẩm trung ương CPC1', '442/QĐ-BV', '20200323', NULL, NULL),
(49, '', 'Xenetix 300 (30g/ 100ml ).442', 'Iobitridol', 'Iobitridol', '2.10', 'Tiêm', '30g/ 100ml', 'VN-16787-13', '', 'Lọ', '485000', '4000', 'Guerbet - Pháp', 'Pháp', 'Công ty Cổ phần Dược phẩm trung ương CPC1', '442/QĐ-BV', '20200323', NULL, NULL),
(50, '', 'Xenetix 350 (35g/ 100ml).442', 'Iobitridol', 'Iobitridol', '2.10', 'Tiêm', '35g/ 100ml', 'VN-16789-13', '', 'Lọ', '635000', '2000', 'Guerbet, Pháp', 'Pháp', 'Công ty Cổ phần Dược phẩm trung ương CPC1', '442/QĐ-BV', '20200323', NULL, NULL),
(51, '', 'Pulmicort Respules.442', 'Budesonid', 'Budesonid', '5.05', 'Khí dung', '500mcg/2ml', 'VN-19559-16', '', 'Ống', '13834', '50000', 'AstraZeneca AB-Thụy Điển', 'Thụy điển', 'Công ty trách nhiệm hữu hạn một thành viên dược liệu trung ương 2', '442/QĐ-BV', '20200323', NULL, NULL),
(52, '', 'Symbicort Turbuhaler.442', 'Budesonid + formoterol', 'Budesonid + formoterol', '5.02', 'Dạng hít', '160mcg + 4,5mcg/liều', 'VN-20379-17', '', 'Ống', '286440', '2000', 'AstraZeneca AB-Thụy Điển', 'Thụy điển', 'Công ty trách nhiệm hữu hạn một thành viên dược liệu trung ương 2', '442/QĐ-BV', '20200323', NULL, NULL),
(53, '', 'Nexium Mups.442', 'Esomeprazol', 'Esomeprazol', '2.10', 'Tiêm', '40mg', 'VN-19782-16', '', 'Viên', '22456', '15000', 'AstraZeneca AB-Thụy Điển', 'Thụy điển', 'Công ty trách nhiệm hữu hạn một thành viên dược liệu trung ương 2', '442/QĐ-BV', '20200323', NULL, NULL),
(54, '', 'Xatral XL 10mg.442', 'Alfuzosin', 'Alfuzosin', '1.01', 'Uống', '10mg', 'VN-14355-11', '', 'Viên', '15291', '10000', 'Sanofi Winthrop Industrie - Pháp', 'Pháp', 'Công ty Cổ phần Dược phẩm thiết bị y tế Hà Nội', '442/QĐ-BV', '20200323', NULL, NULL),
(55, '', 'Ventolin Inhaler.442', 'Salbutamol sulfat', 'Salbutamol sulfat', '5.07', 'Xịt mũi', '100mcg/liều xịt', 'VN-18791-15', '', 'Bình xịt', '76379', '2000', 'Glaxo Wellcome SA; Cơ sở đóng gói thứ cấp, xuất xưởng: GlaxoSmithKline Australia Pty. Ltd,-CSSX: Tây Ban Nha, đóng gói: Úc', 'Úc', 'Công ty trách nhiệm hữu hạn một thành viên dược liệu trung ương 2', '442/QĐ-BV', '20200323', NULL, NULL),
(56, '', 'Ventolin Nebules.442', 'Salbutamol sulfat', 'Salbutamol sulfat', '5.07', 'Xịt mũi', '2,5mg/ 2,5ml', 'VN-20765-17', '', 'Ống', '4575', '40000', 'GlaxoSmithKline Australia Pty., Ltd.-Úc', 'Úc', 'Công ty trách nhiệm hữu hạn một thành viên dược liệu trung ương 2', '442/QĐ-BV', '20200323', NULL, NULL),
(57, '', 'Seretide Evohaler DC 25/125mcg.442', 'Salmeterol+ fluticason propionat', 'Salmeterol+ fluticason propionat', '5.02', 'Dạng hít', '25mcg + 125mcg', 'VN-21286-18', '', 'Bình xịt', '225996', '1000', 'Glaxo Wellcome S.A-Tây Ban Nha', 'Tây ban nha', 'Công ty trách nhiệm hữu hạn một thành viên dược liệu trung ương 2', '442/QĐ-BV', '20200323', NULL, NULL),
(58, '', 'Bridion.442', 'Sugammadex', 'Sugammadex', '2.10', 'Tiêm', '100mg/ml', 'VN-21211-18', '', 'Lọ', '1814340', '50', 'Patheon Manufacturing Services LLC; đóng gói tại: N.V. Organon-CSSX: Mỹ, đóng gói: Hà Lan', 'Hà Lan', 'Công ty trách nhiệm hữu hạn một thành viên dược liệu trung ương 2', '442/QĐ-BV', '20200323', NULL, NULL),
(59, '', 'Vastarel MR.442', 'Trimetazidin', 'Trimetazidin', '1.01', 'Uống', '35mg', 'VN-17735-14', '', 'Viên', '2705', '10000', 'Les Laboratoires Servier Industrie-Pháp', 'Pháp', 'Công ty trách nhiệm hữu hạn một thành viên dược liệu trung ương 2', '442/QĐ-BV', '20200323', NULL, NULL),
(60, '', 'Co-Diovan 160/25.442', 'Valsartan + hydroclorothiazid', 'Valsartan + hydroclorothiazid', '1.01', 'Uống', '160mg + 25mg', 'VN-19285-15', '', 'Viên', '17307', '2000', 'Novartis Farma S.p.A-Ý', 'Ý', 'Công ty trách nhiệm hữu hạn một thành viên dược liệu trung ương 2', '442/QĐ-BV', '20200323', NULL, NULL),
(61, '', 'Galvus Met 50mg/1000mg.442', 'Vildagliptin + metformin', 'Vildagliptin + metformin', '1.01', 'Uống', '50mg+1000mg', 'VN-19291-15', '', 'Viên', '9274', '10000', 'Novartis Pharma Produktions GmbH-Đức', 'Đức', 'Công ty trách nhiệm hữu hạn một thành viên dược liệu trung ương 2', '442/QĐ-BV', '20200323', NULL, NULL),
(62, '', 'Klacid.442', 'Clarithromycin', 'Clarithromycin', '1.01', 'Uống', '125mg/5ml', 'VN-16101-13', '', 'Lọ', '103140', '2000', 'PT. Abbott Indonesia - Indonesia', 'Indonesia', 'Công ty Trách nhiệm hữu hạn một thàn viên Vimedimex Bình Dương', '442/QĐ-BV', '20200323', NULL, NULL),
(63, '', 'Lipanthyl 200M.442', 'Fenofibrat', 'Fenofibrat', '1.01', 'Uống', '200mg', 'VN-17205-13', '', 'Viên', '7053', '15000', 'Recipharm Fontaine - Pháp', 'Pháp', 'Công ty Trách nhiệm hữu hạn một thàn viên Vimedimex Bình Dương', '442/QĐ-BV', '20200323', NULL, NULL),
(64, '', 'Elthon 50mg.442', 'Itoprid', 'Itoprid', '1.01', 'Uống', '50mg', 'VN-18978-15', '', 'Viên', '4796', '40000', 'Mylan EPD G.K. - Nhật', 'Nhật', 'Công ty Trách nhiệm hữu hạn một thàn viên Vimedimex Bình Dương', '442/QĐ-BV', '20200323', NULL, NULL),
(65, '', 'Duspatalin retard.442', 'Mebeverin hydroclorid', 'Mebeverin hydroclorid', '1.01', 'Uống', '200mg', 'VN-12831-11', '', 'Viên', '5870', '30000', 'Mylan Laboratories SAS - Pháp', 'Pháp', 'Công ty Trách nhiệm hữu hạn một thàn viên Vimedimex Bình Dương', '442/QĐ-BV', '20200323', NULL, NULL),
(66, '', 'Sevorane .442', 'Sevofluran', 'Sevofluran', '5.05', 'Khí dung', '250ml', 'VN-20637-17', '', 'Chai', '3578600', '150', 'AbbVie Srl-Ý', 'Ý', 'Công ty trách nhiệm hữu hạn một thành viên dược liệu trung ương 2', '442/QĐ-BV', '20200323', NULL, NULL),
(67, '', 'Oflovid.442', 'Ofloxacin', 'Ofloxacin', '6.01', 'Nhỏ mắt', '15mg/5ml', 'VN-19341-15', '', 'Lọ', '55873', '4000', 'Santen Pharmaceutical Co., Ltd.- Nhà máy Noto - Nhật', 'Nhật', 'Công ty Trách nhiệm hữu hạn một thàn viên Vimedimex Bình Dương', '442/QĐ-BV', '20200323', NULL, NULL),
(68, '', 'Oflovid Ophthalmic Ointment .442', 'Ofloxacin', 'Ofloxacin', '6.01', 'Nhỏ mắt', '0,3%', 'VN-18723-15', '', 'Tuýp', '74530', '4000', 'Santen Pharmaceutical Co. Ltd. - Nhật', 'Nhật', 'Công ty Trách nhiệm hữu hạn một thàn viên Vimedimex Bình Dương', '442/QĐ-BV', '20200323', NULL, NULL),
(69, '', 'Duoplavin.442', 'Acetylsalicylic acid+ clopidogrel', 'Acetylsalicylic acid+ clopidogrel', '1.01', 'Uống', '100mg + 75mg', 'VN-22466-19', '', 'Viên', '20828', '10000', 'Sanofi Winthrop Industrie - Pháp', 'Pháp', 'Công ty Cổ phần Dược phẩm thiết bị y tế Hà Nội', '442/QĐ-BV', '20200323', NULL, NULL),
(70, '', 'Boganic Forte.444', 'Actiso, Rau đắng, Bìm bìm', 'Actiso, Rau đắng, Bìm bìm', '1.01', 'Uống', '170mg + 128mg + 13,6mg', 'VD-19791-13', '', 'Viên', '1800', '200000', 'Traphaco', 'Việt Nam', 'Công ty cổ phần Traphaco', '444/QĐ-BV', '20200323', NULL, NULL),
(71, '', 'Lipidan.444', 'Bán hạ nam, Bạch linh, Xa tiền tử, Ngũ gia bì chân chim, Sinh khương, Trần bì, Rụt, Sơn tra, Hậu phá', 'Bán hạ nam, Bạch linh, Xa tiền tử, Ngũ gia bì chân chim, Sinh khương, Trần bì, Rụt, Sơn tra, Hậu phác nam', '1.01', 'Uống', '440mg; 890mg; 440mg; 440mg; 110mg; 440mg; 560mg; 440mg; 330mg', 'VD-26662-17', '', 'Viên', '2625', '200000', 'Công ty TNHH BRV Healthcare', 'Việt Nam', 'Công ty cổ phần thương mại dược phẩm và trang thiết bị y tế Thuận Phát', '444/QĐ-BV', '20200323', NULL, NULL),
(72, '', 'Boganic.444', 'Actiso, Rau đắng, Bìm bìm', 'Actiso, Rau đắng, Bìm bìm', '1.01', 'Uống', '100mg + 75mg + 7,5mg', 'VD-19790-13', '', 'Viên', '650', '400000', 'Traphaco', 'Việt Nam', 'Công ty cổ phần Traphaco', '444/QĐ-BV', '20200323', NULL, NULL),
(73, '', 'Hoạt huyết dưỡng não.444', 'Đinh lăng, Bạch quả', 'Đinh lăng, Bạch quả', '1.01', 'Uống', '150mg +5mg.', 'VD-19621-13', '', 'Viên', '800', '350000', 'Traphaco', 'Việt Nam', 'Công ty cổ phần Traphaco', '444/QĐ-BV', '20200323', NULL, NULL),
(74, '', 'Sáng mắt.444', 'Thục địa, Hoài sơn, Trạch tả, Cúc hoa, Thảo quyết minh, Hạ khô thảo, Hà thủ ô đỏ', 'Thục địa, Hoài sơn, Trạch tả, Cúc hoa, Thảo quyết minh, Hạ khô thảo, Hà thủ ô đỏ', '1.01', 'Uống', '125mg + 160mg + 160mg + 40mg + 40mg + 50mg + 24mg + 12,5mg', 'VD-24070-16', '', 'Viên', '650', '300000', 'Traphaco', 'Việt Nam', 'Công ty cổ phần Traphaco', '444/QĐ-BV', '20200323', NULL, NULL),
(75, '', 'Bổ gan tiêu độc Livsin-94.444', 'Diệp hạ châu, Chua ngút, Cỏ nhọ nồi', 'Diệp hạ châu, Chua ngút, Cỏ nhọ nồi', '1.01', 'Uống', '1500mg + 250mg + 250mg', 'VD-21649-14', '', 'Viên', '1500', '400000', 'Công ty Cổ phần Dược phẩm Hà Tây', 'Việt Nam', 'Công ty trách nhiệm hữu hạn dịch vụ đầu tư phát triển y tế Hà Nội', '444/QĐ-BV', '20200323', NULL, NULL),
(76, '', 'Piascledine.444', 'Cao toàn phần không xà phòng hóa quả bơ, Cao toàn phần không xà phòng hóa dầu đậu nành', 'Cao toàn phần không xà phòng hóa quả bơ, Cao toàn phần không xà phòng hóa dầu đậu nành', '1.01', 'Uống', '300mg (100mg + 200mg)', 'VN-16540-13', '', 'viên', '12000', '50000', 'Laboratoires Expanscience', 'Pháp', 'Công ty trách nhiệm hữu hạn dược phẩm và trang thiết bị y tế Hoàng Đức', '444/QĐ-BV', '20200323', NULL, NULL),
(77, '', 'Thiên sứ hộ tâm đan.444', 'Đan sâm, Tam thất, Borneol', 'Đan sâm, Tam thất, Borneol', '1.01', 'Uống', '43,56mg + 8,52mg + 1mg/ viên', 'VN-20102-16', '', 'viên', '460', '500000', 'Tasly Pharmaceutical Group Co.,LTD', 'Trung Quốc', 'Công ty trách nhiệm hữu hạn thương mại dược phẩm Đông Á', '444/QĐ-BV', '20200323', NULL, NULL),
(78, '', 'Thông Tâm Lạc.444', 'Nhân sâm, Thủy điệt, Toàn yết, Xích thược, Thuyền thoái, Thổ miết trùng, Ngô công, Đàn hương, Giáng ', 'Nhân sâm, Thủy điệt, Toàn yết, Xích thược, Thuyền thoái, Thổ miết trùng, Ngô công, Đàn hương, Giáng hương, Nhũ hương, Toan táo nhân, Băng phiến', '1.01', 'Uống', '37,67mg + 71,06mg + 47,09mg + 32,53mg + 47,09mg + 47,09mg + 9,42mg + 15,41mg + 16,27mg + 15,41mg + 3', 'VN-9380-09', '', 'Viên', '6900', '100000', 'Shijiazhuang Yiling', 'Trung Quốc', 'Công ty cổ phần dược phẩm Tùng Linh', '444/QĐ-BV', '20200323', NULL, NULL),
(79, '', 'Crila Forte.444', 'Cao khô Trinh nữ hoàng cung.', 'Cao khô Trinh nữ hoàng cung.', '1.01', 'Uống', '500mg', 'VD-24654-16', '', 'Viên', '4900', '100000', 'Công ty Cổ phần Dược phẩm Thiên Dược', 'Việt Nam', 'Công ty cổ phần dược phẩm Vinacare', '444/QĐ-BV', '20200323', NULL, NULL),
(80, '', 'Thập toàn đại bổ Vinaplant.444', 'Đương quy, Bạch truật,Đảng sâm, Quế nhục, Thục địa, Cam thảo, Hoàng kỳ,Bạch linh, Xuyên khung, Bạch ', 'Đương quy, Bạch truật,Đảng sâm, Quế nhục, Thục địa, Cam thảo, Hoàng kỳ,Bạch linh, Xuyên khung, Bạch thược', '1.01', 'Uống', '0,5g; 0,27g; 0,33g; 0,27g; 0,27g; 0,33g; 0,5g; 0,33g; 0,5g; 0,33g', 'VD-33554-19', '', 'Viên', '4000', '30000', 'Công ty Cổ phần dược phẩm Thành Phát', 'Việt Nam', 'Công ty cổ phần thương mại dược phẩm và trang thiết bị y tế Thuận Phát', '444/QĐ-BV', '20200323', NULL, NULL),
(81, '2020.G.455.1', 'Betahema.455', 'Erythropoietin', 'Erythropoietin', '2.10', 'Tiêm', '2000IU/ 1ml', 'QLSP-1145-19', '5', 'Lọ', '214000', '20000', 'Laboratorio Pablo Cassará S.R.L', 'Argentina', 'Công ty Cổ phần Dược Á Châu', '455/QĐ-BV', '20200325', NULL, NULL),
(82, '2020.G.455.2', 'Medskin Acyclovir 200 .455', 'Aciclovir', 'Aciclovir', '2.14', 'Truyền tĩnh mạch', '200mg', 'VD-20576-14', '2', 'viên', '950', '30000', 'CTCP Dược Hậu Giang - CN nhà máy DP DHG tại Hậu Giang', 'Việt Nam', 'Công ty cổ phần Dược Hậu Giang', '455/QĐ-BV', '20200325', NULL, NULL),
(83, '2020.G.455.3', 'Laci-eye.455', 'Hydroxypropylmethylcellulose', 'Hydroxypropylmethylcellulose', '6.01', 'Nhỏ mắt', '3mg/1ml', 'VD-27827-17', '4', 'Ống', '15000', '3000', 'Công ty cổ phần dược phẩm CPC1 Hà Nội', 'Việt Nam', 'Công ty cổ phần Dược phẩm CPC1 Hà Nội', '455/QĐ-BV', '20200325', NULL, NULL),
(84, '2020.G.455.4', 'Acyclovir Stada 800mg.455', 'Aciclovir', 'Aciclovir', '1.01', 'Uống', '800mg', 'VD-23346-15', '3', 'Viên', '4100', '30000', 'Công ty TNHH liên doanh Stellapharm', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Gia Linh', '455/QĐ-BV', '20200325', NULL, NULL),
(85, '2020.G.455.5', 'Scanneuron.455', 'Vitamin B1 + B6 + B12', 'Vitamin B1 + B6 + B12', '1.01', 'Uống', '100mg + 200mg + 200mcg', 'VD-22677-15', '2', 'Viên', '1000', '250000', 'Công ty TNHH liên doanh Stellapharm', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Gia Linh', '455/QĐ-BV', '20200325', NULL, NULL),
(86, '2020.G.455.6', 'Fefasdin 60.455', 'Fexofenadin', 'Fexofenadin', '1.01', 'Uống', '60mg', 'VD-26174-17', '4', 'Viên', '242', '80000', 'Công ty TNHH liên doanh Stellapharm', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Khánh Hòa', '455/QĐ-BV', '20200325', NULL, NULL),
(87, '2020.G.455.7', 'Kenzuda 5/12,5.455', 'Enalapril + hydrochlorothiazid', 'Enalapril + hydrochlorothiazid', '1.01', 'Uống', '5mg + 12,5mg', 'VD-32025-19', '4', 'Viên', '1995', '100000', 'Công ty cổ phần dược phẩm Tipharco', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Vian', '455/QĐ-BV', '20200325', NULL, NULL),
(88, '2020.G.455.8', 'Metformin Stada 1000mg MR.455', 'Metformin', 'Metformin', '1.01', 'Uống', '1000mg', 'VD-27526-17', '2', 'Viên', '2000', '200000', 'Công ty TNHH Liên doanh Stellapharm', 'Việt Nam', 'Công ty Cổ phần Dược và Thiết bị y tế T.N.T', '455/QĐ-BV', '20200325', NULL, NULL),
(89, '2020.G.455.9', 'Glaritus.455', 'Insulin tác dụng chậm, kéo dài (Slow-acting, Long-acting)', 'Insulin tác dụng chậm, kéo dài (Slow-acting, Long-acting)', '2.10', 'Tiêm', '100IU/ml', 'QLSP-1069-17', '5', 'Ống', '215000', '4000', 'Wockhardt Limited', 'India', 'Công ty TNHH Đắc HÀ', '455/QĐ-BV', '20200325', NULL, NULL),
(90, '2020.G.455.10', 'Wosulin-R.455', 'Insulin tác dụng nhanh, ngắn (Fast-acting, Short-acting)', 'Insulin tác dụng nhanh, ngắn (Fast-acting, Short-acting)', '2.10', 'Tiêm', '40IU/ml', 'VN-13426-11', '5', 'IU', '228', '1600000', 'Wockhardt Ltd.', 'India', 'Công ty TNHH Đắc HÀ', '455/QĐ-BV', '20200325', NULL, NULL),
(91, '2020.G.455.11', 'Wosulin 30/70.455', 'Insulin trộn, hỗn hợp (Mixtard-acting, Dual-acting)', 'Insulin trộn, hỗn hợp (Mixtard-acting, Dual-acting)', '2.10', 'Tiêm', '40IU/ml', 'VN-13424-11', '5', 'IU', '228', '4000000', 'Wockhardt Ltd.', 'India', 'Công ty TNHH Đắc HÀ', '455/QĐ-BV', '20200325', NULL, NULL),
(92, '2020.G.455.12', 'Bút Wosulin 30/70.455', 'Insulin trộn, hỗn hợp (Mixtard-acting, Dual-acting)', 'Insulin trộn, hỗn hợp (Mixtard-acting, Dual-acting)', '2.10', 'Tiêm', '100IU/ml', 'VN-13913-11', '5', 'Bút', '110000', '2000', 'Wockhardt Limited;', 'India', 'Công ty TNHH Đắc HÀ', '455/QĐ-BV', '20200325', NULL, NULL),
(93, '2020.G.455.13', 'Medibivo sol.455', 'Bromhexin hydroclorid', 'Bromhexin hydroclorid', '1.01', 'Uống', '4mg/5ml', 'VD-27935-17', '4', 'Ống', '1880', '30000', 'Công ty cổ phần dược phẩm Me Di Sun', 'Việt Nam', 'Công ty trách nhiệm hữu hạn Benephar', '455/QĐ-BV', '20200325', NULL, NULL),
(94, '2020.G.455.14', 'Lavezzi-10.455', 'Benazepril hydroclorid', 'Benazepril hydroclorid', '1.01', 'Uống', '10mg', 'VD-29722-18', '4', 'viên', '4500', '100000', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú', 'Việt Nam', 'Công ty Trách nhiệm Hữu hạn đầu tư phát triển y tế Hà Nộ', '455/QĐ-BV', '20200325', NULL, NULL),
(95, '2020.G.455.15', 'Magrax.455', 'Etoricoxib', 'Etoricoxib', '1.01', 'Uống', '90mg', 'VD-30344-18', '4', 'viên', '814', '100000', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú', 'Việt Nam', 'Công ty Trách nhiệm Hữu hạn đầu tư phát triển y tế Hà Nộ', '455/QĐ-BV', '20200325', NULL, NULL),
(96, '2020.G.455.16', 'Huntelaar.455', 'Lacidipin', 'Lacidipin', '1.01', 'Uống', '4mg', 'VD-19661-13', '4', 'viên', '2335', '300000', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú', 'Việt Nam', 'Công ty Trách nhiệm Hữu hạn đầu tư phát triển y tế Hà Nộ', '455/QĐ-BV', '20200325', NULL, NULL),
(97, '2020.G.455.17', 'Dinara.455', 'Tenofovir + Lamivudin', 'Tenofovir + Lamivudin', '1.01', 'Uống', '100mg + 300mg', 'QLĐB-600-17', '4', 'viên', '15000', '10000', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú', 'Việt Nam', 'Công ty Trách nhiệm Hữu hạn đầu tư phát triển y tế Hà Nộ', '455/QĐ-BV', '20200325', NULL, NULL),
(98, '2020.G.455.18', 'Tranagliptin 5.455', 'Linagliptin', 'Linagliptin', '1.01', 'Uống', '5mg', 'VD-29848-18', '4', 'viên', '10000', '50000', 'Công ty Cổ phần Dược phẩm Tipharco', 'Việt Nam', 'Công ty Trách nhiệm Hữu hạn đầu tư phát triển y tế Hà Nộ', '455/QĐ-BV', '20200325', NULL, NULL),
(99, '2020.G.455.19', 'Nerazzu-plus.455', 'Losartan + hydroclorothiazid', 'Losartan + hydroclorothiazid', '1.01', 'Uống', '100mg + 25mg', 'VD-26502-17', '4', 'viên', '2400', '250000', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú', 'Việt Nam', 'Công ty Trách nhiệm Hữu hạn đầu tư phát triển y tế Hà Nộ', '455/QĐ-BV', '20200325', NULL, NULL),
(100, '2020.G.455.20', 'Terzence-2,5.455', 'Methotrexat', 'Methotrexat', '1.01', 'Uống', '2,5mg', 'QLĐB-643-17', '4', 'viên', '2250', '1000', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú', 'Việt Nam', 'Công ty Trách nhiệm Hữu hạn đầu tư phát triển y tế Hà Nộ', '455/QĐ-BV', '20200325', NULL, NULL),
(101, '2020.G.455.21', 'Vigorito.455', 'Vildagliptin', 'Vildagliptin', '1.01', 'Uống', '50mg', 'VD-21482-14', '4', 'viên', '5190', '100000', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú', 'Việt Nam', 'Công ty Trách nhiệm Hữu hạn đầu tư phát triển y tế Hà Nộ', '455/QĐ-BV', '20200325', NULL, NULL),
(102, '2020.G.455.22', 'Agiclovir 5%.455', 'Aciclovir', 'Aciclovir', '3.05', 'Dùng ngoài', '5%,5g', 'VD-18693-13', '4', 'Tuýp', '4830', '5000', 'Cty CP DP Agimexpharm', 'Việt Nam', 'Công ty TNHH Dược phầm Ba Đình', '455/QĐ-BV', '20200325', NULL, NULL),
(103, '2020.G.455.23', 'Goutcolcin.455', 'Colchicin', 'Colchicin', '1.01', 'Uống', '1mg', 'VD-24115-16', '4', 'Viên', '294', '50000', 'CN Cty CP DP Agimexpharm', 'Việt Nam', 'Công ty TNHH Dược phầm Ba Đình', '455/QĐ-BV', '20200325', NULL, NULL),
(104, '2020.G.455.24', 'Olesom.455', 'Ambroxol', 'Ambroxol', '1.01', 'Uống', '30 mg/ 5ml', 'VN-14057-11', '2', 'Lọ', '39600', '10000', 'Gracure', 'India', 'Công ty TNHH Dược phẩm Mai Linh', '455/QĐ-BV', '20200325', NULL, NULL),
(105, '2020.G.455.25', 'Ebitac Forte.455', 'Enalapril + hydrochlorothiazid', 'Enalapril + hydrochlorothiazid', '1.01', 'Uống', '20mg + 12,5mg', 'VN-17896-14', '2', 'Viên', '3800', '100000', 'Ukraina', '', 'Công ty TNHH Dược phẩm Mai Linh', '455/QĐ-BV', '20200325', NULL, NULL),
(106, '2020.G.455.26', 'Cardesartan 16.455', 'Candesartan', 'Candesartan', '1.01', 'Uống', '16mg', 'VD-28951-18', '4', 'Viên', '1029', '100000', 'Công ty Cổ phần dược phẩm Hà Tây', 'Việt Nam', 'Công ty TNHH Dược phẩm Tân An', '455/QĐ-BV', '20200325', NULL, NULL),
(107, '2020.G.455.27', 'Cardesartan 8.455', 'Candesartan', 'Candesartan', '1.01', 'Uống', '8mg', 'VD-27878-17', '4', 'Viên', '693', '100000', 'Công ty Cổ phần dược phẩm Hà Tây', 'Việt Nam', 'Công ty TNHH Dược phẩm Tân An', '455/QĐ-BV', '20200325', NULL, NULL),
(108, '2020.G.455.28', 'Lodirein.455', 'Carbocistein', 'Carbocistein', '1.01', 'Uống', '375mg', 'VD-23586-15', '', 'Viên', '710', '100000', 'Công ty Cổ phần dược phẩm Me Di Sun', 'Việt Nam', 'Công ty TNHH Dược phẩm Tân An', '455/QĐ-BV', '20200325', NULL, NULL),
(109, '2020.G.455.32', 'Mixtard 30 .455', 'Insulin trộn, hỗn hợp (Mixtard-acting, Dual-acting)', 'Insulin trộn, hỗn hợp (Mixtard-acting, Dual-acting)', '2.10', 'Tiêm', '(700IU+ 300IU)/10ml', 'QLSP-1055-17', '2', 'Lọ', '74', '3000000', 'Novo Nordisk Production S.A.S', 'Pháp', 'Công ty TNHH MTV Vimedimex Bình Dương', '455/QĐ-BV', '20200325', NULL, NULL),
(110, '2020.G.455.34', 'Camzitol.455', 'Acetylsalicylic acid', 'Acetylsalicylic acid', '1.01', 'Uống', '100mg', 'VN-22015-19', '1', 'Viên', '2900', '100000', 'Farmalabor Produtos Farmacêuticos, S.A (Fab.)', 'portugal', 'Công ty TNHH TM DP Minh Quân', '455/QĐ-BV', '20200325', NULL, NULL),
(111, '2020.G.455.31', 'Mixtard 30 FlexPen .455', 'Insulin trộn, hỗn hợp (Mixtard-acting, Dual-acting)', 'Insulin trộn, hỗn hợp (Mixtard-acting, Dual-acting)', '2.10', 'Tiêm', '300IU/3ml', 'QLSP-1056-17', '1', 'Bút tiêm', '113800', '3000', 'Novo Nordisk Production S.A.S', 'Pháp', 'Công ty TNHH MTV Vimedimex Bình Dương', '455/QĐ-BV', '20200325', NULL, NULL),
(112, '2020.G.455.29', 'Duotrol.455', 'Metformin + glibenclamid', 'Metformin + glibenclamid', '1.01', 'Uống', '500mg + 5mg', 'VN-19750-16', '2', 'Viên', '3100', '150000', 'USV Private Limited', 'India', 'Công ty TNHH Đầu tư phát triển Hưng Thành', '455/QĐ-BV', '20200325', NULL, NULL),
(113, '2020.G.455.30', 'Insulidd N.455', 'Insulin người tác dụng trung bình, trung gian', 'Insulin người tác dụng trung bình, trung gian', '2.10', 'Tiêm', '400UI/10ml', 'VN-12286-11', '5', 'IU', '226', '2000000', 'M.J Biopharm Pvt., Ltd', 'India', 'Công ty TNHH MTV Dược Sài Gòn', '455/QĐ-BV', '20200325', NULL, NULL),
(114, '2020.G.455.35', 'Phamzopic 7.5mg.455', 'Zopiclon', 'Zopiclon', '1.01', 'Uống', '7.5mg', 'VN-18734-15', '1', 'Viên', '2700', '100000', 'Pharmascience Inc,', 'Canada', 'Công ty TNHH TM DP Minh Quân', '455/QĐ-BV', '20200325', NULL, NULL),
(115, '2020.G.455.36', 'Fabamox 250.455', 'Amoxicilin', 'Amoxicilin', '1.01', 'Uống', '250 mg', 'VD-21362-14', '3', 'Gói', '2160', '30000', 'Công ty CPDP Trung Ương I (Pharbaco)', 'Việt Nam', 'Công ty TNHH Thương mại Dược phẩm Thanh Phương', '455/QĐ-BV', '20200325', NULL, NULL),
(116, '2020.G.517.1', 'Morganin.517', 'Arginin hydroclorid', 'Arginin hydroclorid', '1.01', 'Uống', '500mg', 'VD-22466 -15', '4', 'Viên', '4400', '10', 'Công ty CP dược phẩm Hà Tây - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Davinci-Pháp', '517/QĐ-BV', '20200414', NULL, NULL),
(117, '2020.G.517.12', 'Ama Power.517', 'Ampicilin + sulbactam', 'Ampicilin + sulbactam', '2.10', 'Tiêm', '1g+500mg', 'VN-19857-16', '1', 'Lọ', '61050', '10000', 'S.C.Antibiotice S.A - Romani', 'Romani', 'Công ty Cổ phần Dược phẩm An Khang', '517/QĐ-BV', '20200414', NULL, NULL),
(118, '2020.G.517.13', 'Fabadroxil.517', 'Cefadroxil', 'Cefadroxil', '1.01', 'Uống', '250mg', 'VD-30523-18', '3', 'Gói', '4500', '10000', 'Cty CP Dược Phẩm Trung Ương I - Phabarco- Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm An Khang', '517/QĐ-BV', '20200414', NULL, NULL),
(119, '2020.G.517.14', 'Cetiam Inj. 1g.517', 'Cefotiam', 'Cefotiam', '2.10', 'Tiêm', '1g', 'VN-16869-13', '2', 'Lọ', '66000', '12000', 'Kyung Dong Pharm Co.,Ltd - Korea', 'Korea', 'Công ty Cổ phần Dược phẩm An Khang', '517/QĐ-BV', '20200414', NULL, NULL),
(120, '2020.G.517.15', 'Cefpodoxim 40mg/5ml.517', 'Cefpodoxim', 'Cefpodoxim', '1.01', 'Uống', '40mg/5ml lọ 60ml', 'VD-31221-18', '4', 'Lọ', '57000', '6000', 'Cty CP Dược Phẩm Trung Ương 2 - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm An Khang', '517/QĐ-BV', '20200414', NULL, NULL),
(121, '2020.G.517.16', 'Keronbe Inj.517', 'Ketoprofen', 'Ketoprofen', '2.10', 'Tiêm', '100mg/2ml', 'VN-20058-16', '2', 'Ống', '26500', '10000', 'Daihan Pharm. Co., Ltd - Korea', 'Korea', 'Công ty Cổ phần Dược phẩm An Khang', '517/QĐ-BV', '20200414', NULL, NULL),
(122, '2020.G.517.17', 'Scolanzo.517', 'Lanzoprazol', 'Lanzoprazol', '1.01', 'Uống', '30mg', 'VN-0735-10', '1', 'Viên', '9450', '15000', 'Laboratorlos Liconsa, S.A - Spain', 'Spain', 'Công ty Cổ phần Dược phẩm An Khang', '517/QĐ-BV', '20200414', NULL, NULL),
(123, '2020.G.517.18', 'Opeverin.517', 'Mebeverin hydroclorid', 'Mebeverin hydroclorid', '1.01', 'Uống', '135mg', 'VD-21678-14', '4', 'Viên', '1948', '100000', 'Cty CD Dược Phẩm OPV - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm An Khang', '517/QĐ-BV', '20200414', NULL, NULL),
(124, '2020.G.517.19', 'Tydol PM.517', 'Paracetamol + diphenhydramin', 'Paracetamol + diphenhydramin', '1.01', 'Uống', '500mg+25mg', 'VD-16977-12', '4', 'Viên', '850', '200000', 'Cty CD Dược Phẩm OPV - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm An Khang', '517/QĐ-BV', '20200414', NULL, NULL),
(125, '2020.G.517.20', 'Saihasin.517', 'Piracetam', 'Piracetam', '2.15', 'Tiêm truyền', '1200mg/10ml', 'VD-25526-16', '4', 'Ống', '6950', '10', 'Cty CP Dược Phẩm Hà Tây - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm An Khang', '517/QĐ-BV', '20200414', NULL, NULL),
(126, '2020.G.517.21', 'Phacodolin.517', 'Tinidazol', 'Tinidazol', '1.01', 'Uống', '500mg/100ml', 'VD-30537-18', '4', 'Chai', '25500', '40000', 'Cty CP Dược Phẩm Trung Ương I - Phabarco- Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm An Khang', '517/QĐ-BV', '20200414', NULL, NULL),
(127, '2020.G.517.22', 'Antivic 75.517', 'Pregabalin', 'Pregabalin', '1.01', 'Uống', '75mg', 'VD-26751-17', '5', 'Viên', '966', '10', 'Công ty Cổ phần Dược phẩm An Thiên - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm An Thiên', '517/QĐ-BV', '20200414', NULL, NULL),
(128, '2020.G.517.23', 'BFS - Amiron.517', 'Amiodaron hydroclorid', 'Amiodaron hydroclorid', '2.10', 'Tiêm', '150mg/ 3ml', 'VD-28871-18', '4', 'Lọ', '24000', '500', 'Công ty cổ phần dược phẩm CPC1 Hà Nội - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm CPC1 Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(129, '2020.G.517.5', 'Cefoperazone 0,5g.517', 'Cefoperazon', 'Cefoperazon', '2.10', 'Tiêm', '0,5g', 'VD-31708-19', '2', 'Lọ', '35000', '15000', 'Chi nhánh 3 - Công ty cổ phần Dược phẩm Imexpharm tại Bình Dương, Việt Nam', 'Việt Nam', 'Công ty Trách nhiệm hữu hạn Thương mại Dược phẩm Minh Quân', '517/QĐ-BV', '20200414', NULL, NULL),
(130, '2020.G.517.6', 'Fluconazole.517', 'Fluconazol', 'Fluconazol', '2.15', 'Tiêm truyền', '200mg/ 100ml', 'VN-10859-10', '1', 'Lọ', '219000', '2000', 'Solupharm Pharmazeutische Erzeugnisse GmbH, Đức', 'Đức', 'Công ty Trách nhiệm hữu hạn Thương mại Dược phẩm Minh Quân', '517/QĐ-BV', '20200414', NULL, NULL),
(131, '2020.G.517.7', 'Disomic.517', 'Ketoprofen', 'Ketoprofen', '3.05', 'Dùng ngoài', '50mg/ 2ml', 'VN-21526-18', '1', 'Ống', '19900', '20000', 'S.C.Rompharm Company S.r.l, Romani', 'Romani', 'Công ty Trách nhiệm hữu hạn Thương mại Dược phẩm Minh Quân', '517/QĐ-BV', '20200414', NULL, NULL),
(132, '2020.G.517.8', 'Budecort 0,5mg Respules.517', 'Budesonid', 'Budesonid', '5.05', 'Khí dung', '0,5mg/ 2ml', 'VN-15754-12', '2', 'Nang', '9900', '40000', 'Cipla Ltd. - India', 'India', 'Công ty Cổ phần Dược Á Châu', '517/QĐ-BV', '20200414', NULL, NULL),
(133, '2020.G.517.9', 'SaVi Gemfibrozil 600.517', 'Gemfibrozil', 'Gemfibrozil', '1.01', 'Uống', '600mg', 'VD-28033-17', '2', 'Viên', '4300', '100000', 'Công ty cổ phần dược phẩm SaVi - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược Á Châu', '517/QĐ-BV', '20200414', NULL, NULL),
(134, '2020.G.517.10', 'Glirit 500mg/2,5mg .517', 'Metformin + glibenclamid', 'Metformin + glibenclamid', '1.01', 'Uống', '500mg + 2,5mg', 'VD-24598-16', '3', 'viên', '1850', '100000', 'CTCP Dược Hậu Giang - CN nhà máy DP DHG tại Hậu Giang Việt Nam', 'Việt Nam', 'Công Ty Cổ phần Dược Hậu Giang', '517/QĐ-BV', '20200414', NULL, NULL),
(135, '2020.G.517.11', 'Hapacol Flu.517', 'Paracetamol + pseudoephedrin+ chlorpheniramin', 'Paracetamol + pseudoephedrin+ chlorpheniramin', '1.01', 'Uống', '500mg + 10mg + 2mg', 'VD-30131-18', '4', 'viên', '490', '150000', 'CTCP Dược Hậu Giang - CN nhà máy DP DHG tại Hậu Giang Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược Hậu Giang', '517/QĐ-BV', '20200414', NULL, NULL),
(136, '2020.G.517.25', 'Hemotocin.517', 'Carbetocin', 'Carbetocin', '2.10', 'Tiêm', '100mcg/1ml', 'VD-26774-17', '4', 'Lọ', '346500', '100', 'Công ty cổ phần dược phẩm CPC1 Hà Nội - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm CPC1 Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(137, '2020.G.517.26', 'Digoxin-BFS.517', 'Digoxin', 'Digoxin', '2.10', 'Tiêm', '0,25mg/ 1ml', 'VD-31618-19', '4', 'Lọ', '16000', '500', 'Công ty Cổ phần Dược phẩm CPC1 Hà Nội- Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm CPC1 Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(138, '2020.G.517.27', 'Dobutamin - BFS.517', 'Dobutamin', 'Dobutamin', '2.10', 'Tiêm', '250mg/5ml', 'VD-26125-17', '4', 'Ống', '55000', '500', 'Công ty cổ phần dược phẩm CPC1 Hà Nội - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm CPC1 Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(139, '2020.G.517.28', 'BFS-Famotidin.517', 'Famotidin', 'Famotidin', '2.10', 'Tiêm', '20mg/2ml', 'VD-29702-18', '4', 'Lọ', '38850', '7000', 'Công ty cổ phần dược phẩm CPC1 Hà Nội - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm CPC1 Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(140, '2020.G.517.29', 'Stiprol.517', 'Glycerol', 'Glycerol', '4.06', 'Thụt', '2,25g/3g. Tuýp 9g', 'VD-21083-14', '4', 'Tuýp', '6930', '200', 'Công ty cổ phần dược Hà Tĩnh - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm CPC1 Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(141, '2020.G.517.32', 'Magnesi-BFS 15%.517', 'Magnesi sulfat', 'Magnesi sulfat', '2.15', 'Tiêm truyền', '750mg/5ml', 'VD-22694-15', '4', 'Ống', '3700', '10000', 'Công ty cổ phần dược phẩm CPC1 Hà Nội - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm CPC1 Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(142, '2020.G.517.33', 'BFS-Naloxone.517', 'Naloxon hydroclorid', 'Naloxon hydroclorid', '2.10', 'Tiêm', '0,4mg/ml', 'VD-23379-15', '4', 'Ống', '29400', '100', 'Công ty cổ phần dược phẩm CPC1 Hà Nội - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm CPC1 Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(143, '2020.G.517.34', 'BFS-Neostigmine 0.25.517', 'Neostigmin metylsulfat', 'Neostigmin metylsulfat', '2.10', 'Tiêm', '0,25 mg/ml', 'VD-24008-15', '4', 'Ống', '5460', '5000', 'Công ty cổ phần dược phẩm CPC1 Hà Nội - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm CPC1 Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(144, '2020.G.517.35', 'BFS-Noradrenaline 10mg.517', 'Nor- adrenalin', 'Nor- adrenalin', '2.10', 'Tiêm', '10mg/10ml', 'VD-26771-17', '4', 'Lọ', '145000', '200', 'Công ty cổ phần dược phẩm CPC1 Hà Nội - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm CPC1 Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(145, '2020.G.517.37', 'Rocuronium-BFS.517', 'Rocuronium bromid', 'Rocuronium bromid', '2.10', 'Tiêm', '50mg/5ml', 'VD-26775-17', '4', 'Ống', '58000', '5000', 'Công ty cổ phần dược phẩm CPC1 Hà Nội - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm CPC1 Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(146, '2020.G.517.38', 'Zensalbu nebules 5.0.517', 'Salbutamol (sulfat)', 'Salbutamol (sulfat)', '5.05', 'Khí dung', '5mg/2,5ml', 'VD-21554-14', '4', 'Ống', '8400', '20000', 'Công ty cổ phần dược phẩm CPC1 Hà Nội - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm CPC1 Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(147, '2020.G.517.39', 'Arimenus.517', 'Terbutalin', 'Terbutalin', '2.10', 'Tiêm', '1 mg/ml', 'VD-26002-16', '4', 'Lọ', '19950', '10000', 'Công ty cổ phần dược phẩm CPC1 Hà Nội - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm CPC1 Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(148, '2020.G.517.40', 'BFS-Tranexamic 500mg/10ml.517', 'Tranexamic acid', 'Tranexamic acid', '2.10', 'Tiêm', '500mg/10ml', 'VD-24750-16', '4', 'Ống', '14000', '4000', 'Công ty cổ phần dược phẩm CPC1 Hà Nội - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm CPC1 Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(149, '2020.G.517.41', 'Bisostad 5.517', 'Bisoprolol', 'Bisoprolol', '1.01', 'Uống', '5mg', 'VD-23337-15', '1', 'Viên', '1020', '300000', 'Công ty TNHH liên doanh Stellapharm - Chi nhánh 1', 'Việt Nam', 'Công ty Cổ phần Dược Gia Linh', '517/QĐ-BV', '20200414', NULL, NULL),
(150, '2020.G.517.42', 'Lostad T50.517', 'Losartan', 'Losartan', '1.01', 'Uống', '50mg', 'VD-20373-13', '1', 'Viên', '1510', '100000', 'Công ty TNHH liên doanh Stellapharm - Chi nhánh 1', 'Việt Nam', 'Công ty Cổ phần Dược Gia Linh', '517/QĐ-BV', '20200414', NULL, NULL),
(151, '2020.G.517.43', 'Metformin Stada 500mg.517', 'Metformin', 'Metformin', '1.01', 'Uống', '500mg', 'VD-23976-15', '1', 'Viên', '585', '150000', 'Công ty TNHH liên doanh Stellapharm - Chi nhánh 1', 'Việt Nam', 'Công ty Cổ phần Dược Gia Linh', '517/QĐ-BV', '20200414', NULL, NULL),
(152, '2020.G.517.44', 'Torpace-5.517', 'Ramipril', 'Ramipril', '1.01', 'Uống', '5mg', 'VN-20351-17', '2', 'Viên', '3000', '100000', 'Torrent Pharmaceuticals Ltd. Ấn Độ', 'India', 'Công ty Cổ phần Dược phẩm Hiệp Bách Niên', '517/QĐ-BV', '20200414', NULL, NULL),
(153, '2020.G.517.45', 'Heberprot-P75.517', 'Recombinant human Epidermal Growth Factor (rhEGF)', 'Recombinant human Epidermal Growth Factor (rhEGF)', '2.10', 'Tiêm', '0,075mg', 'QLSP-0705-13', '1', 'Lọ', '10600000', '10', 'Center for Genetic Engineering and Biotechnology (CIGB) - CuBa', 'Cuba', 'Công ty Cổ phần Dược phẩm hoàng mai', '517/QĐ-BV', '20200414', NULL, NULL),
(154, '2020.G.517.46', 'Atorvastatin 10.517', 'Atorvastatin', 'Atorvastatin', '1.01', 'Uống', '10mg', 'VD-21312-14', '4', 'Viên', '129', '300000', 'Công ty cổ phần dược phẩm Khánh Hòa - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Khánh Hòa', '517/QĐ-BV', '20200414', NULL, NULL),
(155, '2020.G.517.47', 'Clanzen.517', 'Levocetirizin', 'Levocetirizin', '1.01', 'Uống', '5mg', 'VD-28970-18', '4', 'Viên', '167', '200000', 'Công ty cổ phần dược phẩm Khánh Hòa - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Khánh Hòa', '517/QĐ-BV', '20200414', NULL, NULL),
(156, '2020.G.517.48', 'Ofloxacin.517', 'Ofloxacin', 'Ofloxacin', '1.01', 'Uống', '200mg', 'VD-27919-17', '4', 'Viên', '278', '200000', 'Công ty cổ phần dược phẩm Khánh Hòa - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Khánh Hòa', '517/QĐ-BV', '20200414', NULL, NULL),
(157, '2020.G.517.49', 'Phenobarbital.517', 'Phenobarbital', 'Phenobarbital', '1.01', 'Uống', '100mg', 'VD-26868-17', '4', 'Viên', '239', '10000', 'Công ty cổ phần dược phẩm Khánh Hòa - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Khánh Hòa', '517/QĐ-BV', '20200414', NULL, NULL),
(158, '2020.G.517.50', 'Kamydazol.517', 'Spiramycin + metronidazol', 'Spiramycin + metronidazol', '1.01', 'Uống', '0,75MUI + 125mg', 'VD-25708-16', '4', 'Viên', '720', '20000', 'Công ty cổ phần dược phẩm Khánh Hòa - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Khánh Hòa', '517/QĐ-BV', '20200414', NULL, NULL),
(159, '2020.G.517.51', 'Hismedan.517', 'Trimetazidin', 'Trimetazidin', '1.01', 'Uống', '20mg', 'VD-17842-13', '4', 'Viên', '115', '200000', 'Công ty cổ phần dược phẩm Khánh Hòa - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Khánh Hòa', '517/QĐ-BV', '20200414', NULL, NULL),
(160, '2020.G.517.52', 'AMINOACID KABI 5% .517', 'Acid amin*', 'Acid amin*', '2.15', 'Tiêm truyền', '5% 250ml', 'VD-25361-16', '4', 'Chai thủy tinh', '44100', '2000', 'Công ty Cổ phần Fresenius Kabi Việt Nam-VietNam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Thiết bị y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(161, '2020.G.517.53', 'PERGLIM M-1..517', 'Glimepirid + metformin', 'Glimepirid + metformin', '1.01', 'Uống', '1mg + 500mg', 'VN-20806-17', '3', 'Viên', '2600', '200000', 'Inventia Healthcare Pvt. Ltd-India', 'India', 'Công ty Cổ phần Dược phẩm Thiết bị y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(162, '2020.G.517.54', 'Dipolac G.517', 'Betamethason dipropionat + clotrimazol + gentamicin', 'Betamethason dipropionat + clotrimazol + gentamicin', '3.05', 'Dùng ngoài', '9,6mg + 15mg + 150mg', 'VD-20117-13', '4', 'Tuýp', '10921', '3000', 'Công ty cổ phần dược phẩm Ampharco U.S.A - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Thiết bị y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(163, '2020.G.517.55', 'SYNDOPA 275.517', 'Levodopa + carbidopa', 'Levodopa + carbidopa', '1.01', 'Uống', '250mg +25mg', 'VN-13392-11', '5', 'Viên', '3650', '15000', 'Sun Pharmaceutical Industries Ltd-India', 'India', 'Công ty Cổ phần Dược phẩm Thiết bị y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(164, '2020.G.517.56', 'PANFOR SR-500.517', 'Metformin', 'Metformin', '1.01', 'Uống', '500mg', 'VN-20018-16', '5', 'Viên', '1200', '200000', 'Inventia Healthcare Pvt. Ltd-India', 'India', 'Công ty Cổ phần Dược phẩm Thiết bị y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(165, '2020.G.517.57', 'PANFOR SR-1000.517', 'Metformin', 'Metformin', '1.01', 'Uống', '1000mg', 'VN-20187-16', '3', 'Viên', '2000', '300000', 'Inventia Healthcare Pvt. Ltd-India', 'India', 'Công ty Cổ phần Dược phẩm Thiết bị y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(166, '2020.G.517.60', 'Diazepam-Hameln.517', 'Diazepam', 'Diazepam', '2.10', 'Tiêm', '10mg/2ml', 'VN-19414-15', '1', 'Ống', '7720', '10000', 'Siegfried Hameln GmbH - Đức', 'ĐỨc', 'Công ty cổ phần dược phẩm trung ương Codupha', '517/QĐ-BV', '20200414', NULL, NULL),
(167, '2020.G.517.61', 'Fentanyl 0,5mg-Rotexmedica.517', 'Fentanyl', 'Fentanyl', '2.10', 'Tiêm', '0,5mg/10ml', 'VN-18442-14', '1', 'Ống', '24000', '10000', 'Rotexmedica - Đức', 'ĐỨc', 'Công ty cổ phần dược phẩm trung ương Codupha', '517/QĐ-BV', '20200414', NULL, NULL),
(168, '2020.G.517.67', 'Kidmin.517', 'Acid amin*', 'Acid amin*', '2.15', 'Tiêm truyền', '7.2% 200ml', 'VD-28287-17', '4', 'Chai', '115000', '4000', 'Công Ty Cổ Phần Dược Phẩm Otsuka Việt Nam - Việt Nam', 'Việt Nam', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(169, '2020.G.517.68', 'Aminoleban.517', 'Acid amin*', 'Acid amin*', '2.15', 'Tiêm truyền', 'Dung dịch 8%/200ml acid amin', 'VD-27298-17', '4', 'Chai', '104000', '2000', 'Công Ty Cổ Phần Dược Phẩm Otsuka Việt Nam - Việt Nam', 'Việt Nam', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL);
INSERT INTO `medicine_searchs` (`id`, `ma_thuoc`, `ten_thuoc`, `ma_hoat_chat`, `ten_hoat_chat`, `ma_duong_dung`, `ten_duong_dung`, `ham_luong`, `so_dang_ky`, `nhom_thuoc`, `don_vi_tinh`, `don_gia`, `so_luong`, `hang_san_xuat`, `nuoc_san_xuat`, `nha_thau`, `quyet_dinh`, `cong_bo`, `created_at`, `updated_at`) VALUES
(170, '2020.G.517.69', 'Sodium Aescinate for Injection 10mg.517', 'Aescin', 'Aescin', '2.10', 'Tiêm', '10mg', '2426/QLD-KD', '5', 'Lọ', '88000', '2000', 'Wuhan Changlian Laifu Pharmaceutical Limited Liability Company - Trung Quốc', 'China', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(171, '2020.G.517.70', 'Sodium Aescinate for Injection 5mg.517', 'Aescin', 'Aescin', '2.10', 'Tiêm', '5mg', '2425/QLD-KD', '5', 'Lọ', '52500', '4000', 'Wuhan Changlian Laifu Pharmaceutical Limited Liability Company - Trung Quốc', 'China', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(172, '2020.G.517.71', 'Colistimetato de Sodio G.E.S 1 MUI.517', 'Colistin*', 'Colistin*', '2.10', 'Tiêm', '1 MUI', '11184/QLD-KD', '1', 'Lọ', '378000', '2000', 'Genfarma Laboratorio, S.L. - Tây Ban Nha', 'Spain', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(173, '2020.G.517.72', 'Diclofenac Kabi 75mg/3ml.517', 'Diclofenac', 'Diclofenac', '2.10', 'Tiêm', '75mg', 'VD-22589-15', '4', 'Ống', '860', '5000', 'Công ty cổ phần Fresenius Kabi Việt Nam - Việt Nam', 'Việt Nam', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(174, '2020.G.517.73', 'Ephedrine Aguettant 30mg/ml .517', 'Ephedrin ', 'Ephedrin ', '2.10', 'Tiêm', '30mg/ml', 'VN-19221-15', '1', 'Ống', '57750', '3000', 'Laboratoire Aguettant - Pháp', 'Pháp', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(175, '2020.G.517.74', 'Ephedrine Aguettant 30mg/10ml .517', 'Ephedrin ', 'Ephedrin ', '2.10', 'Tiêm', '30mg/10ml', 'VN-20793-17', '1', 'Ống', '83000', '3000', 'Laboratoire Aguettant - Pháp', 'Pháp', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(176, '2020.G.517.75', 'Fenosup Lidose.517', 'Fenofibrat', 'Fenofibrat', '1.01', 'Uống', '160mg', 'VN-17451-13', '1', 'Viên', '5267', '80000', 'SMB Technology S.A. - Bỉ', 'Bỉ', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(177, '2020.G.517.76', 'Dotarem.517', 'Gadoteric acid', 'Gadoteric acid', '2.10', 'Tiêm', '0,5mmol/ml', 'VN-15929-12', '1', 'Lọ', '520000', '500', 'Guerbet - Pháp', 'Pháp', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(178, '2020.G.517.77', 'Glucose 10% .517', 'Glucose', 'Glucose', '2.15', 'Tiêm truyền', '10% 500ml', 'VD-25876-16', '4', 'Chai', '10500', '10000', 'Công ty cổ phần Fresenius Kabi Việt Nam - Việt Nam', 'Việt Nam', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(179, '2020.G.517.78', 'Glucose 20% .517', 'Glucose', 'Glucose', '2.15', 'Tiêm truyền', '20% 500ml', 'VD-29314-18', '4', 'Chai', '12390', '1000', 'Công ty cổ phần Fresenius Kabi Việt Nam - Việt Nam', 'Việt Nam', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(180, '2020.G.517.79', 'Immunoglobulinum humanum normale - Biopharma.517', 'Immune globulin', 'Immune globulin', '2.10', 'Tiêm', '150mg/ 1,5ml', '5068/QLD-KD', '5', 'Ống', '253575', '50', 'Biofarma Plasma Limited Liability Company - Ukraine', 'Ukraine', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(181, '2020.G.517.80', 'Lidocain Kabi 2% .517', 'Lidocain (hydroclorid)', 'Lidocain (hydroclorid)', '2.10', 'Tiêm', '40mg/2ml', 'VD-31301-18', '4', 'Ống', '389', '29150', 'Công ty cổ phần Fresenius Kabi Việt Nam - Việt Nam', 'Việt Nam', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(182, '2020.G.517.81', 'Dung dịch tiêm Midanium.517', 'Midazolam', 'Midazolam', '2.10', 'Tiêm', '5mg/ml', 'VN-13844-11', '1', 'Ống', '17850', '5000', 'Warsaw Pharmaceutical Works Polfa S.A - Ba Lan', 'Ba Lan', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(183, '2020.G.517.82', 'Osaphine.517', 'Morphin (hydroclorid, sulfat)', 'Morphin (hydroclorid, sulfat)', '2.10', 'Tiêm', '10mg/1ml', 'VD-28087-17', '4', 'Ống', '3696', '1000', 'Công ty cổ phần dược phẩm Trung ương 1-Pharbaco - Việt Nam', 'Việt Nam', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(184, '2020.G.517.83', 'Natri bicarbonat 1.4% .517', 'Natri hydrocarbonat (natri bicarbonat)', 'Natri hydrocarbonat (natri bicarbonat)', '2.10', 'Tiêm', '1.4% 250ml', 'VD-25877-16', '4', 'Chai', '31973', '2000', 'Công ty cổ phần Fresenius Kabi Việt Nam - Việt Nam', 'Việt Nam', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(185, '2020.G.517.84', 'Natri clorid 0,9% 100ml.517', 'Natri clorid', 'Natri clorid', '2.10', 'Tiêm', '0.9% 100ml', 'VD-21954-14', '4', 'Chai', '7245', '80000', 'Công ty cổ phần Fresenius Kabi Việt Nam - Việt Nam', 'Việt Nam', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(186, '2020.G.517.85', 'Natri clorid 0,9% 500ml.517', 'Natri clorid', 'Natri clorid', '2.10', 'Tiêm', '0,9% 500ml', 'VD-21954-14', '4', 'Chai', '8400', '100000', 'Công ty cổ phần Fresenius Kabi Việt Nam - Việt Nam', 'Việt Nam', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(187, '2020.G.517.65', 'Sufentanil-hameln.517', 'Sufentanil', 'Sufentanil', '2.10', 'Tiêm', '50mcg/ml', 'VN-20250-17', '1', 'Ống', '45000', '5000', 'Siegfried Hameln GmbH - Đức', 'ĐỨc', 'Công ty cổ phần dược phẩm trung ương Codupha', '517/QĐ-BV', '20200414', NULL, NULL),
(188, '2020.G.517.66', 'MG-TAN Inj.517', 'Acid amin + glucose + lipid (*)', 'Acid amin + glucose + lipid (*)', '2.15', 'Tiêm truyền', '11,3% + 11%+ 20%/960ml', 'VN-21330-18', '5', 'Túi', '525000', '2000', 'MG Co., Ltd. - Hàn Quốc', 'Korea', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(189, '2020.G.517.86', 'Glucolyte-2.517', 'Natri clorid + kali clorid+ monobasic kali phosphat+ natri acetat + magnesi sulfat + kẽm sulfat + de', 'Natri clorid + kali clorid+ monobasic kali phosphat+ natri acetat + magnesi sulfat + kẽm sulfat + dextrose', '2.15', 'Tiêm truyền', 'Natri clorid 1,955g + Kali cloid 0,375g + Monobasic kaliphotphat 0,68g + Magie sulfat 0,316g + Kẽm s', 'VD-25376-16', '4', 'Chai', '17000', '20000', 'Công Ty Cổ Phần Dược Phẩm Otsuka Việt Nam - Việt Nam', 'Việt Nam', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(190, '2020.G.517.87', 'Levonor 1ml.517', 'Nor- adrenalin', 'Nor- adrenalin', '2.10', 'Tiêm', '1mg/ 1ml', 'VN-20116-16', '1', 'Ống', '35000', '5000', 'Warsaw Pharmaceutical Works Polfa S.A - Ba Lan', 'Ba Lan', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(191, '2020.G.517.88', 'Levonor 4ml.517', 'Nor- adrenalin', 'Nor- adrenalin', '2.10', 'Tiêm', '4mg/ 4ml', 'VN-20117-16', '1', 'Ống', '39480', '3000', 'Warsaw Pharmaceutical Works Polfa S.A - Ba Lan', 'Ba Lan', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(192, '2020.G.517.58', 'Dex-Tobrin.517', 'Tobramycin + dexamethason', 'Tobramycin + dexamethason', '6.01', 'Nhỏ mắt', '3mg/1ml + 1mg/1ml', 'VN-16553-13', '1', 'Lọ', '43919', '4000', 'Balkanpharma Razgrad AD - Bulgaria', 'Bulgaria', 'Công ty Cổ phần Dược phẩm Thiết bị y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(193, '2020.G.517.59', 'Lotafran.517', 'Lisinopril', 'Lisinopril', '1.01', 'Uống', '20mg', 'VN-20703-17', '1', 'Viên', '3600', '100000', 'S.C. Antibiotice S.A; Romani', 'Romani', 'Công ty Cổ phần Dược phẩm Thuận An Phát', '517/QĐ-BV', '20200414', NULL, NULL),
(194, '2020.G.517.64', 'Pethidin - Hameln.517', 'Pethidin', 'Pethidin', '2.10', 'Tiêm', '100mg/ 2ml', 'VN-19062-15', '1', 'Ống', '18000', '5000', 'Siegfried Hameln GmbH - Đức', 'ĐỨc', 'Công ty cổ phần dược phẩm trung ương Codupha', '517/QĐ-BV', '20200414', NULL, NULL),
(195, '2020.G.517.62', 'Mikrobiel .517', 'Moxifloxacin', 'Moxifloxacin', '2.10', 'Tiêm', '400mg/ 250ml', 'VN-21596-18', '1', 'Chai', '305000', '1500', 'Cooper S.A Pharmaceuticals - Greece', 'Greece', 'Công ty cổ phần dược phẩm trung ương Codupha', '517/QĐ-BV', '20200414', NULL, NULL),
(196, '2020.G.517.63', 'Paracetamol kabi AD.517', 'Paracetamol (acetaminophen)', 'Paracetamol (acetaminophen)', '2.10', 'Tiêm', '1g/100ml', 'VN-20677-17', '1', 'Chai', '42000', '20000', 'Fresenius Kabi - Đức', 'ĐỨc', 'Công ty cổ phần dược phẩm trung ương Codupha', '517/QĐ-BV', '20200414', NULL, NULL),
(197, '2020.G.517.89', 'Gardenal 10mg .517', 'Phenobarbital', 'Phenobarbital', '1.01', 'Uống', '10mg', 'VD-13895-11', '4', 'Viên', '140', '10000', 'Công ty cổ phần dược phẩm Trung ương 1-Pharbaco - Việt Nam', 'Việt Nam', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(198, '2020.G.517.90', 'Phenylalpha 50 micrograms/ml.517', 'Phenylephrin', 'Phenylephrin', '2.10', 'Tiêm', '50mcg/ml', 'VN-22162-19', '1', 'Ống', '121275', '1000', 'Laboratoire Aguettant - Pháp', 'Pháp', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(199, '2020.G.517.91', 'Acetate Ringer’s.517', 'Ringer lactat', 'Ringer lactat', '2.15', 'Tiêm truyền', 'Natri clorid 3g + Canciclorua dihydrate 0,1g + Kaliclorua 0,15g + Natriacetat trihydrate 1,9g', 'VD-24018-15', '4', 'Chai', '16000', '20000', 'Công Ty Cổ Phần Dược Phẩm Otsuka Việt Nam - Việt Nam', 'Việt Nam', 'Công ty cổ phần dược phẩm Trung ương CPC1', '517/QĐ-BV', '20200414', NULL, NULL),
(200, '2020.G.517.92', 'Ciclopirox 0,77%.517', 'Ciclopiroxolamin', 'Ciclopiroxolamin', '3.05', 'Dùng ngoài', '7,7mg/g', 'VD-32007-19', '4', 'Tuýp', '40000', '2000', 'Công ty cổ phần dược phẩm VCP - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Dược phẩm VCP', '517/QĐ-BV', '20200414', NULL, NULL),
(201, '2020.G.517.93', 'Clotrimazol VCP.517', 'Clotrimazol', 'Clotrimazol', '3.05', 'Dùng ngoài', '150mg', 'VD-29209-18', '4', 'Tuýp', '12000', '3000', 'Công ty cổ phần dược phẩm VCP - Việt Nam', 'Việt Nam', 'Công ty Cổ phần dược phẩm VCP', '517/QĐ-BV', '20200414', NULL, NULL),
(202, '2020.G.517.94', 'Berlthyrox 100.517', 'Levothyroxin (muối natri)', 'Levothyroxin (muối natri)', '1.01', 'Uống', '100mcg', 'VN-10763-10', '2', 'Viên', '490', '5000', 'Berlin Chemie AG (Menarini Group) - Đức', 'ĐỨc', 'Công ty Cổ phần Dược phẩm Việt HÀ', '517/QĐ-BV', '20200414', NULL, NULL),
(203, '2020.G.517.95', 'Neo-Tergynan.517', 'Metronidazol + neomycin + nystatin', 'Metronidazol + neomycin + nystatin', '4.01', 'Đặt âm đạo', '500mg + 65.000UI + 100.000UI', 'VN-18967-15', '1', 'Viên đặt', '11880', '5000', 'Sophartex - Pháp', 'Pháp', 'Công ty Cổ phần Dược phẩm Việt HÀ', '517/QĐ-BV', '20200414', NULL, NULL),
(204, '2020.G.517.96', 'Kbat.517', 'Itraconazol', 'Itraconazol', '1.01', 'Uống', '100mg', 'VN-17320-13', '1', 'Viên', '16000', '10000', 'S.C. Slavia Pharma S.R.L. - Romania', 'Romani', 'Công ty cổ phần dược phẩm Việt Nga', '517/QĐ-BV', '20200414', NULL, NULL),
(205, '2020.G.517.97', 'Meyerursolic F.517', 'Ursodeoxycholic acid', 'Ursodeoxycholic acid', '1.01', 'Uống', '500mg', 'VD-30051-18', '4', 'Viên', '18500', '1000', 'Công ty Liên doanh Meyer - BPC - Việt Nam', 'Việt Nam', 'Công ty cổ phần dược phẩm Việt Nga', '517/QĐ-BV', '20200414', NULL, NULL),
(206, '2020.G.517.98', 'Atropin sulfat .517', 'Atropin (sulfat)', 'Atropin (sulfat)', '2.10', 'Tiêm', '0,25mg/1ml', 'VD-24897-16', '4', 'ống', '460', '10000', 'Vinphaco - Việt nam', 'Việt Nam', 'Công ty Cổ phần DƯợc phẩm Vĩnh Phúc', '517/QĐ-BV', '20200414', NULL, NULL),
(207, '2020.G.517.99', 'Dimedrol.517', 'Diphenhydramin', 'Diphenhydramin', '2.10', 'Tiêm', '10mg/1ml', 'VD-24899-16', '4', 'ống', '504', '7710', 'Vinphaco - Việt nam', 'Việt Nam', 'Công ty Cổ phần DƯợc phẩm Vĩnh Phúc', '517/QĐ-BV', '20200414', NULL, NULL),
(208, '2020.G.517.100', 'Paparin .517', 'Papaverin hydroclorid', 'Papaverin hydroclorid', '2.10', 'Tiêm', '40mg/2ml', 'VD-20485-14', '4', 'ống', '2835', '1000', 'Vinphaco - Việt nam', 'Việt Nam', 'Công ty Cổ phần DƯợc phẩm Vĩnh Phúc', '517/QĐ-BV', '20200414', NULL, NULL),
(209, '2020.G.517.101', 'Vinphyton .517', 'Phytomenadion (vitamin K1)', 'Phytomenadion (vitamin K1)', '2.10', 'Tiêm', '1mg/1ml', 'VD-16307-12', '4', 'ống', '1470', '5000', 'Vinphaco - Việt nam', 'Việt Nam', 'Công ty Cổ phần DƯợc phẩm Vĩnh Phúc', '517/QĐ-BV', '20200414', NULL, NULL),
(210, '2020.G.517.115', 'Trozimed.517', 'Calcipotriol', 'Calcipotriol', '3.05', 'Dùng ngoài', '1,5mg/30g', 'VD-28486-17', '4', 'tuýp', '136500', '10', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú - Việt Nam', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(211, '2020.G.517.116', 'Dopolys.517', 'Cao ginkgo biloba+ heptaminol clohydrat+ troxerutin', 'Cao ginkgo biloba+ heptaminol clohydrat+ troxerutin', '1.01', 'Uống', '7mg + 150mg + 150mg', 'VD-13124-10', '4', 'viên', '2410', '100000', 'Công ty cổ phần xuất nhập khẩu y tế Domesco - Việt Nam', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(212, '2020.G.517.117', 'Tepirace.517', 'Clonidin', 'Clonidin', '1.01', 'Uống', '0,15mg', 'VD-30352-18', '4', 'viên', '3000', '1000', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú - Việt Nam', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(213, '2020.G.517.118', 'Paolucci.517', 'Deferipron', 'Deferipron', '1.01', 'Uống', '500mg', 'VD-21063-14', '4', 'viên', '4500', '1000', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú - Việt Nam', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(214, '2020.G.517.119', 'Dryches.517', 'Dutasterid', 'Dutasterid', '1.01', 'Uống', '0,5mg', 'VD-28454-17', '4', 'viên', '7000', '50000', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú - Việt Nam', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(215, '2020.G.517.120', 'Xonatrix forte.517', 'Fexofenadin', 'Fexofenadin', '1.01', 'Uống', '180mg', 'VD-18842-13', '4', 'viên', '3500', '10', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú - Việt Nam', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(216, '2020.G.517.121', 'Manduka.517', 'Flavoxat', 'Flavoxat', '1.01', 'Uống', '200mg', 'VD-28472-17', '4', 'viên', '5570', '40000', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú - Việt Nam', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(217, '2020.G.517.102', 'Vitamin B1 .517', 'Vitamin B1', 'Vitamin B1', '2.10', 'Tiêm', '100mg/1ml', 'VD-25834-16', '4', 'ống', '567', '10000', 'Vinphaco - Việt nam', 'Việt Nam', 'Công ty Cổ phần DƯợc phẩm Vĩnh Phúc', '517/QĐ-BV', '20200414', NULL, NULL),
(218, '2020.G.517.103', 'Prazone - S 2.0g.517', 'Cefoperazon + sulbactam', 'Cefoperazon + sulbactam', '2.10', 'Tiêm', '1g+1g', 'VN-18288-14', '2', 'Lọ', '75500', '35000', 'Venus Remedies limited - Ấn độ', 'India', 'Công ty Cổ phần Dược và thiết bị y tế T.N.T', '517/QĐ-BV', '20200414', NULL, NULL),
(219, '2020.G.517.104', 'Heparigen Inj.517', 'L-Ornithin - L- aspartat', 'L-Ornithin - L- aspartat', '2.10', 'Tiêm', '500mg/5ml', 'VN-18415-14', '2', 'Ống', '15000', '10000', 'Dai Han Pharm. Co., Ltd - Korea', 'Korea', 'Công ty Cổ phần Dược và thiết bị y tế T.N.T', '517/QĐ-BV', '20200414', NULL, NULL),
(220, '2020.G.517.105', 'Aspirin 100.517', 'Acetylsalicylic acid', 'Acetylsalicylic acid', '1.01', 'Uống', '100 mg', 'VD-20058-13', '4', 'Viên', '450', '300000', 'Traphaco HY Việt Nam', 'Việt Nam', 'Công ty Cổ phần Traphaco', '517/QĐ-BV', '20200414', NULL, NULL),
(221, '2020.G.517.106', 'Dorodipin 10mg.517', 'Amlodipin', 'Amlodipin', '1.01', 'Uống', '10mg', 'VD-25426-16', '3', 'Viên', '410', '100000', 'Công ty Cổ phần Xuất nhập khẩu Y tế DOMESCO - Việt Nam', 'Việt Nam', 'Công ty Cổ phần Xuất nhập khẩu y tế Domesco', '517/QĐ-BV', '20200414', NULL, NULL),
(222, '2020.G.517.107', 'Colistimethate for Injection U.S.P.517', 'Colistin*', 'Colistin*', '2.10', 'Tiêm', '150mg', 'VN-20727-17', '1', 'Lọ', '1518000', '400', 'Patheon Manufacturing Services LLC-USA', 'USA', 'Công ty cổ phần xuất nhập khẩu y tế Thái an', '517/QĐ-BV', '20200414', NULL, NULL),
(223, '2020.G.517.108', 'Relipoietin 4000IU Erythropoietin người tái tổ hợp 4000IU.517', 'Erythropoietin', 'Erythropoietin', '2.10', 'Tiêm', '4000IU', 'QLSP-GC-H03-1106-18', '4', 'Bơm tiêm', '278000', '10000', 'Cơ sở sản xuất và đóng gói sơ cấp: Reliance Life Sciences Pvt. Ltd.-India; Cơ sở nhận gia công, đóng gói thứ cấp và xuất xưởng: Công ty CP Dược phẩm Trung Ương I- Pharbaco - Việt Nam', 'Việt Nam', 'Công ty cổ phần xuất nhập khẩu y tế Thái an', '517/QĐ-BV', '20200414', NULL, NULL),
(224, '2020.G.517.109', 'Suxamethonium chlorid VUAB 100mg.517', 'Suxamethonium clorid', 'Suxamethonium clorid', '2.10', 'Tiêm', '100mg/2ml', '7386/QLD-KD', '1', 'Lọ', '24000', '2000', 'VUAB Pharma a.s -Cộng hòa Séc', 'Cộng hòa séc', 'Công ty cổ phần xuất nhập khẩu y tế Thái an', '517/QĐ-BV', '20200414', NULL, NULL),
(225, '2020.G.517.110', 'Lisiplus HCT 10/12.5.517', 'Lisinopril + hydroclorothiazid', 'Lisinopril + hydroclorothiazid', '1.01', 'Uống', '10 mg + 12,5 mg', 'VD-17766-12', '1', 'Viên', '3000', '100000', 'Công ty TNHH Liên doanh Stellapharm-Chi nhánh 1; Việt Nam', 'Việt Nam', 'Công ty TNHH Đắc Hà', '517/QĐ-BV', '20200414', NULL, NULL),
(226, '2020.G.517.111', 'Ezvasten.517', 'Atorvastatin + ezetimibe', 'Atorvastatin + ezetimibe', '1.01', 'Uống', '20mg + 10mg', 'VD-19657-13', '4', 'viên', '5900', '200000', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú - Việt Nam', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(227, '2020.G.517.112', 'Enterobella.517', 'Bacillus clausii', 'Bacillus clausii', '1.01', 'Uống', '1.109 - 2.109 cfu', 'QLSP-0794-14', '4', 'viên', '3900', '150000', 'Công ty Cổ phần hóa - dược phẩm Mekophar - Việt Nam', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(228, '2020.G.517.113', 'Zentobiso 10.0mg.517', 'Bisoprolol', 'Bisoprolol', '1.01', 'Uống', '10mg', 'VN-17387-13', '1', 'viên', '7000', '50000', 'Niche Generics Ltd. - Ireland', 'Ireland', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(229, '2020.G.517.114', 'Clipoxid-300.517', 'Calci lactat', 'Calci lactat', '1.01', 'Uống', '300mg', 'VD-19652-13', '4', 'viên', '1290', '100000', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú - Việt Nam', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(230, '2020.G.517.122', 'Salgad.517', 'Fluconazol', 'Fluconazol', '1.01', 'Uống', '150mg', 'VD-28483-17', '4', 'viên', '2000', '10000', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú - Việt Nam', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(231, '2020.G.517.123', 'Reinal.517', 'Flunarizin', 'Flunarizin', '1.01', 'Uống', '10mg', 'VD-28482-17', '4', 'viên', '525', '250000', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú - Việt Nam', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(232, '2020.G.517.124', 'Thuốc tiêm Sinrodan 30mg/ml.517', 'Ketorolac', 'Ketorolac', '2.10', 'Tiêm', '30mg/1ml', 'VD-17602-13', '2', 'ống', '7400', '2000', 'Taiwan Biotech Co., Ltd. - Đài Loan', 'Đài loan', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(233, '2020.G.517.126', 'Kauskas-50.517', 'Lamotrigine', 'Lamotrigine', '1.01', 'Uống', '50mg', 'VD-28914-18', '4', 'viên', '3160', '500', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú - Việt Nam', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(234, '2020.G.517.127', 'Lefvox-750.517', 'Levofloxacin', 'Levofloxacin', '1.01', 'Uống', '750mg', 'VD-31088-18', '4', 'viên', '7960', '10000', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú - Việt Nam', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(235, '2020.G.517.128', 'Bacterocin Oint.517', 'Mupirocin', 'Mupirocin', '3.05', 'Dùng ngoài', '20mg/g x 5g', 'VN-21777-19', '2', 'tuýp', '36000', '2000', 'Kolmar Korea - Hàn Quốc', 'Korea', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(236, '2020.G.517.129', 'Sakuzyal.517', 'Oxcarbazepin', 'Oxcarbazepin', '1.01', 'Uống', '300mg', 'VD-15281-11', '4', 'viên', '3100', '15000', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú - Việt Nam', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(237, '2020.G.517.130', 'Moritius.517', 'Pregabalin', 'Pregabalin', '1.01', 'Uống', '75mg', 'VD-19664-13', '4', 'viên', '966', '80000', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú - Việt Nam', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(238, '2020.G.517.131', 'Queitoz-200.517', 'Quetiapin', 'Quetiapin', '1.01', 'Uống', '200mg', 'VD-19667-13', '4', 'viên', '10000', '500', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú - Việt Nam', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(239, '2020.G.517.132', 'Queitoz-50.517', 'Quetiapin', 'Quetiapin', '1.01', 'Uống', '50mg', 'VD-20077-13', '4', 'viên', '7000', '500', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú - Việt Nam', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(240, '2020.G.517.133', 'Lakcay.517', 'Raloxifen', 'Raloxifen', '1.01', 'Uống', '60mg', 'VD-28470-17', '4', 'viên', '2730', '20000', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú - Việt Nam', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(241, '2020.G.517.134', 'Hemafolic.517', 'Sắt (III) hydroxyd polymaltose +acid folic', 'Sắt (III) hydroxyd polymaltose +acid folic', '1.01', 'Uống', '50mg + 0,5mg', 'VD-25593-16', '4', 'ống', '4250', '10000', 'Công ty Cổ phần Dược phẩm 2/9 TP HCM - Việt Nam', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(242, '2020.G.517.135', 'Tarfloz.517', 'Sắt fumarat', 'Sắt fumarat', '1.01', 'Uống', '300mg', 'VN-17401-13', '5', 'viên', '3500', '30000', 'Celogen Pharma Pvt., Ltd. - India', 'India', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(243, '2020.G.517.136', 'Nasrix.517', 'Simvastatin + ezetimibe', 'Simvastatin + ezetimibe', '1.01', 'Uống', '20mg + 10mg', 'VD-28475-17', '4', 'viên', '3060', '50000', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú - Việt Nam', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(244, '2020.G.517.137', 'Gellux.517', 'Sucralfat', 'Sucralfat', '1.01', 'Uống', '1g/15g', 'VD-27438-17', '4', 'gói', '3500', '100000', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú - Việt Nam', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(245, '2020.G.517.138', 'Thuốc mỡ Tacropic.517', 'Tacrolimus', 'Tacrolimus', '3.05', 'Dùng ngoài', '10mg/10g', 'VD-20364-13', '4', 'tuýp', '106810', '200', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú - Việt Nam', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(246, '2020.G.517.139', 'Katies.517', 'Tiropramid hydroclorid', 'Tiropramid hydroclorid', '1.01', 'Uống', '100mg', 'VD-19170-13', '4', 'viên', '1600', '100000', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú - Việt Nam', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(247, '2020.G.517.140', 'Amriamid 200.517', 'Amisulprid', 'Amisulprid', '1.01', 'Uống', '200mg', 'VD-31566-19', '4', 'Viên', '4935', '500', 'CN Cty CP DP Agimexpharm - NM SX DP Agimexpharm - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Ba Đình', '517/QĐ-BV', '20200414', NULL, NULL),
(248, '2020.G.517.141', 'Cloramphenicol 0,4%.517', 'Cloramphenicol', 'Cloramphenicol', '6.01', 'Nhỏ mắt', '0,4%', 'VD-29945-18', '4', 'Lọ', '13482', '5000', 'Cty CP Dược VTYT Hải Dương (Hdpharma) - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Ba Đình', '517/QĐ-BV', '20200414', NULL, NULL),
(249, '2020.G.517.142', 'Ketovazol 2%.517', 'Ketoconazol', 'Ketoconazol', '3.05', 'Dùng ngoài', '0,1g (2%/5g)', 'VD-18694-13', '4', 'Tuýp', '3549', '1000', 'Cty CP DP Agimexpharm - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Ba Đình', '517/QĐ-BV', '20200414', NULL, NULL),
(250, '2020.G.517.143', 'Idomagi.517', 'Linezolid*', 'Linezolid*', '1.01', 'Uống', '600mg', 'VD-30280-18', '4', 'Viên', '14490', '4000', 'CN Cty CP DP Agimexpharm - NM SX DP Agimexpharm - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Ba Đình', '517/QĐ-BV', '20200414', NULL, NULL),
(251, '2020.G.517.144', 'Agimycob.517', 'Metronidazol + neomycin + nystatin', 'Metronidazol + neomycin + nystatin', '4.01', 'Đặt âm đạo', '500mg + 65.000IU + 100.000IU', 'VD-29657-18', '4', 'viên', '1680', '5000', 'CN Cty CP DP Agimexpharm - NM SX DP Agimexpharm - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Ba Đình', '517/QĐ-BV', '20200414', NULL, NULL),
(252, '2020.G.517.145', 'Propylthiouracil.517', 'Propylthiouracil (PTU)', 'Propylthiouracil (PTU)', '1.01', 'Uống', '50mg', 'VD-28325-17', '4', 'Viên', '315', '10000', 'Cty CP SH DP Ba Đình - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Ba Đình', '517/QĐ-BV', '20200414', NULL, NULL),
(253, '2020.G.517.146', 'Agitritine 200.517', 'Trimebutin maleat', 'Trimebutin maleat', '1.01', 'Uống', '200mg', 'VD-13753-11', '4', 'Viên', '630', '100000', 'CN Cty CP DP Agimexpharm - NM SX DP Agimexpharm - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Ba Đình', '517/QĐ-BV', '20200414', NULL, NULL),
(254, '2020.G.517.147', 'Chamcromus 0,03%.517', 'Tacrolimus', 'Tacrolimus', '3.05', 'Dùng ngoài', '3mg', 'VD-26293-17', '4', 'Tuýp', '84000', '200', 'Dopharma - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Bách Việt', '517/QĐ-BV', '20200414', NULL, NULL),
(255, '2020.G.517.148', 'Chamcromus 0,1%.517', 'Tacrolimus', 'Tacrolimus', '3.05', 'Dùng ngoài', '10mg', 'VD-26294-17', '4', 'Tuýp', '88000', '200', 'Dopharma - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Bách Việt', '517/QĐ-BV', '20200414', NULL, NULL),
(256, '2020.G.517.149', 'SaVi Valsartan Plus HCT 80/12.5.517', 'Valsartan + hydroclorothiazid', 'Valsartan + hydroclorothiazid', '1.01', 'Uống', '80mg + 12,5mg', 'VD-23010-15', '2', 'Viên', '5800', '50000', 'Savipharm - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Bách Việt', '517/QĐ-BV', '20200414', NULL, NULL),
(257, '2020.G.517.150', 'Folihem.517', 'Sắt fumarat + acid folic', 'Sắt fumarat + acid folic', '1.01', 'Uống', '310mg + 0,35mg', 'VN-19441-15', '1', 'Viên', '2250', '200000', 'Remedica Ltd. - Cyprus', 'cyprus', 'Công ty TNHH Dược phẩm Gia Minh', '517/QĐ-BV', '20200414', NULL, NULL),
(258, '2020.G.517.153', 'Bidilucil 500.517', 'Meclophenoxat', 'Meclophenoxat', '2.10', 'Tiêm', '500 mg', 'VD-20667-14', '4', 'Lọ', '58000', '15000', 'Bidiphar 1 - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Hồng Đức Việt', '517/QĐ-BV', '20200414', NULL, NULL),
(259, '2020.G.517.154', 'Nadaxena.517', 'Naproxen', 'Naproxen', '1.01', 'Uống', '500mg', 'VN-21927-19', '1', 'Viên', '9600', '10', 'Pabianickie Zaklady Farmaceutyczne Polfa S.A.-Poland', '', 'Công ty TNHH Dược phẩm Huy Cường', '517/QĐ-BV', '20200414', NULL, NULL),
(260, '2020.G.517.156', 'Idatril 5mg.517', 'Imidapril', 'Imidapril', '1.01', 'Uống', '5mg', 'VD-18550-13', '3', 'Viên', '3700', '50000', 'Công ty Cổ phần Dược phẩm và Sinh học y tế - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Kim Phúc', '517/QĐ-BV', '20200414', NULL, NULL),
(261, '2020.G.517.157', 'Actelsar HCT 40mg/12,5mg.517', 'Telmisartan + hydroclorothiazid', 'Telmisartan + hydroclorothiazid', '1.01', 'Uống', '40mg + 12,5mg', 'VN-21654-19', '1', 'Viên', '9600', '100000', 'Actavis Ltd. - Malta', 'Malta', 'Công ty TNHH Dược phẩm Kim Phúc', '517/QĐ-BV', '20200414', NULL, NULL),
(262, '2020.G.517.158', 'Deplin.517', 'Acid thioctic/ Meglumin thioctat', 'Acid thioctic/ Meglumin thioctat', '2.10', 'Tiêm', '600mg', 'VN-16995-13', '2', 'ống', '186000', '2500', 'Solupharm Pharmazeutische Erzeugnisse GmbH - Germany', 'Đức', 'Công ty TNHH Dược phẩm Mai Linh', '517/QĐ-BV', '20200414', NULL, NULL),
(263, '2020.G.517.159', 'Predegyl.517', 'Econazol', 'Econazol', '4.01', 'Đặt âm đạo', '150mg', 'VD-20818-14', '4', 'Viên', '11000', '5000', 'Công ty CP DP Sao Kim', 'Việt Nam', 'Công ty TNHH Dược phẩm Mai Linh', '517/QĐ-BV', '20200414', NULL, NULL),
(264, '2020.G.517.160', 'Gluthion .517', 'Glutathion', 'Glutathion', '2.10', 'Tiêm', '600mg', '15595/QLD-KD', '1', 'lọ', '126000', '3000', '1-Laboratorio Italiano Biochimico Farmaceutico Lisapharma S.P.A, Italy 2-Laboratorio Farmaceutico C.T.S.R.L, Italy', 'Italy', 'Công ty TNHH Dược phẩm Mai Linh', '517/QĐ-BV', '20200414', NULL, NULL),
(265, '2020.G.517.161', 'Kalium Chloratum 500mg, .517', 'Kali clorid', 'Kali clorid', '1.01', 'Uống', '500mg', 'VN-14110-11', '1', 'Viên', '1500', '150000', 'Biomedica Spol. S.r.o, CH Séc', 'Cộng hòa séc', 'Công ty TNHH Dược phẩm Mai Linh', '517/QĐ-BV', '20200414', NULL, NULL),
(266, '2020.G.517.162', 'Azilyo.517', 'Azithromycin', 'Azithromycin', '1.01', 'Uống', '500mg', 'VD-28855-18', '4', 'Lọ', '94920', '5000', 'Công ty Cổ phần Dược phẩm An Thiên - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Tân An', '517/QĐ-BV', '20200414', NULL, NULL),
(267, '2020.G.517.163', 'Mezaterol 20.517', 'Bambuterol', 'Bambuterol', '1.01', 'Uống', '20mg', 'VD-25696-16', '4', 'Viên', '1995', '30000', 'Công ty Cổ phần dược phẩm Hà Tây - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Tân An', '517/QĐ-BV', '20200414', NULL, NULL),
(268, '2020.G.517.164', 'Mezapentin 600.517', 'Gabapentin', 'Gabapentin', '1.01', 'Uống', '600mg', 'VD-27886-17', '4', 'Viên', '2499', '50000', 'Công ty Cổ phần dược phẩm Hà Tây - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Tân An', '517/QĐ-BV', '20200414', NULL, NULL),
(269, '2020.G.517.165', 'Disthyrox.517', 'Levothyroxin (muối natri)', 'Levothyroxin (muối natri)', '1.01', 'Uống', '100mcg', 'VD-21846-14', '4', 'Viên', '294', '70000', 'Công ty Cổ phần dược phẩm Hà Tây - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Tân An', '517/QĐ-BV', '20200414', NULL, NULL),
(270, '2020.G.517.166', 'Pomatat.517', 'Magnesi aspartat+ kali aspartat', 'Magnesi aspartat+ kali aspartat', '1.01', 'Uống', '140mg + 158mg', 'VD-22155-15', '4', 'Viên', '1050', '100000', 'Công ty Cổ phần dược phẩm Hà Tây - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Tân An', '517/QĐ-BV', '20200414', NULL, NULL),
(271, '2020.G.517.167', 'A.T Mequitazine.517', 'Mequitazin', 'Mequitazin', '1.01', 'Uống', '5mg', 'VD-32792-19', '4', 'Viên', '1344', '100000', 'Công ty cổ phần dược phẩm An Thiên - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Tân An', '517/QĐ-BV', '20200414', NULL, NULL),
(272, '2020.G.517.168', 'Naftizine.517', 'Naftidrofuryl', 'Naftidrofuryl', '1.01', 'Uống', '200mg', 'VD-25512-16', '4', 'Viên', '4410', '150000', 'Công ty Cổ phần dược phẩm Me Di Sun - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Tân An', '517/QĐ-BV', '20200414', NULL, NULL),
(273, '2020.G.517.169', 'Pecrandil 10.517', 'Nicorandil', 'Nicorandil', '1.01', 'Uống', '10mg', 'VD-30394-18', '4', 'Viên', '3990', '80000', 'Công ty Cổ phần dược phẩm Hà Tây - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Tân An', '517/QĐ-BV', '20200414', NULL, NULL),
(274, '2020.G.517.170', 'Hypravas 40.517', 'Pravastatin', 'Pravastatin', '1.01', 'Uống', '40mg', 'VD-31763-19', '4', 'Viên', '4300', '100000', 'Công ty Cổ phần dược phẩm Me Di Sun - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Tân An', '517/QĐ-BV', '20200414', NULL, NULL),
(275, '2020.G.517.171', 'Basethyrox.517', 'Propylthiouracil', 'Propylthiouracil', '1.01', 'Uống', '100mg', 'VD-21287-14', '4', 'Viên', '735', '10000', 'Công ty Cổ phần dược phẩm Hà Tây - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Tân An', '517/QĐ-BV', '20200414', NULL, NULL),
(276, '2020.G.517.172', 'Beynit 2.5.517', 'Ramipril', 'Ramipril', '1.01', 'Uống', '2,5mg', 'VD-33470-19', '4', 'Viên', '2583', '150000', 'Công ty Cổ phần dược phẩm Me Di Sun - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Tân An', '517/QĐ-BV', '20200414', NULL, NULL),
(277, '2020.G.517.173', 'Mezapid.517', 'Rebamipid', 'Rebamipid', '1.01', 'Uống', '100mg', 'VD-26149-17', '4', 'Viên', '924', '150000', 'Công ty Cổ phần dược phẩm Hà Tây -Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Tân An', '517/QĐ-BV', '20200414', NULL, NULL),
(278, '2020.G.517.174', 'Mezamazol.517', 'Thiamazol', 'Thiamazol', '1.01', 'Uống', '5mg', 'VD-21298-14', '4', 'Viên', '588', '20000', 'Công ty Cổ phần dược phẩm Hà Tây - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Tân An', '517/QĐ-BV', '20200414', NULL, NULL),
(279, '2020.G.517.175', 'Cisteine 250.517', 'Carbocistein', 'Carbocistein', '1.01', 'Uống', '250 mg/ 5 ml', 'VD-26027-16', '4', 'Chai', '28000', '5000', 'Công ty TNHH Thai Nakorn Patana - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm và trang thiết bị y tế Hoàng Đức', '517/QĐ-BV', '20200414', NULL, NULL),
(280, '2020.G.517.176', 'Liposic.517', 'Carbomer', 'Carbomer', '6.01', 'Nhỏ mắt', '0,2% (2mg/g)', 'VN-15471-12', '1', 'Tuýp', '56000', '5000', 'Dr. Gerhard Mann Chem.- Pharm. Fabrik GmbH - Đức', 'Đức', 'Công ty TNHH Dược phẩm và trang thiết bị y tế Hoàng Đức', '517/QĐ-BV', '20200414', NULL, NULL),
(281, '2020.G.517.177', 'CANASONE C.B.517', 'Clotrimazol + betamethason', 'Clotrimazol + betamethason', '3.05', 'Dùng ngoài', '0,1 g/100g 1 g/100g', 'VD-18593-13', '4', 'Tuýp', '15000', '5000', 'Công ty TNHH Thai Nakorn Patana - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm và trang thiết bị y tế Hoàng Đức', '517/QĐ-BV', '20200414', NULL, NULL),
(282, '2020.G.517.178', 'Indocollyre.517', 'Indomethacin', 'Indomethacin', '6.01', 'Nhỏ mắt', '0,1%', 'VN-12548-11', '1', 'Lọ', '68000', '5000', 'Laboratoire Chauvin - Pháp', 'Pháp', 'Công ty TNHH Dược phẩm và trang thiết bị y tế Hoàng Đức', '517/QĐ-BV', '20200414', NULL, NULL),
(283, '2020.G.517.179', 'DEBBY.517', 'Nifuroxazid', 'Nifuroxazid', '1.01', 'Uống', '218 mg/5ml', 'VD-24652-16', '4', 'Chai', '17000', '5000', 'Công ty TNHH Thai Nakorn Patana - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm và trang thiết bị y tế Hoàng Đức', '517/QĐ-BV', '20200414', NULL, NULL),
(284, '2020.G.517.180', 'Sara.517', 'Paracetamol', 'Paracetamol', '1.01', 'Uống', '120mg/5ml', 'VD-29552-18', '4', 'Chai', '18000', '5000', 'Công ty TNHH Thai Nakorn Patana - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm và trang thiết bị y tế Hoàng Đức', '517/QĐ-BV', '20200414', NULL, NULL),
(285, '2020.G.517.181', 'Sara for children.517', 'Paracetamol', 'Paracetamol', '1.01', 'Uống', '250mg/5ml', 'VD-28619-17', '4', 'chai', '23500', '8000', 'Công ty TNHH Thai Nakorn Patana - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm và trang thiết bị y tế Hoàng Đức', '517/QĐ-BV', '20200414', NULL, NULL),
(286, '2020.G.517.182', 'COLATUS.517', 'Paracetamol + chlorpheniramin + phenylephrine + dextromethorphan', 'Paracetamol + chlorpheniramin + phenylephrine + dextromethorphan', '1.01', 'Uống', '120 mg/5ml 1 mg/5ml 2,5 mg/5ml 7,5 mg/5ml', 'VD-25515-16', '4', 'Chai', '20000', '5000', 'Công ty TNHH Thai Nakorn Patana - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm và trang thiết bị y tế Hoàng Đức', '517/QĐ-BV', '20200414', NULL, NULL),
(287, '2020.G.517.183', 'Dorithricin.517', 'Tyrothricin + benzocain+ benzalkonium', 'Tyrothricin + benzocain+ benzalkonium', '1.02', 'Ngậm', '(0,5+1+1,5)mg', 'VN-20293-17', '1', 'Viên', '2200', '50000', 'Medice Arzneimittel Putter GmbH & Co.KG - Đức', 'ĐỨc', 'Công ty TNHH Dược phẩm và trang thiết bị y tế Hoàng Đức', '517/QĐ-BV', '20200414', NULL, NULL),
(288, '2020.G.517.184', 'Vancomycin hydrochloride for infusion.517', 'Vancomycin', 'Vancomycin', '2.10', 'Tiêm', '1000mg', 'VN-19885-16', '1', 'Lọ', '89500', '2000', 'Xellia Pharmaceuticals ApS - Đan Mạch', 'Đan mạch', 'Công ty TNHH Dược phẩm Việt-Pháp', '517/QĐ-BV', '20200414', NULL, NULL),
(289, '2020.G.517.185', 'TP Natri clorid 0,9%.517', 'Natri clorid', 'Natri clorid', '2.15', 'Tiêm truyền', '0,9%/ 500ml', 'VD-31909-19', '4', 'Chai', '6250', '10000', 'Công ty cổ phần dược phẩm Thành Phát - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Xuân Hòa', '517/QĐ-BV', '20200414', NULL, NULL),
(290, '2020.G.517.186', 'Rotundin.517', 'Rotundin', 'Rotundin', '1.01', 'Uống', '30mg', 'VD-30855-18', '4', 'Viên', '318', '200000', 'Công ty TNHH MTV 120 Armephaco - Việt Nam', 'Việt Nam', 'Công ty TNHH Dược phẩm Xuân Hòa', '517/QĐ-BV', '20200414', NULL, NULL),
(291, '2020.G.517.187', 'Reamberin.517', 'Meglumin natri succinat ', 'Meglumin natri succinat ', '2.15', 'Tiêm truyền', '1,5%-400ml', 'VN-19527-15', '5', 'Chai', '151950', '1550', 'Scientific Technological Pharmaceutical Firm \"Polysan\", Ltd. - CHLB Nga', 'CHKB Nga', 'Công ty TNHH Dược phẩm Thống Nhất', '517/QĐ-BV', '20200414', NULL, NULL),
(292, '2020.G.517.189', 'Digoxin/ Anfarm.517', 'Digoxin', 'Digoxin', '2.10', 'Tiêm', '0,5 mg/2ml', 'VN-21737-19', '1', 'Ống', '22500', '500', 'Anfarm Hellas S.A. - Greece', 'Greece', 'Công ty TNHH Đầu tư phát triển Hưng Thành', '517/QĐ-BV', '20200414', NULL, NULL),
(293, '2020.G.517.190', 'Laevolac.517', 'Lactulose', 'Lactulose', '1.01', 'Uống', '10g/15ml', 'VN-19613-16', '1', 'Gói', '2700', '10000', 'Fresenius Kabi Austria GmbH - Austria', 'Austria', 'Công ty TNHH Đầu tư phát triển Hưng Thành', '517/QĐ-BV', '20200414', NULL, NULL),
(294, '2020.G.517.191', 'Mibelcam 15mg/1,5ml.517', 'Meloxicam', 'Meloxicam', '2.10', 'Tiêm', '15mg/1,5ml', 'VN-16455-13', '2', 'Ống', '20500', '10000', 'Idol Ilac Dolum Sanayii Ve Ticaret A.S - Turkey', 'Turkey', 'Công ty TNHH Đầu tư phát triển Hưng Thành', '517/QĐ-BV', '20200414', NULL, NULL),
(295, '2020.G.517.192', 'Pulmicort Respules.517', 'Budesonid', 'Budesonid', '5.05', 'Khí dung', '0,5mg/ml', 'VN-21666-19', '1', 'Ống', '24906', '8000', 'AstraZeneca AB-Thụy Điển', 'Thụy điển', 'Công ty TNHH MTV Dược liệu TW2', '517/QĐ-BV', '20200414', NULL, NULL),
(296, '2020.G.517.193', 'Forxiga.517', 'Dapagliflozin', 'Dapagliflozin', '1.01', 'Uống', '10mg', 'VN3-37-18', '1', 'Viên', '19000', '5000', 'AstraZeneca Pharmaceuticals LP; đóng gói AstraZeneca UK Limited-CSSX: Mỹ, đóng gói: Anh', 'Anh', 'Công ty TNHH MTV Dược liệu TW2', '517/QĐ-BV', '20200414', NULL, NULL),
(297, '2020.G.517.194', 'Daflon.517', 'Diosmin + hesperidin', 'Diosmin + hesperidin', '1.01', 'Uống', '450mg; 50mg', 'VN-15519-12', '1', 'Viên', '3258', '10000', 'Les Laboratoires Servier Industrie-Pháp', 'Pháp', 'Công ty TNHH MTV Dược liệu TW2', '517/QĐ-BV', '20200414', NULL, NULL),
(298, '2020.G.517.195', 'Geloplasma.517', 'Gelatin succinyl + natri clorid +natri hydroxyd', 'Gelatin succinyl + natri clorid +natri hydroxyd', '2.15', 'Tiêm truyền', '500ml', 'VN-19838-16', '1', 'Túi', '110000', '2000', 'Fresenius Kabi France-Pháp', 'Pháp', 'Công ty TNHH MTV Dược liệu TW2', '517/QĐ-BV', '20200414', NULL, NULL),
(299, '2020.G.517.197', 'Lipovenoes 10% PLR.517', 'Nhũ dịch lipid', 'Nhũ dịch lipid', '2.15', 'Tiêm truyền', '10%, 250ml', 'VN-17439-13', '1', 'Chai', '90500', '3300', 'Fresenius Kabi Austria GmbH-Áo', 'Áo', 'Công ty TNHH MTV Dược liệu TW2', '517/QĐ-BV', '20200414', NULL, NULL),
(300, '2020.G.517.199', 'Coveram 5mg/5mg.517', 'Perindopril + amlodipin', 'Perindopril + amlodipin', '1.01', 'Uống', '5mg; 5mg', 'VN-18635-15', '1', 'Viên', '6589', '20000', 'Servier Ireland Industries Ltd-Ailen', 'Ailen', 'Công ty TNHH MTV Dược liệu TW2', '517/QĐ-BV', '20200414', NULL, NULL),
(301, '2020.G.517.200', 'Viacoram 3.5mg/2.5mg.517', 'Perindopril + amlodipin', 'Perindopril + amlodipin', '1.01', 'Uống', '3,5mg; 2,5mg', 'VN3-46-18', '1', 'Viên', '5960', '10000', 'Servier (Ireland) Industries Ltd-Ailen', 'Ailen', 'Công ty TNHH MTV Dược liệu TW2', '517/QĐ-BV', '20200414', NULL, NULL),
(302, '2020.G.517.201', 'TRIPLIXAM 5mg/1.25mg/5mg.517', 'Amlodipin + indapamid + perindopril', 'Amlodipin + indapamid + perindopril', '1.01', 'Uống', '5mg; 1,25mg; 5mg', 'VN3-11-17', '1', 'Viên', '8557', '20000', 'Servier (Ireland) Industries Ltd-Ailen', 'Ailen', 'Công ty TNHH MTV Dược liệu TW2', '517/QĐ-BV', '20200414', NULL, NULL),
(303, '2020.G.517.202', 'Fresofol 1% Mct/Lct.517', 'Propofol', 'Propofol', '2.10', 'Tiêm', '1%, 20ml', 'VN-17438-13', '1', 'Ống', '28500', '4000', 'Fresenius Kabi Austria GmbH-Áo', 'Áo', 'Công ty TNHH MTV Dược liệu TW2', '517/QĐ-BV', '20200414', NULL, NULL),
(304, '2020.G.517.203', 'Rocuronium Kabi 10mg/ml.517', 'Rocuronium bromid', 'Rocuronium bromid', '2.10', 'Tiêm', '10mg/ml', 'VN-18303-14', '1', 'Lọ', '49800', '5000', 'Fresenius Kabi Austria GmbH-Áo', 'Áo', 'Công ty TNHH MTV Dược liệu TW2', '517/QĐ-BV', '20200414', NULL, NULL),
(305, '2020.G.517.204', 'Brilinta.517', 'Ticagrelor', 'Ticagrelor', '1.01', 'Uống', '90mg', 'VN-19006-15', '1', 'Viên', '15873', '2000', 'AstraZeneca AB-Thụy Điển', 'Thụy điển', 'Công ty TNHH MTV Dược liệu TW2', '517/QĐ-BV', '20200414', NULL, NULL),
(306, '2020.G.517.206', 'Aceronko 4.517', 'Acenocoumarol', 'Acenocoumarol', '1.01', 'Uống', '4mg', 'VD-20825-14', '4', 'Viên', '820', '16000', 'Công ty CPDP TW 1 - Pharbaco', 'Việt Nam', 'Công ty TNHH MTV Dược Sài Gòn', '517/QĐ-BV', '20200414', NULL, NULL),
(307, '2020.G.517.207', 'Combilipid MCT Peri injection.517', 'Acid amin + glucose + lipid (*)', 'Acid amin + glucose + lipid (*)', '2.15', 'Tiêm truyền', '8% 150ml + 16% 150ml + 20% 75ml/ 375ml', 'VN-21297-18', '2', 'Túi', '560000', '1500', 'JW Life Science Corporation - Hàn Quốc', 'Korea', 'Công ty TNHH MTV Dược Sài Gòn', '517/QĐ-BV', '20200414', NULL, NULL),
(308, '2020.G.517.208', 'CKDKmoxilin Dry Syrup 7:1.517', 'Amoxicilin + acid clavulanic', 'Amoxicilin + acid clavulanic', '2.10', 'Tiêm', '2000mg + 285mg', 'VN-19576-16', '2', 'Lọ', '115000', '10', 'Chong Kun Dang Pharmaceutical Corp - Hàn Quốc', 'Korea', 'Công ty TNHH MTV Dược Sài Gòn', '517/QĐ-BV', '20200414', NULL, NULL),
(309, '2020.G.517.209', 'Rinedif 300 Cap.517', 'Cefdinir', 'Cefdinir', '1.01', 'Uống', '300mg', 'VD-33799-19', '4', 'Viên', '2513', '30000', 'Công ty cổ phần Trust Farma Quốc tế - Việt Nam', 'Việt Nam', 'Công ty TNHH MTV Dược Sài Gòn', '517/QĐ-BV', '20200414', NULL, NULL),
(310, '2020.G.517.213', 'Duphalac.517', 'Lactulose', 'Lactulose', '1.01', 'Uống', '10g/15ml', 'VN-20896-18', '1', 'Chai', '86000', '2000', 'Abbott Biologicals B.V - Hà Lan', 'Hà lan', 'Công ty TNHH MTV Vimedimex Bình Dương', '517/QĐ-BV', '20200414', NULL, NULL),
(311, '2020.G.517.214', 'Tanganil 500mg.517', 'Acetyl leucin', 'Acetyl leucin', '2.10', 'Tiêm', '500mg/5ml', 'VN-18066-14', '1', 'Ống', '14368', '40000', 'Pierre Fabre Medicament production - Pháp', 'Pháp', 'Công ty TNHH MTV Vimedimex Bình Dương', '517/QĐ-BV', '20200414', NULL, NULL),
(312, '2020.G.517.215', 'Tanganil 500mg.517', 'Acetyl leucin', 'Acetyl leucin', '1.01', 'Uống', '500mg', 'VN-15590-12', '1', 'Viên', '4612', '50000', 'Pierre Fabre Medicament production - Pháp', 'Pháp', 'Công ty TNHH MTV Vimedimex Bình Dương', '517/QĐ-BV', '20200414', NULL, NULL),
(313, '2020.G.517.240', 'Paracetamol 1g/10ml.517', 'Paracetamol', 'Paracetamol', '2.10', 'Tiêm', '1g/10ml', 'VD-26906-17', '4', 'Ống', '18000', '5000', 'Công ty CPDP Minh Dân - Việt Nam', 'Việt Nam', 'Liên danh nhà thầu Công ty Cổ phần Thương Mại Minh Dân và công ty Cổ phần Dược phẩm Minh Dân', '517/QĐ-BV', '20200414', NULL, NULL),
(314, '2020.G.517.210', 'Fentanyl - Hameln 2ml.517', 'Fentanyl', 'Fentanyl', '2.10', 'Tiêm', '0,1mg/2ml', 'VN-17326-13', '1', 'Ống', '11800', '10000', 'Siegfried Hameln GmbH - Đức', 'Đức', 'Công ty TNHH MTV Dược Sài Gòn', '517/QĐ-BV', '20200414', NULL, NULL),
(315, '2020.G.517.211', 'Métforilex MR.517', 'Metformin', 'Metformin', '1.01', 'Uống', '500mg', 'VD-28743-18', '4', 'Viên', '1200', '200000', 'Chi nhánh Công ty CP Armephaco - Xí nghiệp dược phẩm 150 - Việt Nam', 'Việt Nam', 'Công ty TNHH MTV Dược Sài Gòn', '517/QĐ-BV', '20200414', NULL, NULL),
(316, '2020.G.517.216', 'Creon® 25000.517', 'Amylase + lipase + protease', 'Amylase + lipase + protease', '1.01', 'Uống', '300mg', 'QLSP-0700-13', '1', 'Viên', '13703', '10000', 'Abbott Laboratories GmbH - Đức', 'Đức', 'Công ty TNHH MTV Vimedimex Bình Dương', '517/QĐ-BV', '20200414', NULL, NULL),
(317, '2020.G.517.237', 'Cefodomid 200.517', 'Cefpodoxim', 'Cefpodoxim', '1.01', 'Uống', '200mg', 'VD-24228-16', '4', 'Viên', '1715', '10', 'Công ty CPDP Minh Dân - Việt Nam', 'Việt Nam', 'Liên danh nhà thầu Công ty Cổ phần Thương Mại Minh Dân và công ty Cổ phần Dược phẩm Minh Dân', '517/QĐ-BV', '20200414', NULL, NULL),
(318, '2020.G.517.224', 'Theostat L.P 300mg.517', 'Theophylin', 'Theophylin', '1.01', 'Uống', '300mg', 'VN-14794-12', '1', 'Viên', '2812', '2000', 'Pierre Fabre Medicament production - Pháp', 'Pháp', 'Công ty TNHH MTV Vimedimex Bình Dương', '517/QĐ-BV', '20200414', NULL, NULL),
(319, '2020.G.517.225', 'Prismasol B0.517', 'Dung dịch lọc máu liên tục (có hoặc không có chống đông bằng citrat; có hoặc không có chứa lactat)', 'Dung dịch lọc máu liên tục (có hoặc không có chống đông bằng citrat; có hoặc không có chứa lactat)', '2.15', 'Tiêm truyền', 'Khoang A: Mỗi 1000ml chứa: Calcium clorid dihydrat 5,145g; Magnesium clorid hexahydrat: 2,033g; Aci', 'VN-21678-19', '2', 'Túi', '700000', '2000', 'Bieffe Medital S.p.A - Ý', 'Ý', 'Công ty TNHH THIẾt bị Y tế Phương Đông', '517/QĐ-BV', '20200414', NULL, NULL),
(320, '2020.G.517.227', 'Cefamandol 1g.517', 'Cefamandol', 'Cefamandol', '2.10', 'Tiêm', '1g', 'VD-31706-19', '2', 'Lọ', '63000', '10000', 'Công ty CPDP Imexpharm - Việt Nam', 'Việt Nam', 'Công ty TNHH Thương mại dược phẩm Thanh Phương', '517/QĐ-BV', '20200414', NULL, NULL),
(321, '2020.G.517.228', 'Talliton.517', 'Carvedilol', 'Carvedilol', '1.01', 'Uống', '6,25mg', 'VN-19942-16', '1', 'Viên', '2877', '50000', 'Egis Pharmaceuticals Private Limited Company - Hungary', 'Hungary', 'Công ty TNHH Thương Mại Nam Đồng', '517/QĐ-BV', '20200414', NULL, NULL),
(322, '2020.G.517.229', 'Schaaf.517', 'Doxazosin', 'Doxazosin', '1.01', 'Uống', '2mg', 'VD-30348-18', '4', 'Viên', '4200', '100000', 'Công ty CP Dược Phẩm Đạt Vi Phú - Việt Nam', 'Việt Nam', 'Công ty TNHH Thương Mại Nam Đồng', '517/QĐ-BV', '20200414', NULL, NULL),
(323, '2020.G.517.230', 'Donox 20mg.517', 'Isosorbid (dinitrat hoặcmononitrat)', 'Isosorbid (dinitrat hoặcmononitrat)', '1.01', 'Uống', '20mg', 'VD-29396-18', '4', 'Viên', '1450', '200000', 'Công ty CP XNK Y tế Domesco - Việt Nam', 'Việt Nam', 'Công ty TNHH Thương Mại Nam Đồng', '517/QĐ-BV', '20200414', NULL, NULL),
(324, '2020.G.517.231', 'Mecolzine.517', 'Mesalamin', 'Mesalamin', '1.01', 'Uống', '500mg', '22598/QLD-KD', '1', 'Viên', '9200', '30000', 'Faes Farma, S.A - Tây Ban Nha', 'Tây Ban Nha', 'Công ty TNHH Thương Mại Nam Đồng', '517/QĐ-BV', '20200414', NULL, NULL),
(325, '2020.G.517.232', 'Asentra 50mg.517', 'Sertralin', 'Sertralin', '1.01', 'Uống', '50mg', 'VN-19911-16', '1', 'Viên', '8600', '20000', 'KRKA, d.d., Novo mesto - Slovenia', 'Slovenia', 'Công ty TNHH Thương Mại Nam Đồng', '517/QĐ-BV', '20200414', NULL, NULL),
(326, '2020.G.517.233', 'Postcare 100.517', 'Progesteron', 'Progesteron', '1.01', 'Uống', '100mg', 'VD-24359-16', '4', 'Viên', '5300', '10000', 'Mediplantex. Việt Nam', 'Việt Nam', 'Công ty TNHH Thương Mại và công nghệ Hà Minh', '517/QĐ-BV', '20200414', NULL, NULL),
(327, '2020.G.517.235', 'Adrenalin 1mg/1ml.517', 'Adrenalin', 'Adrenalin', '2.10', 'Tiêm', '1mg/ 1ml', 'VD-31774-19', '4', 'Ống', '1590', '9000', 'Công ty CPDP Minh Dân - Việt Nam', 'Việt Nam', 'Liên danh nhà thầu Công ty Cổ phần Thương Mại Minh Dân và công ty Cổ phần Dược phẩm Minh Dân', '517/QĐ-BV', '20200414', NULL, NULL),
(328, '2020.G.517.236', 'Calci clorid 500mg/ 5ml.517', 'Calci clorid', 'Calci clorid', '2.10', 'Tiêm', '500mg/ 5ml', 'VD-22935-15', '4', 'Ống', '914', '2000', 'Công ty CPDP Minh Dân - Việt Nam', 'Việt Nam', 'Liên danh nhà thầu Công ty Cổ phần Thương Mại Minh Dân và công ty Cổ phần Dược phẩm Minh Dân', '517/QĐ-BV', '20200414', NULL, NULL),
(329, '2020.G.517.238', 'Dexamethason .517', 'Dexamethason', 'Dexamethason', '2.10', 'Tiêm', '4mg/ 1ml ( tương đương 3,3mg Dexamethason/ 1ml)', 'VD-25716-16', '4', 'Ống', '777', '5000', 'Công ty CPDP Minh Dân - Việt Nam', 'Việt Nam', 'Liên danh nhà thầu Công ty Cổ phần Thương Mại Minh Dân và công ty Cổ phần Dược phẩm Minh Dân', '517/QĐ-BV', '20200414', NULL, NULL),
(330, '2020.G.517.239', 'Kali clorid 500mg/ 5ml.517', 'Kali clorid', 'Kali clorid', '2.10', 'Tiêm', '500mg/ 5ml', 'VD-23599-15', '4', 'Ống', '1257', '25295', 'Công ty CPDP Minh Dân - Việt Nam', 'Việt Nam', 'Liên danh nhà thầu Công ty Cổ phần Thương Mại Minh Dân và công ty Cổ phần Dược phẩm Minh Dân', '517/QĐ-BV', '20200414', NULL, NULL),
(331, '2020.G.517.241', 'Cotrimoxazol 480mg.517', 'Sulfamethoxazol + trimethoprim', 'Sulfamethoxazol + trimethoprim', '1.01', 'Uống', '400mg + 80mg', 'VD-24799-16', '4', 'Viên', '209', '100000', 'Công ty CPDP Minh Dân - Việt Nam', 'Việt Nam', 'Liên danh nhà thầu Công ty Cổ phần Thương Mại Minh Dân và công ty Cổ phần Dược phẩm Minh Dân', '517/QĐ-BV', '20200414', NULL, NULL),
(332, '2020.G.517.205', 'Tygacil.517', 'Tigecyclin*', 'Tigecyclin*', '2.10', 'Tiêm', '50mg', 'VN-20333-17', '1', 'Lọ', '731000', '800', 'Wyeth Lederle S.r.l-Ý', 'Ý', 'Công ty TNHH MTV Dược liệu TW2', '517/QĐ-BV', '20200414', NULL, NULL),
(333, '2020.G.517.212', 'Piperacillin/Tazobactam Kabi 2g/0,25g.517', 'Piperacilin + tazobactam*', 'Piperacilin + tazobactam*', '2.10', 'Tiêm', '2g + 0,25g', 'VN-21200-18', '1', 'Lọ', '80000', '4000', 'Labesfal - Laboratórios Almiro,S.A. - Bồ Đào Nha', 'Bồ Đào nha', 'Công ty TNHH MTV Dược Sài Gòn', '517/QĐ-BV', '20200414', NULL, NULL),
(334, '2020.G.517.217', 'Betaserc 24mg.517', 'Betahistin', 'Betahistin', '1.01', 'Uống', '24mg', 'VN-21651-19', '1', 'Viên', '5962', '50000', 'Mylan Laboratories SAS - Pháp', 'Pháp', 'Công ty TNHH MTV Vimedimex Bình Dương', '517/QĐ-BV', '20200414', NULL, NULL);
INSERT INTO `medicine_searchs` (`id`, `ma_thuoc`, `ten_thuoc`, `ma_hoat_chat`, `ten_hoat_chat`, `ma_duong_dung`, `ten_duong_dung`, `ham_luong`, `so_dang_ky`, `nhom_thuoc`, `don_vi_tinh`, `don_gia`, `so_luong`, `hang_san_xuat`, `nuoc_san_xuat`, `nha_thau`, `quyet_dinh`, `cong_bo`, `created_at`, `updated_at`) VALUES
(335, '2020.G.517.218', 'Ginkor Fort.517', 'Cao ginkgo biloba+ heptaminol clohydrat+ troxerutin', 'Cao ginkgo biloba+ heptaminol clohydrat+ troxerutin', '1.01', 'Uống', '14mg + 300mg + 300mg', 'VN-16802-13', '1', 'Viên', '3238', '30000', 'Beaufour Ipsen Industrie - Pháp', 'Pháp', 'Công ty TNHH MTV Vimedimex Bình Dương', '517/QĐ-BV', '20200414', NULL, NULL),
(336, '2020.G.517.219', 'Fluomizin.517', 'Dequalinium clorid', 'Dequalinium clorid', '4.01', 'Đặt âm đạo', '10mg', 'VN-16654-13', '1', 'Viên', '19420', '5000', 'Rottendorf Pharma GmbH - Đức', 'Đức', 'Công ty TNHH MTV Vimedimex Bình Dương', '517/QĐ-BV', '20200414', NULL, NULL),
(337, '2020.G.517.220', 'Potassium Chloride Proamp 0,10g/ml.517', 'Kali clorid', 'Kali clorid', '2.10', 'Tiêm', '1g/10ml', 'VN-16303-13', '1', 'Ống', '5500', '10000', 'Laboratoire Aguettant - Pháp', 'Pháp', 'Công ty TNHH MTV Vimedimex Bình Dương', '517/QĐ-BV', '20200414', NULL, NULL),
(338, '2020.G.517.221', 'Permixon 160mg.517', 'Lipidosterol serenoarepense (Lipid-sterol của Serenoa repens)', 'Lipidosterol serenoarepense (Lipid-sterol của Serenoa repens)', '1.01', 'Uống', '160mg', 'VN-14792-12', '1', 'Viên', '7493', '10000', 'Pierre Fabre Medicament production - Pháp', 'Pháp', 'Công ty TNHH MTV Vimedimex Bình Dương', '517/QĐ-BV', '20200414', NULL, NULL),
(339, '2020.G.517.222', 'Acupan.517', 'Nefopam (hydroclorid)', 'Nefopam (hydroclorid)', '2.10', 'Tiêm', '20mg', 'VN-18589-15', '1', 'Ống', '33000', '3000', 'Delpharm Tours (xuất xưởng: Biocodex) - Pháp', 'Pháp', 'Công ty TNHH MTV Vimedimex Bình Dương', '517/QĐ-BV', '20200414', NULL, NULL),
(340, '2020.G.517.223', 'Nicardipine Aguettant 10mg/10ml.517', 'Nicardipin', 'Nicardipin', '2.10', 'Tiêm', '10mg/10ml', 'VN-19999-16', '1', 'Ống', '124999', '5000', 'Laboratoire Aguettant - Pháp', 'Pháp', 'Công ty TNHH MTV Vimedimex Bình Dương', '517/QĐ-BV', '20200414', NULL, NULL),
(341, 'TH517', 'Oresol new', 'Natri clorid + natri citrat + kali clorid + glucose khan', 'Natri clorid + natri citrat + kali clorid + glucose khan', '1.01', 'Uống', '2,7g + 0,52g + 0,3g + 0,509g', 'VD-23143-15', 'N4', 'Gói', '756', '20000', 'Bidiphar', 'Việt Nam', 'Bidiphar', '29/QĐ-BV', '20200108', NULL, NULL),
(342, 'TH515', 'Hamett', 'Diosmectit', 'Diosmectit', '1.01', 'Uống', '3g', 'VD-20555-14', 'N4', 'Gói', '1056', '5000', 'Hậu Giang', 'Việt nam', 'Công ty Cổ phần Dược Hậu Giang', '557/QĐ-BV', '20200424', NULL, NULL),
(343, 'TH515', 'Acetazolamid 250mg', 'Acetazolamid', 'Acetazolamid', '1.01', 'Uống', '250mg', 'VD-13361-10', 'N4', 'Viên', '735', '5000', 'Công ty Cổ phần DPDL Pharmedic', 'Việt Nam', 'Chi nhánh công ty TNHH MTV Dược Sài Gòn Tại Hà Nội', '557/QĐ-BV', '20200424', NULL, NULL),
(344, '2020.G.517.555', 'Noradrenaline Base Aguettant 4mg/4ml.517', 'Nor- adrenalin', 'Nor- adrenalin', '2.10', 'Tiêm', '4mg/ 4ml', 'VN-20000-16', '1', 'ống', '37650', '3000', 'Laboratoire Aguettant', 'Pháp', 'Chi nhánh công ty trách nhiệm hữu hạn MTV Vimedimex Bình Dương tại Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(345, '', 'Lyoxatin', 'Oxaliplatin', 'Oxaliplatin', '2.10', 'Tiêm', '100mg/20ml', 'VD-23141-15', '4', 'Lọ', '420000', '2', 'Công ty cổ phần Dược - Trang thiết bị y tế Bình Định (Bidiphar)', 'Việt Nam', 'Công ty cổ phần Dược - Trang thiết bị y tế Bình Định (Bidiphar)', '869/QĐ-BV', '20200625', NULL, NULL),
(346, '', 'XALVOBIN 500mg', 'Capecitabin', 'Capecitabin', '1.01', 'Uống', '500mg', 'VN-20931-18', '1', 'Viên', '38000', '120', 'Remedica Ltd - Síp', 'Síp', 'Liên danh Công ty UNI - Văn Lang', '869/QĐ-BV', '20200625', NULL, NULL),
(347, '', 'Jimenez', 'Tenofovir (TDF)', 'Tenofovir (TDF)', '1.01', 'Uống', '300mg', 'VD-30341-18', '4', 'Viên', '2100', '9990', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú', 'Việt Nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '869/QĐ-BV', '20200625', NULL, NULL),
(348, '', 'Vincomid', 'Metoclopramid', 'Metoclopramid', '2.10', 'Tiêm', '10mg/2ml', 'VD-21919-14', '4', 'Ống', '1260', '2500', 'CTCP DP Vĩnh Phúc-Việt Nam', 'Việt Nam', 'CTCP DP Vĩnh Phúc-Việt Nam', '869/QĐ-BV', '20200625', NULL, NULL),
(349, '', 'Vingomin', 'Methyl ergometrin (maleat)', 'Methyl ergometrin (maleat)', '2.10', 'Tiêm', '0,2mg/1ml', 'VD-24908-16', '4', 'Ống', '11900', '2000', 'CTCP DP Vĩnh Phúc-Việt Nam', 'Việt Nam', 'CTCP DP Vĩnh Phúc-Việt Nam', '869/QĐ-BV', '20200625', NULL, NULL),
(350, '', 'Franilax', 'Furosemid + spironolacton', 'Furosemid + spironolacton', '1.01', 'Uống', '20mg+ 50mg', 'VD-28458-17', '4', 'Viên', '2200', '50000', 'Công ty Cổ phần Dược phẩm Đạt Vi Phú', 'Việt Nam', 'Công ty TRách nhiệm Hữu hạn dịch vụ đầu tư phát triển y tế HÀ Nội', '644/QĐ-BV', '20200513', NULL, NULL),
(351, '', 'Glucofine 1000mg', 'Metformin', 'Metformin', '1.01', 'Uống', '1000mg', 'VD-33036-19', '4', 'Viên', '806', '200000', 'Công ty Cổ phần Xuất nhập khẩu y tế Domesco', 'Việt Nam', 'Công ty Cổ phần Xuất nhập khẩu y tế Domesco', '644/QĐ-BV', '20200513', NULL, NULL),
(352, '2020.G.455.37', 'Natri clorid 0,9% 1000ml.455', 'Natri clorid', 'Natri clorid', '2.10', 'Tiêm', '9g/1000ml', 'VD-25944-16', 'N4', 'Chai', '16500', '30000', 'Công ty TNHH B.Braun - Việt Nam', 'Việt Nam', 'Công ty Trách nhiệm hữu hạn dược phẩm xuân hòa', '455/QĐ-BV', '20200325', NULL, NULL),
(353, '2020.1003.TH.1', 'Metronidazol 250mg', 'Metronidazol', 'Metronidazol', '1.01', 'Uống', '250mg', 'VD-12849-10', 'N4', 'Viên', '190', '10000', 'NSX-1777/VN', 'VN', 'Công ty Cổ phần Thương mại Minh Dân', '1003/QĐ-BV', '20200708', NULL, NULL),
(354, '', 'Cardio-BFS.517', 'Propranolol (hydroclorid)', 'Propranolol (hydroclorid)', '2.10', 'Tiêm', '', 'VD-31616-19', '4', 'Ống', '42000', '50', 'Công ty Cổ phần Dược phẩm CPC1 Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược phẩm CPC1 Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(355, '', 'BFS-Cafein.517', 'Cafein (citrat)', 'Cafein (citrat)', '2.10', 'Tiêm', '', 'VD-24589-16', '4', 'Ống', '25000', '1000', 'Công ty Cổ phần Dược phẩm CPC1 Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược phẩm CPC1 Hà Nội', '517/QĐ-BV', '20200414', NULL, NULL),
(356, '2020.TDY.1029.1', 'Bá tử nhân', 'Bá tử nhân', 'Bá tử nhân', '1.01', 'Uống', '', '', 'N3', 'Gam', '609', '100000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(357, '2020.TDY.1029.2', 'Bạch linh (Phục linh, Bạch phục linh)', 'Bạch linh (Phục linh, Bạch phục linh)', 'Bạch linh (Phục linh, Bạch phục linh)', '1.01', 'Uống', '', '', 'N3', 'Gam', '174', '300000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(358, '2020.TDY.1029.3', 'Bán hạ bắc', 'Bán hạ bắc', 'Bán hạ bắc', '1.01', 'Uống', '', '', 'N3', 'Gam', '210', '30000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(359, '2020.TDY.1029.4', 'Câu đằng', 'Câu đằng', 'Câu đằng', '1.01', 'Uống', '', '', 'N3', 'Gam', '231', '20000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(360, '2020.TDY.1029.5', 'Dâm dương hoắc', 'Dâm dương hoắc', 'Dâm dương hoắc', '1.01', 'Uống', '', '', 'N3', 'Gam', '263', '10000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(361, '2020.TDY.1029.6', 'Dây đau xương', 'Dây đau xương', 'Dây đau xương', '1.01', 'Uống', '', '', 'N3', 'Gam', '53', '100000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(362, '2020.TDY.1029.7', 'Đan sâm', 'Đan sâm', 'Đan sâm', '1.01', 'Uống', '', '', 'N3', 'Gam', '210', '150000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(363, '2020.TDY.1029.8', 'Đào nhân', 'Đào nhân', 'Đào nhân', '1.01', 'Uống', '', '', 'N3', 'Gam', '452', '30000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(364, '2020.TDY.1029.9', 'Đương quy (Toàn quy)', 'Đương quy (Toàn quy)', 'Đương quy (Toàn quy)', '1.01', 'Uống', '', '', 'N3', 'Gam', '347', '500000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(365, '2020.TDY.1029.10', 'Hà thủ ô đỏ', 'Hà thủ ô đỏ', 'Hà thủ ô đỏ', '1.01', 'Uống', '', '', 'N3', 'Gam', '183', '300000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(366, '2020.TDY.1029.11', 'Hoàng kỳ (Bạch kỳ)', 'Hoàng kỳ (Bạch kỳ)', 'Hoàng kỳ (Bạch kỳ)', '1.01', 'Uống', '', '', 'N3', 'Gam', '179', '500000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(367, '2020.TDY.1029.12', 'Hoàng liên', 'Hoàng liên', 'Hoàng liên', '1.01', 'Uống', '', '', 'N3', 'Gam', '882', '20000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(368, '2020.TDY.1029.13', 'Huyền sâm', 'Huyền sâm', 'Huyền sâm', '1.01', 'Uống', '', '', 'N3', 'Gam', '189', '50000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(369, '2020.TDY.1029.14', 'Kê huyết đằng', 'Kê huyết đằng', 'Kê huyết đằng', '1.01', 'Uống', '', '', 'N3', 'Gam', '53', '50000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(370, '2020.TDY.1029.38', 'Thiên niên kiện', 'Thiên niên kiện', 'Thiên niên kiện', '1.01', 'Uống', '', '', 'N3', 'Gam', '95', '100000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(371, '2020.TDY.1029.39', 'Thổ phục linh', 'Thổ phục linh', 'Thổ phục linh', '1.01', 'Uống', '', '', 'N3', 'Gam', '126', '60000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(372, '2020.TDY.1029.40', 'Trạch tả', 'Trạch tả', 'Trạch tả', '1.01', 'Uống', '', '', 'N3', 'Gam', '105', '100000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(373, '2020.TDY.1029.41', 'Tri mẫu', 'Tri mẫu', 'Tri mẫu', '1.01', 'Uống', '', '', 'N3', 'Gam', '242', '7000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(374, '2020.TDY.1029.42', 'Tri mẫu', 'Tri mẫu', 'Tri mẫu', '1.01', 'Uống', '', '', 'N3', 'Gam', '242', '4000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(375, '2020.TDY.1029.43', 'Trư linh', 'Trư linh', 'Trư linh', '1.01', 'Uống', '', '', 'N3', 'Gam', '1481', '20000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(376, '2020.TDY.1029.44', 'Viễn chí', 'Viễn chí', 'Viễn chí', '1.01', 'Uống', '', '', 'N3', 'Gam', '893', '250000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(377, '2020.TDY.1029.45', 'Xích thược', 'Xích thược', 'Xích thược', '1.01', 'Uống', '', '', 'N3', 'Gam', '315', '80000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(378, '2020.TDY.1029.28', 'Sa sâm', 'Sa sâm', 'Sa sâm', '1.01', 'Uống', '', '', 'N3', 'Gam', '420', '50000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(379, '2020.TDY.1029.29', 'Sài hồ', 'Sài hồ', 'Sài hồ', '1.01', 'Uống', '', '', 'N3', 'Gam', '578', '250000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(380, '2020.TDY.1029.30', 'Sinh địa', 'Sinh địa', 'Sinh địa', '1.01', 'Uống', '', '', 'N3', 'Gam', '147', '30000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(381, '2020.TDY.1029.31', 'Sơn thù', 'Sơn thù', 'Sơn thù', '1.01', 'Uống', '', '', 'N3', 'Gam', '378', '80000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(382, '2020.TDY.1029.32', 'Tang ký sinh', 'Tang ký sinh', 'Tang ký sinh', '1.01', 'Uống', '', '', 'N3', 'Gam', '63', '50000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(383, '2020.TDY.1029.33', 'Táo nhân', 'Táo nhân', 'Táo nhân', '1.01', 'Uống', '', '', 'N3', 'Gam', '483', '250000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(384, '2020.TDY.1029.34', 'Tế tân', 'Tế tân', 'Tế tân', '1.01', 'Uống', '', '', 'N3', 'Gam', '525', '15000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(385, '2020.TDY.1029.35', 'Tục đoạn', 'Tục đoạn', 'Tục đoạn', '1.01', 'Uống', '', '', 'N3', 'Gam', '210', '200000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(386, '2020.TDY.1029.36', 'Thăng ma', 'Thăng ma', 'Thăng ma', '1.01', 'Uống', '', '', 'N3', 'Gam', '378', '50000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(387, '2020.TDY.1029.37', 'Thiên ma', 'Thiên ma', 'Thiên ma', '1.01', 'Uống', '', '', 'N3', 'Gam', '1056', '15000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(388, '2020.TDY.1029.15', 'Kim ngân hoa', 'Kim ngân hoa', 'Kim ngân hoa', '1.01', 'Uống', '', '', 'N3', 'Gam', '462', '100000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(389, '2020.TDY.1029.16', 'Khương hoàng/Uất kim', 'Khương hoàng/Uất kim', 'Khương hoàng/Uất kim', '1.01', 'Uống', '', '', 'N3', 'Gam', '126', '60000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(390, '2020.TDY.1029.17', 'Khương hoạt', 'Khương hoạt', 'Khương hoạt', '1.01', 'Uống', '', '', 'N3', 'Gam', '1218', '300000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(391, '2020.TDY.1029.18', 'Mạch môn', 'Mạch môn', 'Mạch môn', '1.01', 'Uống', '', '', 'N3', 'Gam', '200', '150000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(392, '2020.TDY.1029.19', 'Mẫu đơn bì', 'Mẫu đơn bì', 'Mẫu đơn bì', '1.01', 'Uống', '', '', 'N3', 'Gam', '273', '60000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(393, '2020.TDY.1029.20', 'Mộc hương', 'Mộc hương', 'Mộc hương', '1.01', 'Uống', '', '', 'N3', 'Gam', '210', '20000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(394, '2020.TDY.1029.21', 'Mộc qua', 'Mộc qua', 'Mộc qua', '1.01', 'Uống', '', '', 'N3', 'Gam', '168', '60000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(395, '2020.TDY.1029.22', 'Ngọc trúc', 'Ngọc trúc', 'Ngọc trúc', '1.01', 'Uống', '', '', 'N3', 'Gam', '420', '15000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(396, '2020.TDY.1029.23', 'Ngũ vị tử', 'Ngũ vị tử', 'Ngũ vị tử', '1.01', 'Uống', '', '', 'N3', 'Gam', '399', '30000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(397, '2020.TDY.1029.24', 'Nhân sâm', 'Nhân sâm', 'Nhân sâm', '1.01', 'Uống', '', '', 'N3', 'Gam', '2940', '10000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(398, '2020.TDY.1029.25', 'Nhục thung dung', 'Nhục thung dung', 'Nhục thung dung', '1.01', 'Uống', '', '', 'N3', 'Gam', '945', '30000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(399, '2020.TDY.1029.26', 'Phụ tử chế (Hắc phụ, Bạch phụ)', 'Phụ tử chế (Hắc phụ, Bạch phụ)', 'Phụ tử chế (Hắc phụ, Bạch phụ)', '1.01', 'Uống', '', '', 'N3', 'Gam', '525', '15000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(400, '2020.TDY.1029.27', 'Sa nhân', 'Sa nhân', 'Sa nhân', '1.01', 'Uống', '', '', 'N3', 'Gam', '525', '30000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(401, '2020.TDY.1029.46', 'Xuyên khung', 'Xuyên khung', 'Xuyên khung', '1.01', 'Uống', '', '', 'N3', 'Gam', '179', '200000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(402, '2020.TDY.1029.47', 'Ý dĩ', 'Ý dĩ', 'Ý dĩ', '1.01', 'Uống', '', '', 'N3', 'Gam', '116', '150000', 'Công ty Cổ phần Dược liệu Hà Nội', 'Việt Nam', 'Công ty Cổ phần Dược liệu Hà Nội', '1029/QĐ-BV', '20200715', NULL, NULL),
(403, '2020.TDY.1029.48', 'Ba kích', 'Ba kích', 'Ba kích', '1.01', 'Uống', '', '', 'N3', 'Gam', '483', '200000', 'Công ty Cổ phần Dược phẩm Thành Phát', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Thành Phát', '1029/QĐ-BV', '20200715', NULL, NULL),
(404, '2020.TDY.1029.49', 'Bạch thược', 'Bạch thược', 'Bạch thược', '1.01', 'Uống', '', '', 'N3', 'Gam', '157', '300000', 'Công ty Cổ phần Dược phẩm Thành Phát', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Thành Phát', '1029/QĐ-BV', '20200715', NULL, NULL),
(405, '2020.TDY.1029.50', 'Bạch truật', 'Bạch truật', 'Bạch truật', '1.01', 'Uống', '', '', 'N3', 'Gam', '167', '300000', 'Công ty Cổ phần Dược phẩm Thành Phát', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Thành Phát', '1029/QĐ-BV', '20200715', NULL, NULL),
(406, '2020.TDY.1029.52', 'Câu kỷ tử', 'Câu kỷ tử', 'Câu kỷ tử', '1.01', 'Uống', '', '', 'N3', 'Gam', '252', '250000', 'Công ty Cổ phần Dược phẩm Thành Phát', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Thành Phát', '1029/QĐ-BV', '20200715', NULL, NULL),
(407, '2020.TDY.1029.51', 'Cam thảo', 'Cam thảo', 'Cam thảo', '1.01', 'Uống', '', '', 'N3', 'Gam', '230', '200000', 'Công ty Cổ phần Dược phẩm Thành Phát', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Thành Phát', '1029/QĐ-BV', '20200715', NULL, NULL),
(408, '2020.TDY.1029.53', 'Dây gắm', 'Dây gắm', 'Dây gắm', '1.01', 'Uống', '', '', 'N3', 'Gam', '2964', '15000', 'Công ty Cổ phần Dược phẩm Thành Phát', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Thành Phát', '1029/QĐ-BV', '20200715', NULL, NULL),
(409, '2020.TDY.1029.54', 'Đại táo', 'Đại táo', 'Đại táo', '1.01', 'Uống', '', '', 'N3', 'Gam', '98', '400000', 'Công ty Cổ phần Dược phẩm Thành Phát', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Thành Phát', '1029/QĐ-BV', '20200715', NULL, NULL),
(410, '2020.TDY.1029.65', 'Thục địa', 'Thục địa', 'Thục địa', '1.01', 'Uống', '', '', 'N3', 'Gam', '147', '250000', 'Công ty Cổ phần Dược phẩm Thành Phát', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Thành Phát', '1029/QĐ-BV', '20200715', NULL, NULL),
(411, '2020.TDY.1029.66', 'Uy linh tiên', 'Uy linh tiên', 'Uy linh tiên', '1.01', 'Uống', '', '', 'N3', 'Gam', '336', '120000', 'Công ty Cổ phần Dược phẩm Thành Phát', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Thành Phát', '1029/QĐ-BV', '20200715', NULL, NULL),
(412, '2020.TDY.1029.56', 'Đỗ trọng', 'Đỗ trọng', 'Đỗ trọng', '1.01', 'Uống', '', '', 'N3', 'Gam', '136', '400000', 'Công ty Cổ phần Dược phẩm Thành Phát', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Thành Phát', '1029/QĐ-BV', '20200715', NULL, NULL),
(413, '2020.TDY.1029.57', 'Huyền hồ', 'Huyền hồ', 'Huyền hồ', '1.01', 'Uống', '', '', 'N3', 'Gam', '641', '300000', 'Công ty Cổ phần Dược phẩm Thành Phát', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Thành Phát', '1029/QĐ-BV', '20200715', NULL, NULL),
(414, '2020.TDY.1029.58', 'Hy thiêm', 'Hy thiêm', 'Hy thiêm', '1.01', 'Uống', '', '', 'N3', 'Gam', '105', '80000', 'Công ty Cổ phần Dược phẩm Thành Phát', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Thành Phát', '1029/QĐ-BV', '20200715', NULL, NULL),
(415, '2020.TDY.1029.59', 'Ích trí nhân', 'Ích trí nhân', 'Ích trí nhân', '1.01', 'Uống', '', '', 'N3', 'Gam', '508', '50000', 'Công ty Cổ phần Dược phẩm Thành Phát', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Thành Phát', '1029/QĐ-BV', '20200715', NULL, NULL),
(416, '2020.TDY.1029.60', 'Long nhãn', 'Long nhãn', 'Long nhãn', '1.01', 'Uống', '', '', 'N3', 'Gam', '252', '150000', 'Công ty Cổ phần Dược phẩm Thành Phát', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Thành Phát', '1029/QĐ-BV', '20200715', NULL, NULL),
(417, '2020.TDY.1029.61', 'Ngưu tất', 'Ngưu tất', 'Ngưu tất', '1.01', 'Uống', '', '', 'N3', 'Gam', '219', '250000', 'Công ty Cổ phần Dược phẩm Thành Phát', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Thành Phát', '1029/QĐ-BV', '20200715', NULL, NULL),
(418, '2020.TDY.1029.62', 'Phòng phong', 'Phòng phong', 'Phòng phong', '1.01', 'Uống', '', '', 'N3', 'Gam', '734', '250000', 'Công ty Cổ phần Dược phẩm Thành Phát', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Thành Phát', '1029/QĐ-BV', '20200715', NULL, NULL),
(419, '2020.TDY.1029.63', 'Quế chi', 'Quế chi', 'Quế chi', '1.01', 'Uống', '', '', 'N3', 'Gam', '63', '150000', 'Công ty Cổ phần Dược phẩm Thành Phát', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Thành Phát', '1029/QĐ-BV', '20200715', NULL, NULL),
(420, '2020.TDY.1029.64', 'Tần giao', 'Tần giao', 'Tần giao', '1.01', 'Uống', '', '', 'N3', 'Gam', '756', '150000', 'Công ty Cổ phần Dược phẩm Thành Phát', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Thành Phát', '1029/QĐ-BV', '20200715', NULL, NULL),
(421, '2020.TDY.1029.55', 'Đảng sâm', 'Đảng sâm', 'Đảng sâm', '1.01', 'Uống', '', '', 'N3', 'Gam', '409', '500000', 'Công ty Cổ phần Dược phẩm Thành Phát', 'Việt Nam', 'Công ty Cổ phần Dược phẩm Thành Phát', '1029/QĐ-BV', '20200715', NULL, NULL),
(422, '2020.TDY.1029.67', 'Bách bộ', 'Bách bộ', 'Bách bộ', '1.01', 'Uống', '', '', 'N3', 'Gam', '155', '20000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(423, '2020.TDY.1029.71', 'Bạch mao căn', 'Bạch mao căn', 'Bạch mao căn', '1.01', 'Uống', '', '', 'N3', 'Gam', '125', '15000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(424, '2020.TDY.1029.72', 'Bình vôi (Ngải tượng)', 'Bình vôi (Ngải tượng)', 'Bình vôi (Ngải tượng)', '1.01', 'Uống', '', '', 'N3', 'Gam', '92', '100000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(425, '2020.TDY.1029.73', 'Can khương', 'Can khương', 'Can khương', '1.01', 'Uống', '', '', 'N3', 'Gam', '121', '15000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(426, '2020.TDY.1029.78', 'Chỉ thực', 'Chỉ thực', 'Chỉ thực', '1.01', 'Uống', '', '', 'N3', 'Gam', '134', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(427, '2020.TDY.1029.79', 'Chỉ xác', 'Chỉ xác', 'Chỉ xác', '1.01', 'Uống', '', '', 'N3', 'Gam', '96', '20000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(428, '2020.TDY.1029.80', 'Đại hoàng', 'Đại hoàng', 'Đại hoàng', '1.01', 'Uống', '', '', 'N3', 'Gam', '180', '15000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(429, '2020.TDY.1029.81', 'Đơn lá đỏ (Đơn mặt trời)', 'Đơn lá đỏ (Đơn mặt trời)', 'Đơn lá đỏ (Đơn mặt trời)', '1.01', 'Uống', '', '', 'N3', 'Gam', '90', '15000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(430, '2020.TDY.1029.82', 'Hà diệp (Lá sen)', 'Hà diệp (Lá sen)', 'Hà diệp (Lá sen)', '1.01', 'Uống', '', '', 'N3', 'Gam', '65', '15000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(431, '2020.TDY.1029.83', 'Hạ khô thảo', 'Hạ khô thảo', 'Hạ khô thảo', '1.01', 'Uống', '', '', 'N3', 'Gam', '181', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(432, '2020.TDY.1029.84', 'Hạnh nhân', 'Hạnh nhân', 'Hạnh nhân', '1.01', 'Uống', '', '', 'N3', 'Gam', '279', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(433, '2020.TDY.1029.85', 'Hoài sơn', 'Hoài sơn', 'Hoài sơn', '1.01', 'Uống', '', '', 'N3', 'Gam', '117', '250000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(434, '2020.TDY.1029.86', 'Hoàng bá', 'Hoàng bá', 'Hoàng bá', '1.01', 'Uống', '', '', 'N3', 'Gam', '204', '15000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(435, '2020.TDY.1029.87', 'Hoàng cầm', 'Hoàng cầm', 'Hoàng cầm', '1.01', 'Uống', '', '', 'N3', 'Gam', '354', '20000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(436, '2020.TDY.1029.88', 'Hồng hoa', 'Hồng hoa', 'Hồng hoa', '1.01', 'Uống', '', '', 'N3', 'Gam', '763', '25000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(437, '2020.TDY.1029.89', 'Hương phụ', 'Hương phụ', 'Hương phụ', '1.01', 'Uống', '', '', 'N3', 'Gam', '144', '40000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(438, '2020.TDY.1029.90', 'Ích mẫu', 'Ích mẫu', 'Ích mẫu', '1.01', 'Uống', '', '', 'N3', 'Gam', '111', '15000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(439, '2020.TDY.1029.91', 'Ké đấu ngựa (Thương nhĩ tử)', 'Ké đấu ngựa (Thương nhĩ tử)', 'Ké đấu ngựa (Thương nhĩ tử)', '1.01', 'Uống', '', '', 'N3', 'Gam', '102', '20000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(440, '2020.TDY.1029.92', 'Kê nội kim', 'Kê nội kim', 'Kê nội kim', '1.01', 'Uống', '', '', 'N3', 'Gam', '192', '5000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(441, '2020.TDY.1029.93', 'Kim anh', 'Kim anh', 'Kim anh', '1.01', 'Uống', '', '', 'N3', 'Gam', '271', '5000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(442, '2020.TDY.1029.94', 'Kim tiền thảo', 'Kim tiền thảo', 'Kim tiền thảo', '1.01', 'Uống', '', '', 'N3', 'Gam', '130', '20000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(443, '2020.TDY.1029.95', 'Kha tử', 'Kha tử', 'Kha tử', '1.01', 'Uống', '', '', 'N3', 'Gam', '140', '15000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(444, '2020.TDY.1029.68', 'Bạch chỉ', 'Bạch chỉ', 'Bạch chỉ', '1.01', 'Uống', '', '', 'N3', 'Gam', '79', '20000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(445, '2020.TDY.1029.69', 'Bạch hoa xà thiệt thảo', 'Bạch hoa xà thiệt thảo', 'Bạch hoa xà thiệt thảo', '1.01', 'Uống', '', '', 'N3', 'Gam', '186', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(446, '2020.TDY.1029.70', 'Bách hợp', 'Bách hợp', 'Bách hợp', '1.01', 'Uống', '', '', 'N3', 'Gam', '205', '20000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(447, '2020.TDY.1029.74', 'Cát cánh', 'Cát cánh', 'Cát cánh', '1.01', 'Uống', '', '', 'N3', 'Gam', '248', '100000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(448, '2020.TDY.1029.75', 'Cát căn', 'Cát căn', 'Cát căn', '1.01', 'Uống', '', '', 'N3', 'Gam', '89', '20000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(449, '2020.TDY.1029.76', 'Cối xay', 'Cối xay', 'Cối xay', '1.01', 'Uống', '', '', 'N3', 'Gam', '105', '20000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(450, '2020.TDY.1029.77', 'Chi tử', 'Chi tử', 'Chi tử', '1.01', 'Uống', '', '', 'N3', 'Gam', '131', '20000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(451, '2020.TDY.1029.102', 'Mẫu lệ', 'Mẫu lệ', 'Mẫu lệ', '1.01', 'Uống', '', '', 'N3', 'Gam', '123', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(452, '2020.TDY.1029.103', 'Mộc thông', 'Mộc thông', 'Mộc thông', '1.01', 'Uống', '', '', 'N3', 'Gam', '111', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(453, '2020.TDY.1029.104', 'Một dược', 'Một dược', 'Một dược', '1.01', 'Uống', '', '', 'N3', 'Gam', '309', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(454, '2020.TDY.1029.105', 'Nga truật', 'Nga truật', 'Nga truật', '1.01', 'Uống', '', '', 'N3', 'Gam', '119', '15000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(455, '2020.TDY.1029.106', 'Bạc hà', 'Bạc hà', 'Bạc hà', '1.01', 'Uống', '', '', 'N3', 'Gam', '75', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(456, '2020.TDY.1029.107', 'Bạch biển đậu', 'Bạch biển đậu', 'Bạch biển đậu', '1.01', 'Uống', '', '', 'N3', 'Gam', '90', '30000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(457, '2020.TDY.1029.123', 'Râu mèo', 'Râu mèo', 'Râu mèo', '1.01', 'Uống', '', '', 'N3', 'Gam', '76', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(458, '2020.TDY.1029.124', 'Hậu phác', 'Hậu phác', 'Hậu phác', '1.01', 'Uống', '', '', 'N3', 'Gam', '142', '15000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(459, '2020.TDY.1029.96', 'Khiếm thực', 'Khiếm thực', 'Khiếm thực', '1.01', 'Uống', '', '', 'N3', 'Gam', '439', '15000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(460, '2020.TDY.1029.97', 'Khoản đông hoa', 'Khoản đông hoa', 'Khoản đông hoa', '1.01', 'Uống', '', '', 'N3', 'Gam', '700', '15000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(461, '2020.TDY.1029.98', 'Lạc tiên', 'Lạc tiên', 'Lạc tiên', '1.01', 'Uống', '', '', 'N3', 'Gam', '100', '150000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(462, '2020.TDY.1029.99', 'Liên kiều', 'Liên kiều', 'Liên kiều', '1.01', 'Uống', '', '', 'N3', 'Gam', '360', '30000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(463, '2020.TDY.1029.100', 'Liên nhục', 'Liên nhục', 'Liên nhục', '1.01', 'Uống', '', '', 'N3', 'Gam', '195', '250000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(464, '2020.TDY.1029.101', 'Mạn kinh tử', 'Mạn kinh tử', 'Mạn kinh tử', '1.01', 'Uống', '', '', 'N3', 'Gam', '130', '15000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(465, '2020.TDY.1029.117', 'Diệp hạ châu', 'Diệp hạ châu', 'Diệp hạ châu', '1.01', 'Uống', '', '', 'N3', 'Gam', '88', '30000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(466, '2020.TDY.1029.118', 'Đại hồi', 'Đại hồi', 'Đại hồi', '1.01', 'Uống', '', '', 'N3', 'Gam', '191', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(467, '2020.TDY.1029.119', 'Phục thần', 'Phục thần', 'Phục thần', '1.01', 'Uống', '', '', 'N3', 'Gam', '428', '50000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(468, '2020.TDY.1029.120', 'Quảng vương bất lưu hành (Trâu cổ)', 'Quảng vương bất lưu hành (Trâu cổ)', 'Quảng vương bất lưu hành (Trâu cổ)', '1.01', 'Uống', '', '', 'N3', 'Gam', '264', '15000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(469, '2020.TDY.1029.121', 'Độc hoạt', 'Độc hoạt', 'Độc hoạt', '1.01', 'Uống', '', '', 'N3', 'Gam', '196', '300000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(470, '2020.TDY.1029.122', 'Quế nhục', 'Quế nhục', 'Quế nhục', '1.01', 'Uống', '', '', 'N3', 'Gam', '163', '20000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(471, '2020.TDY.1029.108', 'Ngũ gia bì chân chim', 'Ngũ gia bì chân chim', 'Ngũ gia bì chân chim', '1.01', 'Uống', '', '', 'N3', 'Gam', '95', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(472, '2020.TDY.1029.109', 'Bán chi liên', 'Bán chi liên', 'Bán chi liên', '1.01', 'Uống', '', '', 'N3', 'Gam', '192', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(473, '2020.TDY.1029.110', 'Bồ công anh', 'Bồ công anh', 'Bồ công anh', '1.01', 'Uống', '', '', 'N3', 'Gam', '75', '50000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(474, '2020.TDY.1029.111', 'Bồ hoàng', 'Bồ hoàng', 'Bồ hoàng', '1.01', 'Uống', '', '', 'N3', 'Gam', '674', '2000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(475, '2020.TDY.1029.112', 'Cà gai leo', 'Cà gai leo', 'Cà gai leo', '1.01', 'Uống', '', '', 'N3', 'Gam', '121', '20000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(476, '2020.TDY.1029.113', 'Nhũ hương', 'Nhũ hương', 'Nhũ hương', '1.01', 'Uống', '', '', 'N3', 'Gam', '482', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(477, '2020.TDY.1029.114', 'Cẩu tích', 'Cẩu tích', 'Cẩu tích', '1.01', 'Uống', '', '', 'N3', 'Gam', '47', '50000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(478, '2020.TDY.1029.115', 'Cốt khí củ', 'Cốt khí củ', 'Cốt khí củ', '1.01', 'Uống', '', '', 'N3', 'Gam', '117', '20000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(479, '2020.TDY.1029.116', 'Phù bình', 'Phù bình', 'Phù bình', '1.01', 'Uống', '', '', 'N3', 'Gam', '128', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(480, '2020.TDY.1029.125', 'Tang bạch bì', 'Tang bạch bì', 'Tang bạch bì', '1.01', 'Uống', '', '', 'N3', 'Gam', '132', '5000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(481, '2020.TDY.1029.126', 'Tang chi', 'Tang chi', 'Tang chi', '1.01', 'Uống', '', '', 'N3', 'Gam', '91', '20000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(482, '2020.TDY.1029.127', 'Tang diệp', 'Tang diệp', 'Tang diệp', '1.01', 'Uống', '', '', 'N3', 'Gam', '102', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(483, '2020.TDY.1029.128', 'Khổ sâm', 'Khổ sâm', 'Khổ sâm', '1.01', 'Uống', '', '', 'N3', 'Gam', '106', '15000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(484, '2020.TDY.1029.129', 'Lá khôi', 'Lá khôi', 'Lá khôi', '1.01', 'Uống', '', '', 'N3', 'Gam', '302', '20000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(485, '2020.TDY.1029.130', 'Lục thần khúc', 'Lục thần khúc', 'Lục thần khúc', '1.01', 'Uống', '', '', 'N3', 'Gam', '187', '15000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(486, '2020.TDY.1029.131', 'Mã đề', 'Mã đề', 'Mã đề', '1.01', 'Uống', '', '', 'N3', 'Gam', '118', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(487, '2020.TDY.1029.132', 'Mạch nha', 'Mạch nha', 'Mạch nha', '1.01', 'Uống', '', '', 'N3', 'Gam', '117', '20000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(488, '2020.TDY.1029.133', 'Tô diệp', 'Tô diệp', 'Tô diệp', '1.01', 'Uống', '', '', 'N3', 'Gam', '108', '5000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(489, '2020.TDY.1029.138', 'Thạch quyết minh', 'Thạch quyết minh', 'Thạch quyết minh', '1.01', 'Uống', '', '', 'N3', 'Gam', '143', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(490, '2020.TDY.1029.134', 'Tô mộc', 'Tô mộc', 'Tô mộc', '1.01', 'Uống', '', '', 'N3', 'Gam', '132', '5000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(491, '2020.TDY.1029.135', 'Tô tử', 'Tô tử', 'Tô tử', '1.01', 'Uống', '', '', 'N3', 'Gam', '170', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(492, '2020.TDY.1029.136', 'Ngô thù du', 'Ngô thù du', 'Ngô thù du', '1.01', 'Uống', '', '', 'N3', 'Gam', '407', '15000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(493, '2020.TDY.1029.137', 'Tỳ giải', 'Tỳ giải', 'Tỳ giải', '1.01', 'Uống', '', '', 'N3', 'Gam', '190', '15000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(494, '2020.TDY.1029.139', 'Nhân trần', 'Nhân trần', 'Nhân trần', '1.01', 'Uống', '', '', 'N3', 'Gam', '128', '20000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(495, '2020.TDY.1029.140', 'Thạch xương bồ', 'Thạch xương bồ', 'Thạch xương bồ', '1.01', 'Uống', '', '', 'N3', 'Gam', '277', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(496, '2020.TDY.1029.141', 'Ô tặc cốt', 'Ô tặc cốt', 'Ô tặc cốt', '1.01', 'Uống', '', '', 'N3', 'Gam', '207', '200000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(497, '2020.TDY.1029.142', 'Phá cố chỉ (Bổ cốt chỉ)', 'Phá cố chỉ (Bổ cốt chỉ)', 'Phá cố chỉ (Bổ cốt chỉ)', '1.01', 'Uống', '', '', 'N3', 'Gam', '185', '2000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(498, '2020.TDY.1029.143', 'Thảo quyết minh', 'Thảo quyết minh', 'Thảo quyết minh', '1.01', 'Uống', '', '', 'N3', 'Gam', '124', '30000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(499, '2020.TDY.1029.144', 'Quy bản', 'Quy bản', 'Quy bản', '1.01', 'Uống', '', '', 'N3', 'Gam', '440', '5000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(500, '2020.TDY.1029.145', 'Râu ngô', 'Râu ngô', 'Râu ngô', '1.01', 'Uống', '', '', 'N3', 'Gam', '48', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL);
INSERT INTO `medicine_searchs` (`id`, `ma_thuoc`, `ten_thuoc`, `ma_hoat_chat`, `ten_hoat_chat`, `ma_duong_dung`, `ten_duong_dung`, `ham_luong`, `so_dang_ky`, `nhom_thuoc`, `don_vi_tinh`, `don_gia`, `so_luong`, `hang_san_xuat`, `nuoc_san_xuat`, `nha_thau`, `quyet_dinh`, `cong_bo`, `created_at`, `updated_at`) VALUES
(501, '2020.TDY.1029.146', 'Thỏ ty tử', 'Thỏ ty tử', 'Thỏ ty tử', '1.01', 'Uống', '', '', 'N3', 'Gam', '307', '30000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(502, '2020.TDY.1029.147', 'Sài đất', 'Sài đất', 'Sài đất', '1.01', 'Uống', '', '', 'N3', 'Gam', '48', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(503, '2020.TDY.1029.148', 'Sinh khương', 'Sinh khương', 'Sinh khương', '1.01', 'Uống', '', '', 'N3', 'Gam', '107', '20000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(504, '2020.TDY.1029.149', 'Sơn tra', 'Sơn tra', 'Sơn tra', '1.01', 'Uống', '', '', 'N3', 'Gam', '95', '15000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(505, '2020.TDY.1029.150', 'Tam lăng', 'Tam lăng', 'Tam lăng', '1.01', 'Uống', '', '', 'N3', 'Gam', '248', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(506, '2020.TDY.1029.151', 'Tam thất', 'Tam thất', 'Tam thất', '1.01', 'Uống', '', '', 'N3', 'Gam', '3144', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(507, '2020.TDY.1029.152', 'Thông thảo', 'Thông thảo', 'Thông thảo', '1.01', 'Uống', '', '', 'N3', 'Gam', '769', '15000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(508, '2020.TDY.1029.153', 'Tân di', 'Tân di', 'Tân di', '1.01', 'Uống', '', '', 'N3', 'Gam', '251', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(509, '2020.TDY.1029.154', 'Thuyền thoái', 'Thuyền thoái', 'Thuyền thoái', '1.01', 'Uống', '', '', 'N3', 'Gam', '1487', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(510, '2020.TDY.1029.155', 'Tiền hồ', 'Tiền hồ', 'Tiền hồ', '1.01', 'Uống', '', '', 'N3', 'Gam', '350', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(511, '2020.TDY.1029.156', 'Trần bì', 'Trần bì', 'Trần bì', '1.01', 'Uống', '', '', 'N3', 'Gam', '89', '30000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(512, '2020.TDY.1029.157', 'Tử uyển', 'Tử uyển', 'Tử uyển', '1.01', 'Uống', '', '', 'N3', 'Gam', '237', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(513, '2020.TDY.1029.158', 'Thạch vĩ', 'Thạch vĩ', 'Thạch vĩ', '1.01', 'Uống', '', '', 'N3', 'Gam', '98', '15000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(514, '2020.TDY.1029.159', 'Thiên hoa phấn', 'Thiên hoa phấn', 'Thiên hoa phấn', '1.01', 'Uống', '', '', 'N3', 'Gam', '240', '20000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(515, '2020.TDY.1029.160', 'Thương truật', 'Thương truật', 'Thương truật', '1.01', 'Uống', '', '', 'N3', 'Gam', '433', '120000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(516, '2020.TDY.1029.161', 'Xạ đen', 'Xạ đen', 'Xạ đen', '1.01', 'Uống', '', '', 'N3', 'Gam', '147', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(517, '2020.TDY.1029.162', 'Trắc bách diệp', 'Trắc bách diệp', 'Trắc bách diệp', '1.01', 'Uống', '', '', 'N3', 'Gam', '191', '5000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(518, '2020.TDY.1029.163', 'Xa tiền tử', 'Xa tiền tử', 'Xa tiền tử', '1.01', 'Uống', '', '', 'N3', 'Gam', '279', '10000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(519, '2020.TDY.1029.164', 'Trinh nữ (Xấu hổ)', 'Trinh nữ (Xấu hổ)', 'Trinh nữ (Xấu hổ)', '1.01', 'Uống', '', '', 'N3', 'Gam', '158', '2000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(520, '2020.TDY.1029.165', 'Xuyên bối mẫu', 'Xuyên bối mẫu', 'Xuyên bối mẫu', '1.01', 'Uống', '', '', 'N3', 'Gam', '1874', '30000', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', 'Việt Nam', 'Liên danh Công ty cổ phần Dược Sơn Lâm và Công ty TNHH Dược học cổ truyền Thắng Đoan', '1029/QĐ-BV', '20200715', NULL, NULL),
(521, '2020.652.G.1', 'Lovastatin 20mg', 'Lovastatin', 'Lovastatin', '1.01', 'Uống', '20mg', 'VD-17722-12', '4', 'Viên', '1540', '200000', 'Công ty Cổ phần xuất nhập khẩu y tế Domesco', 'Việt nam', 'Công ty TNHH Dịch vụ đầu tư phát triển y tế Hà Nội', '652/QĐ-BV', '20200514', NULL, NULL),
(522, '2020.1079.G.2', 'Oresol 245', 'Natri clorid + natri citrat + kali clorid + glucose khan', 'Natri clorid + natri citrat + kali clorid + glucose khan', '1.01', 'Uống', '520mg + 580mg + 300mg+ 2,7g', 'VD-22037-14', '4', 'Gói', '805', '20000', 'CTCP Dược Hậu Giang', 'Việt nam', 'Công ty Cổ phần Dược Hậu Giang', '1079/QĐ-BV', '20200720', NULL, NULL),
(523, 'THUOC002', 'RIDLOR', 'Clopidogrel', 'Clopidogrel', '1.01', 'Uống', '75mg', 'VN-17748-14', '1', 'Viên', '1099', '10000', 'Pharmathen S.A', 'Greece', 'Công ty Cổ phần Dược phẩm Thiết bị Y tế Hà Nội', '1044/QĐ-BV', '20200715', NULL, NULL),
(524, 'THUOC001', 'Trinitrina', 'Glyceryl trinitrat(Nitroglycerin)', 'Glyceryl trinitrat(Nitroglycerin)', '2.10', 'Tiêm', '1,5mg', 'VN-21228-18', '1', 'Ống', '42800', '100', 'Fisiopharma SRL', 'Italya', 'Công ty TNHH Dược phẩm Gia Minh', '1044/QĐ-BV', '20200715', NULL, NULL),
(525, 'THNDM1102', 'Linod 600mg', 'Linezolid*', 'Linezolid*', '2.10', 'Tiêm', '600mg', 'VD-26611-17', '1', 'Lọ', '358000', '30', '', '', 'Công ty Cổ phần Dược phẩm Sviet', '1044/QĐ-BV', '20200715', NULL, NULL),
(527, 'ma_thuoc', 'ten_thuoc', 'ma_hoat_chat', 'ten_hoat_chat', 'ma_duong_dung', 'ten_duong_dung', 'ham_luong', 'so_dang_ky', 'nhom_thuoc', 'don_vi_tinh', '0', '0', 'hang_san_xuat', 'nuoc_san_xuat', 'nha_thau', 'quyet_dinh', 'cong_bo', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `med_regs`
--

CREATE TABLE `med_regs` (
  `id` int(11) NOT NULL,
  `name` varchar(254) NOT NULL,
  `gender` int(10) NOT NULL,
  `birthday` date NOT NULL,
  `city` int(10) NOT NULL,
  `district` int(10) NOT NULL,
  `ward` int(10) NOT NULL,
  `email` varchar(254) NOT NULL,
  `phone` varchar(254) NOT NULL,
  `healthcaredate` date NOT NULL,
  `healthcaretime` int(10) NOT NULL,
  `clinic` int(10) NOT NULL,
  `symptoms` varchar(254) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `med_regs`
--

INSERT INTO `med_regs` (`id`, `name`, `gender`, `birthday`, `city`, `district`, `ward`, `email`, `phone`, `healthcaredate`, `healthcaretime`, `clinic`, `symptoms`, `created_at`, `updated_at`) VALUES
(5, 'Nguyễn Ngọc Trác', 1, '2018-09-07', 2, 1, 2, 'tracnn20021979@gmail.com', '0988795445', '2018-09-08', 1, 1, '1,2', '2018-09-07 06:39:30', '2018-09-07 06:39:30'),
(9, 'Nguyễn Ngọc Trác', 1, '1979-02-20', 2, 1, 2, 'habvnn@gmail.com', '0979791086', '2018-09-14', 1, 1, '1,2', '2018-09-13 06:55:31', '2018-09-13 06:55:31'),
(10, 'Nguyen Ngoc Trac', 1, '1979-02-20', 2, 1, 2, 'tracnn20021979@gmail.com', '0988795445', '2018-09-15', 1, 2, '1', '2018-09-14 02:50:07', '2018-09-14 02:50:07'),
(11, 'Nguyen Ngoc Trac', 1, '1979-02-20', 2, 1, 2, 'tracnn20021979@gmail.com', '0988795445', '2018-09-15', 1, 2, '1', '2018-09-14 02:51:18', '2018-09-14 02:51:18'),
(12, 'Nguyen Ngoc Trac', 1, '1979-02-20', 2, 1, 2, 'tracnn20021979@gmail.com', '0988795445', '2018-09-15', 1, 2, '1', '2018-09-14 02:51:49', '2018-09-14 02:51:49'),
(13, 'Nguyen Ngoc Trac', 1, '1979-02-20', 2, 1, 2, 'tracnn20021979@gmail.com', '0988795445', '2018-09-15', 1, 2, '1', '2018-09-14 02:52:39', '2018-09-14 02:52:39'),
(15, 'Nguyen Ngoc Trac', 1, '1979-02-20', 2, 1, 2, 'tracnn20021979@gmail.com', '0988795445', '2018-09-15', 1, 2, '1', '2018-09-14 02:54:56', '2018-09-14 02:54:56'),
(16, 'Nguyen Ngoc Trac', 1, '1979-02-20', 2, 1, 2, 'tracnn20021979@gmail.com', '0988795445', '2018-09-15', 1, 2, '1', '2018-09-14 02:55:07', '2018-09-14 02:55:07'),
(18, 'Nguyễn Đình Vũ', 1, '1992-01-01', 2, 2, 3, 'tracnn20021979@gmail.com', '0988795445', '2018-09-20', 1, 2, '2', '2018-09-14 04:05:13', '2018-09-14 04:05:13'),
(19, 'Nguyễn Ngọc Trác', 1, '1979-02-20', 2, 1, 2, 'habvnn@gmail.com', '0979791086', '2018-09-19', 1, 1, '1,2', '2018-09-18 01:00:41', '2018-09-18 01:00:41'),
(20, 'Nguyễn Thành Luân', 1, '1990-02-20', 2, 2, 3, 'tracnn20021979@gmail.com', '0988795445', '2018-10-06', 3, 2, '2', '2018-10-05 03:56:12', '2018-10-05 03:56:12'),
(21, 'Nguyen Ngọc Hà', 1, '1980-02-20', 2, 1, 2, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 1, 1, '1', '2018-10-11 02:00:21', '2018-10-11 02:00:21'),
(25, 'Nguyễn Văn Quang', 1, '1979-02-20', 2, 2, 3, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 1, 2, '1', '2018-10-11 02:09:00', '2018-10-11 02:09:00'),
(28, 'Nguyễn Ngọc Trác', 1, '1979-02-20', 2, 1, 2, 'tracnn20021979@gmail.com', '0988495445', '2018-10-12', 1, 1, '2', '2018-10-11 02:15:27', '2018-10-11 02:15:27'),
(29, 'Nguyễn Đình Vũ', 1, '1979-02-20', 2, 1, 2, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 1, 1, '2', '2018-10-11 02:21:30', '2018-10-11 02:21:30'),
(30, 'Nguyễn Quang Hải', 1, '1979-02-20', 2, 2, 3, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 1, 2, '1', '2018-10-11 02:22:46', '2018-10-11 02:22:46'),
(31, 'Nguyễn Văn Hà', 1, '2000-01-01', 2, 1, 2, 'tracnn20021979@gmail.com', '0988794554', '2018-10-12', 1, 2, '1', '2018-10-11 02:39:52', '2018-10-11 02:39:52'),
(32, 'Hoàng Mạnh Hà', 1, '2000-01-01', 2, 2, 3, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 1, 2, '1', '2018-10-11 02:42:48', '2018-10-11 02:42:48'),
(33, 'Nguyễn Đức Mạnh', 1, '2000-01-01', 2, 2, 3, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 1, 2, '1', '2018-10-11 02:49:52', '2018-10-11 02:49:52'),
(34, 'Nguyễn Ngọc Hải', 1, '1979-02-20', 2, 1, 1, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 1, 1, '2', '2018-10-11 03:14:18', '2018-10-11 03:14:18'),
(35, 'Nguyễn Ngọc Hải', 1, '1979-02-20', 2, 1, 1, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 1, 1, '2', '2018-10-11 03:14:26', '2018-10-11 03:14:26'),
(36, 'Nguyễn', 1, '1979-02-20', 2, 2, 3, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 1, 2, '1', '2018-10-11 03:17:26', '2018-10-11 03:17:26'),
(37, 'Nguyễn Ngọc Hải', 0, '1979-02-20', 2, 2, 3, 'tracnn20021979@gmail.com', '0988795445', '2018-10-13', 2, 2, '2', '2018-10-11 03:19:13', '2018-10-11 03:19:13'),
(38, 'Nguyễn Văn Hải', 1, '1979-02-20', 2, 2, 3, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 1, 2, '1', '2018-10-11 03:20:49', '2018-10-11 03:20:49'),
(39, 'Nguyễn Văn Mười', 1, '1979-02-20', 2, 2, 3, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 1, 2, '1', '2018-10-11 03:24:02', '2018-10-11 03:24:02'),
(40, 'Nguyễn Văn Hai', 1, '2000-01-01', 2, 2, 3, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 2, 2, '1', '2018-10-11 03:25:52', '2018-10-11 03:25:52'),
(41, 'Nguyễn Văn Hai', 1, '2000-01-01', 2, 2, 3, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 2, 2, '1', '2018-10-11 03:26:29', '2018-10-11 03:26:29'),
(42, 'Minh Nguyễn', 1, '1980-01-01', 2, 2, 3, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 1, 2, '1', '2018-10-11 03:28:10', '2018-10-11 03:28:10'),
(43, 'Nguyễn Văn Hải', 1, '1972-02-20', 2, 2, 3, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 1, 2, '1', '2018-10-11 03:50:35', '2018-10-11 03:50:35'),
(44, 'Nguyen', 1, '1980-01-01', 2, 2, 3, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 1, 2, '1', '2018-10-11 04:22:19', '2018-10-11 04:22:19'),
(45, 'Nguyen', 0, '1980-01-01', 2, 2, 3, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 1, 1, '1', '2018-10-11 04:23:44', '2018-10-11 04:23:44'),
(46, 'Nguyễn Đình Vũ', 1, '1992-01-01', 2, 2, 3, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 1, 1, '2', '2018-10-11 04:27:33', '2018-10-11 04:27:33'),
(47, 'Nguyễn Văn', 1, '1979-02-20', 2, 2, 3, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 1, 1, '1', '2018-10-11 07:13:23', '2018-10-11 07:13:23'),
(48, 'Nguyễn', 1, '1980-01-01', 2, 2, 3, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 1, 1, '1', '2018-10-11 07:15:28', '2018-10-11 07:15:28'),
(49, 'Nguyễn', 1, '1980-01-01', 2, 2, 3, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 1, 1, '1', '2018-10-11 07:26:38', '2018-10-11 07:26:38'),
(50, 'Test', 1, '1999-01-01', 2, 2, 3, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 1, 1, '1', '2018-10-11 07:28:41', '2018-10-11 07:28:41'),
(51, 'Test thử tí', 1, '2000-01-01', 2, 1, 2, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 1, 2, '1', '2018-10-11 07:30:16', '2018-10-11 07:30:16'),
(52, 'Phạm Văn Mách', 1, '2000-01-01', 2, 2, 3, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 2, 2, '1', '2018-10-11 07:32:06', '2018-10-11 07:32:06'),
(53, 'Trần Chân', 1, '1980-01-01', 2, 1, 2, 'tracnn20021979@gmail.com', '0988795445', '2018-10-12', 1, 2, '1', '2018-10-11 14:30:01', '2018-10-11 14:30:01'),
(54, 'tracnn', 1, '1979-02-20', 2, 1, 2, 'tracnn20021979@gmail.com', '0988795445', '2018-10-13', 1, 1, '1', '2018-10-12 05:33:54', '2018-10-12 05:33:54');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_resets_table', 1),
(3, '2017_10_23_052501_laratrust_setup_tables', 1),
(4, '2017_11_06_131628_create_category_genders_table', 2),
(5, '2017_11_17_092157_create_items_table', 3),
(6, '2017_11_23_190358_create_companies_table', 4),
(7, '2017_11_23_190643_create_user__companies_table', 5),
(8, '2017_11_23_190643_create_user_companies_table', 6),
(9, '2018_10_11_093540_create_jobs_table', 7),
(10, '2018_10_11_093719_create_failed_jobs_table', 7),
(11, '2018_10_16_203916_create_table_check_insurance', 8),
(12, '2018_10_18_092003_insurance_cards', 9),
(13, '2018_12_09_083913_create_activity_log_table', 10),
(14, '2024_01_13_202956_create_queue_numbers_table', 11),
(15, '2024_04_08_085534_create_payments_table', 12),
(16, '2024_05_07_151944_create_vaccines_table', 13),
(17, '2024_05_08_140237_create_patients_table', 14),
(26, '2024_05_09_101459_create_vaccinations_table', 15),
(27, '2024_05_15_064006_add_department_name_to_payments_table', 15),
(28, '2024_05_20_080159_create_pre_vaccination_checks_table', 16),
(32, '2024_06_10_072936_create_xml_error_checks_table', 17),
(34, '2024_06_11_143232_create_xml1s_table', 19),
(35, '2024_06_11_143411_create_xml2s_table', 19),
(36, '2024_06_11_143538_create_xml3s_table', 19),
(37, '2024_06_11_143652_create_xml4s_table', 19),
(38, '2024_06_11_143759_create_xml5s_table', 19),
(39, '2024_06_11_142642_create_check_hein_cards_table', 20),
(50, '2024_06_12_143949_create_danh_muc_vat_tu_table', 21),
(51, '2024_06_12_145200_create_danh_muc_dich_vu_table', 21),
(52, '2024_06_13_094552_create_nhan_vien_y_te_table', 21),
(55, '2024_06_12_142339_create_danh_muc_thuoc_table', 22),
(56, '2024_06_14_082320_create_khoa_phong_giuong_table', 23),
(57, '2024_06_14_084449_create_trang_thiet_bi_table', 24),
(60, '2024_06_17_063634_create_xml_error_catalog_table', 25),
(72, '2024_06_19_133004_create_xml1_qd130s_table', 26),
(73, '2024_06_19_135402_create_xml2_qd130s_table', 26),
(74, '2024_06_19_140159_create_xml3_qd130s_table', 26),
(75, '2024_06_19_141730_create_xml4_qd130s_table', 26),
(76, '2024_06_19_142134_create_xml5_qd130s_table', 26),
(78, '2024_06_19_150748_create_xml_qd130_error_result_table', 27),
(79, '2024_06_21_125625_create_xml_qd130_error_catalogs_table', 28);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`email`, `token`, `created_at`) VALUES
('kskmat@gmail.com', '$2y$10$gdeSDSaoHyYTM/6XwTHJn.Gaa.v5hEN3BRwSTzmPp6XCKlW.aW7QS', '2022-09-20 08:29:52'),
('kskbvnn@gmail.com', '$2y$10$as9RE6L9WXUhXKz9S3V33uY95Gwaa9.S4zkyg/UixWhxUbWo6kZEW', '2022-12-27 00:49:59'),
('hoanghungnh@gmail.com', '$2y$10$uFrI1IRlLsZRdUxEE/kstuq5o4JGDMQnE/mHNPyfYrPLTJasBo6Ti', '2022-12-28 02:17:44');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` char(1) NOT NULL,
  `contact_info` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_send_mails`
--

CREATE TABLE `patient_send_mails` (
  `id` bigint(20) NOT NULL,
  `service_req_code` varchar(20) NOT NULL,
  `intruction_time` bigint(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patient_send_sms`
--

CREATE TABLE `patient_send_sms` (
  `id` bigint(20) NOT NULL,
  `service_req_code` varchar(20) NOT NULL,
  `intruction_time` bigint(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `treatment_code` varchar(255) NOT NULL,
  `patient_name` varchar(255) NOT NULL,
  `patient_dob` date NOT NULL,
  `patient_address` varchar(255) NOT NULL,
  `patient_mobile` varchar(255) DEFAULT NULL,
  `patient_relative_mobile` varchar(255) DEFAULT NULL,
  `is_payment` tinyint(1) NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `login_name` varchar(255) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `department_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `display_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'create-users', 'Create Users', 'Create Users', '2017-10-23 19:00:54', '2017-10-23 19:00:54'),
(2, 'read-users', 'Read Users', 'Read Users', '2017-10-23 19:00:54', '2017-10-23 19:00:54'),
(3, 'update-users', 'Update Users', 'Update Users', '2017-10-23 19:00:55', '2017-10-23 19:00:55'),
(4, 'delete-users', 'Delete Users', 'Delete Users', '2017-10-23 19:00:55', '2017-10-23 19:00:55'),
(5, 'create-acl', 'Create Acl', 'Create Acl', '2017-10-23 19:00:55', '2017-10-23 19:00:55'),
(6, 'read-acl', 'Read Acl', 'Read Acl', '2017-10-23 19:00:55', '2017-10-23 19:00:55'),
(7, 'update-acl', 'Update Acl', 'Update Acl', '2017-10-23 19:00:55', '2017-10-23 19:00:55'),
(8, 'delete-acl', 'Delete Acl', 'Delete Acl', '2017-10-23 19:00:56', '2017-10-23 19:00:56'),
(9, 'read-profile', 'Read Profile', 'Read Profile', '2017-10-23 19:00:56', '2017-10-23 19:00:56'),
(10, 'update-profile', 'Update Profile', 'Update Profile', '2017-10-23 19:00:56', '2017-10-23 19:00:56'),
(11, 'create-profile', 'Create Profile', 'Create Profile', '2017-10-23 19:00:59', '2017-10-23 19:00:59'),
(12, 'manager', 'Manager', 'Manager', '2017-10-23 19:00:59', '2017-10-23 19:00:59'),
(13, 'check-hein-card', 'check-hein-card', 'Check Hein Card Number', NULL, NULL),
(14, 'vaccination', 'Vaccination', 'Vaccination', NULL, NULL),
(15, 'ksk-tiepdon', 'Khám sức khỏe - Tiếp đón', 'Khám sức khỏe - Tiếp đón', NULL, NULL),
(16, 'ksk-mat', 'Khám sức khỏe - Mắt', 'Khám sức khỏe - CK Mắt', NULL, NULL),
(17, 'ksk-theluc', 'Khám sức khỏe - Thể lực', 'Khám sức khỏe - Thể lực', NULL, NULL),
(18, 'ksk-noichung', 'Khám sức khỏe - Nội chung', 'Khám sức khỏe - Nội chung', NULL, NULL),
(19, 'ksk-rhm', 'Khám sức khỏe - Răng hàm mặt', 'Khám sức khỏe - Răng hàm mặt', NULL, NULL),
(20, 'ksk-tmh', 'Khám sức khỏe - Tai mũi họng', 'Khám sức khỏe - Tai mũi họng', NULL, NULL),
(21, 'ksk-san', 'Khám sức khỏe - Sản', 'Khám sức khỏe - Sản', NULL, NULL),
(22, 'thungan-khoa', 'Thu ngân - Khoa', 'Thu ngân - Khoa', NULL, NULL),
(23, 'thungan-tckt', 'Thu ngân - Tài chính kế toán', 'Thu ngân - Tài chính kế toán', NULL, NULL),
(24, 'duoc', 'Dược', 'Dược', '2024-05-31 02:50:15', '2024-05-31 02:50:15');

-- --------------------------------------------------------

--
-- Table structure for table `permission_role`
--

CREATE TABLE `permission_role` (
  `permission_id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permission_user`
--

CREATE TABLE `permission_user` (
  `permission_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `user_type` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pre_vaccination_checks`
--

CREATE TABLE `pre_vaccination_checks` (
  `id` int(10) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `vaccine_id` int(10) UNSIGNED NOT NULL,
  `weight` varchar(255) DEFAULT NULL,
  `temperature` varchar(255) DEFAULT NULL,
  `anaphylactic_reaction` tinyint(1) NOT NULL DEFAULT 0,
  `acute_or_chronic_disease` tinyint(1) NOT NULL DEFAULT 0,
  `corticosteroids` tinyint(1) NOT NULL DEFAULT 0,
  `fever_or_hypothermia` tinyint(1) NOT NULL DEFAULT 0,
  `immune_deficiency` tinyint(1) NOT NULL DEFAULT 0,
  `abnormal_heart` tinyint(1) NOT NULL DEFAULT 0,
  `abnormal_lungs` tinyint(1) NOT NULL DEFAULT 0,
  `abnormal_consciousness` tinyint(1) NOT NULL DEFAULT 0,
  `other_contraindications` text DEFAULT NULL,
  `specialist_exam` tinyint(1) NOT NULL DEFAULT 0,
  `specialist_exam_details` text DEFAULT NULL,
  `eligible_for_vaccination` tinyint(1) NOT NULL DEFAULT 1,
  `contraindication` tinyint(1) NOT NULL DEFAULT 0,
  `postponed` tinyint(1) NOT NULL DEFAULT 0,
  `time` timestamp NULL DEFAULT NULL,
  `administered_by` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qd130_xml1s`
--

CREATE TABLE `qd130_xml1s` (
  `id` int(10) UNSIGNED NOT NULL,
  `ma_lk` varchar(255) NOT NULL,
  `stt` int(11) NOT NULL,
  `ma_bn` varchar(255) DEFAULT NULL,
  `ho_ten` varchar(255) DEFAULT NULL,
  `so_cccd` varchar(255) DEFAULT NULL,
  `ngay_sinh` varchar(12) DEFAULT NULL,
  `gioi_tinh` int(11) DEFAULT NULL,
  `nhom_mau` varchar(255) DEFAULT NULL,
  `ma_quoctich` varchar(255) DEFAULT NULL,
  `ma_dantoc` varchar(255) DEFAULT NULL,
  `ma_nghe_nghiep` varchar(255) DEFAULT NULL,
  `dia_chi` text DEFAULT NULL,
  `matinh_cu_tru` varchar(255) DEFAULT NULL,
  `mahuyen_cu_tru` varchar(255) DEFAULT NULL,
  `maxa_cu_tru` varchar(255) DEFAULT NULL,
  `dien_thoai` varchar(255) DEFAULT NULL,
  `ma_the_bhyt` varchar(255) DEFAULT NULL,
  `ma_dkbd` varchar(255) DEFAULT NULL,
  `gt_the_tu` varchar(255) DEFAULT NULL,
  `gt_the_den` varchar(255) DEFAULT NULL,
  `ngay_mien_cct` varchar(255) DEFAULT NULL,
  `ly_do_vv` text DEFAULT NULL,
  `ly_do_vnt` text DEFAULT NULL,
  `ma_ly_do_vnt` varchar(255) DEFAULT NULL,
  `chan_doan_vao` text DEFAULT NULL,
  `chan_doan_rv` text DEFAULT NULL,
  `ma_benh_chinh` varchar(255) DEFAULT NULL,
  `ma_benh_kt` text DEFAULT NULL,
  `ma_benh_yhct` varchar(255) DEFAULT NULL,
  `ma_pttt_qt` varchar(255) DEFAULT NULL,
  `ma_doituong_kcb` varchar(255) DEFAULT NULL,
  `ma_noi_di` varchar(255) DEFAULT NULL,
  `ma_noi_den` varchar(255) DEFAULT NULL,
  `ma_tai_nan` varchar(255) DEFAULT NULL,
  `ngay_vao` varchar(255) DEFAULT NULL,
  `ngay_vao_noi_tru` varchar(255) DEFAULT NULL,
  `ngay_ra` varchar(255) DEFAULT NULL,
  `giay_chuyen_tuyen` varchar(255) DEFAULT NULL,
  `so_ngay_dtri` int(11) DEFAULT NULL,
  `pp_dieu_tri` text DEFAULT NULL,
  `ket_qua_dtri` int(11) DEFAULT NULL,
  `ma_loai_rv` int(11) DEFAULT NULL,
  `ghi_chu` text DEFAULT NULL,
  `ngay_ttoan` varchar(255) DEFAULT NULL,
  `t_thuoc` double DEFAULT NULL,
  `t_vtyt` double DEFAULT NULL,
  `t_tongchi_bv` double DEFAULT NULL,
  `t_tongchi_bh` double DEFAULT NULL,
  `t_bntt` double DEFAULT NULL,
  `t_bncct` double DEFAULT NULL,
  `t_bhtt` double DEFAULT NULL,
  `t_nguonkhac` double DEFAULT NULL,
  `t_bhtt_gdv` double DEFAULT NULL,
  `nam_qt` varchar(255) DEFAULT NULL,
  `thang_qt` varchar(255) DEFAULT NULL,
  `ma_loai_kcb` varchar(255) DEFAULT NULL,
  `ma_khoa` varchar(255) DEFAULT NULL,
  `ma_cskcb` varchar(255) DEFAULT NULL,
  `ma_khuvuc` varchar(255) DEFAULT NULL,
  `can_nang` varchar(255) DEFAULT NULL,
  `can_nang_con` varchar(255) DEFAULT NULL,
  `nam_nam_lien_tuc` varchar(255) DEFAULT NULL,
  `ngay_tai_kham` varchar(255) DEFAULT NULL,
  `ma_hsba` varchar(255) DEFAULT NULL,
  `ma_ttdv` varchar(255) DEFAULT NULL,
  `du_phong` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qd130_xml2s`
--

CREATE TABLE `qd130_xml2s` (
  `id` int(10) UNSIGNED NOT NULL,
  `ma_lk` varchar(255) NOT NULL,
  `stt` int(11) NOT NULL,
  `ma_thuoc` varchar(255) DEFAULT NULL,
  `ma_pp_chebien` varchar(255) DEFAULT NULL,
  `ma_cskcb_thuoc` varchar(255) DEFAULT NULL,
  `ma_nhom` varchar(255) DEFAULT NULL,
  `ten_thuoc` varchar(255) DEFAULT NULL,
  `don_vi_tinh` varchar(255) DEFAULT NULL,
  `ham_luong` text DEFAULT NULL,
  `duong_dung` varchar(255) DEFAULT NULL,
  `dang_bao_che` varchar(255) DEFAULT NULL,
  `lieu_dung` text DEFAULT NULL,
  `cach_dung` varchar(255) DEFAULT NULL,
  `so_dang_ky` varchar(255) DEFAULT NULL,
  `tt_thau` varchar(255) DEFAULT NULL,
  `pham_vi` int(11) DEFAULT NULL,
  `tyle_tt_bh` double DEFAULT NULL,
  `so_luong` double DEFAULT NULL,
  `don_gia` double DEFAULT NULL,
  `thanh_tien_bv` double DEFAULT NULL,
  `thanh_tien_bh` double DEFAULT NULL,
  `t_nguonkhac_nsnn` double DEFAULT NULL,
  `t_nguonkhac_vtnn` double DEFAULT NULL,
  `t_nguonkhac_vttn` double DEFAULT NULL,
  `t_nguonkhac_cl` double DEFAULT NULL,
  `t_nguonkhac` double DEFAULT NULL,
  `muc_huong` double DEFAULT NULL,
  `t_bhtt` double DEFAULT NULL,
  `t_bncct` double DEFAULT NULL,
  `t_bntt` double DEFAULT NULL,
  `ma_khoa` varchar(255) DEFAULT NULL,
  `ma_bac_si` varchar(255) DEFAULT NULL,
  `ma_dich_vu` varchar(255) DEFAULT NULL,
  `ngay_yl` varchar(255) DEFAULT NULL,
  `ngay_th_yl` varchar(255) DEFAULT NULL,
  `ma_pttt` varchar(255) DEFAULT NULL,
  `nguon_ctra` int(11) DEFAULT NULL,
  `vet_thuong_tp` varchar(255) DEFAULT NULL,
  `du_phong` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qd130_xml3s`
--

CREATE TABLE `qd130_xml3s` (
  `id` int(10) UNSIGNED NOT NULL,
  `ma_lk` varchar(255) NOT NULL,
  `stt` int(11) NOT NULL,
  `ma_dich_vu` varchar(255) DEFAULT NULL,
  `ma_pttt_qt` varchar(255) DEFAULT NULL,
  `ma_vat_tu` varchar(255) DEFAULT NULL,
  `ma_nhom` varchar(255) DEFAULT NULL,
  `goi_vtyt` varchar(255) DEFAULT NULL,
  `ten_vat_tu` varchar(255) DEFAULT NULL,
  `ten_dich_vu` varchar(255) DEFAULT NULL,
  `ma_xang_dau` varchar(255) DEFAULT NULL,
  `don_vi_tinh` varchar(255) DEFAULT NULL,
  `pham_vi` int(11) DEFAULT NULL,
  `so_luong` double DEFAULT NULL,
  `don_gia_bv` double DEFAULT NULL,
  `don_gia_bh` double DEFAULT NULL,
  `tt_thau` varchar(255) DEFAULT NULL,
  `tyle_tt_dv` double DEFAULT NULL,
  `tyle_tt_bh` double DEFAULT NULL,
  `thanh_tien_bv` double DEFAULT NULL,
  `thanh_tien_bh` double DEFAULT NULL,
  `t_trantt` double DEFAULT NULL,
  `muc_huong` double DEFAULT NULL,
  `t_nguonkhac_nsnn` double DEFAULT NULL,
  `t_nguonkhac_vtnn` double DEFAULT NULL,
  `t_nguonkhac_vttn` double DEFAULT NULL,
  `t_nguonkhac_cl` double DEFAULT NULL,
  `t_nguonkhac` double DEFAULT NULL,
  `t_bhtt` double DEFAULT NULL,
  `t_bntt` double DEFAULT NULL,
  `t_bncct` double DEFAULT NULL,
  `ma_khoa` varchar(255) DEFAULT NULL,
  `ma_giuong` varchar(255) DEFAULT NULL,
  `ma_bac_si` varchar(255) DEFAULT NULL,
  `nguoi_thuc_hien` varchar(255) DEFAULT NULL,
  `ma_benh` varchar(255) DEFAULT NULL,
  `ma_benh_yhct` varchar(255) DEFAULT NULL,
  `ngay_yl` varchar(255) DEFAULT NULL,
  `ngay_th_yl` varchar(255) DEFAULT NULL,
  `ngay_kq` varchar(255) DEFAULT NULL,
  `ma_pttt` varchar(255) DEFAULT NULL,
  `vet_thuong_tp` varchar(255) DEFAULT NULL,
  `pp_vo_cam` varchar(255) DEFAULT NULL,
  `vi_tri_th_dvkt` varchar(255) DEFAULT NULL,
  `ma_may` varchar(255) DEFAULT NULL,
  `ma_hieu_sp` varchar(255) DEFAULT NULL,
  `tai_su_dung` varchar(255) DEFAULT NULL,
  `du_phong` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qd130_xml4s`
--

CREATE TABLE `qd130_xml4s` (
  `id` int(10) UNSIGNED NOT NULL,
  `ma_lk` varchar(255) NOT NULL,
  `stt` int(11) NOT NULL,
  `ma_dich_vu` varchar(255) DEFAULT NULL,
  `ma_chi_so` varchar(255) DEFAULT NULL,
  `ten_chi_so` varchar(255) DEFAULT NULL,
  `gia_tri` varchar(255) DEFAULT NULL,
  `don_vi_do` varchar(255) DEFAULT NULL,
  `mo_ta` text DEFAULT NULL,
  `ket_luan` text DEFAULT NULL,
  `ngay_kq` varchar(255) DEFAULT NULL,
  `ma_bs_doc_kq` varchar(255) DEFAULT NULL,
  `du_phong` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qd130_xml5s`
--

CREATE TABLE `qd130_xml5s` (
  `id` int(10) UNSIGNED NOT NULL,
  `ma_lk` varchar(255) NOT NULL,
  `stt` int(11) NOT NULL,
  `dien_bien_ls` text DEFAULT NULL,
  `giai_doan_benh` text DEFAULT NULL,
  `hoi_chan` text DEFAULT NULL,
  `phau_thuat` text DEFAULT NULL,
  `thoi_diem_dbls` varchar(255) DEFAULT NULL,
  `nguoi_thuc_hien` varchar(255) DEFAULT NULL,
  `du_phong` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qd130_xml_error_catalogs`
--

CREATE TABLE `qd130_xml_error_catalogs` (
  `id` int(10) UNSIGNED NOT NULL,
  `xml` varchar(255) NOT NULL,
  `error_code` varchar(255) NOT NULL,
  `error_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qd130_xml_error_results`
--

CREATE TABLE `qd130_xml_error_results` (
  `id` int(10) UNSIGNED NOT NULL,
  `xml` varchar(255) NOT NULL,
  `ma_lk` varchar(255) NOT NULL,
  `stt` int(11) NOT NULL,
  `ngay_yl` varchar(255) DEFAULT NULL,
  `ngay_kq` varchar(255) DEFAULT NULL,
  `error_code` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `queue_numbers`
--

CREATE TABLE `queue_numbers` (
  `id` int(10) UNSIGNED NOT NULL,
  `department_code` varchar(255) NOT NULL,
  `phone_number` varchar(255) NOT NULL,
  `number` int(11) NOT NULL,
  `is_sms_sended` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `queue_numbers`
--

INSERT INTO `queue_numbers` (`id`, `department_code`, `phone_number`, `number`, `is_sms_sended`, `created_at`, `updated_at`) VALUES
(1, 'KKB', '0988795445', 1, 0, '2024-01-14 00:28:04', '2024-01-14 00:28:04'),
(2, 'KKB', '0975757933', 2, 0, '2024-01-14 00:39:13', '2024-01-14 00:39:13'),
(3, 'KKB', '0969742490', 3, 0, '2024-01-14 00:41:20', '2024-01-14 00:41:20'),
(4, 'KKB', '0988795445', 1, 0, '2024-01-15 02:07:42', '2024-01-15 02:07:42'),
(5, 'KKB', '0975757933', 2, 0, '2024-01-15 02:07:56', '2024-01-15 02:07:56'),
(6, 'KKB', '0652246985', 3, 0, '2024-01-15 15:05:40', '2024-01-15 15:05:40'),
(7, 'KKB', '0988795445', 1, 0, '2024-01-16 06:51:46', '2024-01-16 06:51:46'),
(8, 'KKB', '0975757933', 1, 0, '2024-01-16 23:46:55', '2024-01-16 23:46:55'),
(9, 'KKB', '0988795445', 1, 0, '2024-01-19 08:13:25', '2024-01-19 08:13:25'),
(10, 'KKB', '0988795445', 1, 0, '2024-02-06 04:36:21', '2024-02-06 04:36:21'),
(11, 'KKB', '0988795445', 1, 0, '2024-02-14 01:28:05', '2024-02-14 01:28:05');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'superadministrator', 'SuperAdministrator', 'SuperAdministrator', '2017-10-23 19:00:54', '2017-10-23 19:00:54'),
(2, 'administrator', 'Administrator', 'Administrator', '2017-10-23 19:00:57', '2017-10-23 19:00:57'),
(3, 'ksk', 'Khám sức khỏe', 'Khám sức khỏe', '2017-10-23 19:00:58', '2017-10-23 19:00:58'),
(4, 'thungan', 'Thu ngân', 'Thu ngân', NULL, NULL),
(5, 'thungan-tonghop', 'Thu ngân tổng hợp', 'Thu ngân tổng hợp', NULL, NULL),
(6, 'vaccination', 'Vaccination', 'Vaccination', NULL, NULL),
(8, 'duoc', NULL, NULL, '2024-05-31 02:50:15', '2024-05-31 02:50:15');

-- --------------------------------------------------------

--
-- Table structure for table `role_user`
--

CREATE TABLE `role_user` (
  `role_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `user_type` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sarcov2_ct`
--

CREATE TABLE `sarcov2_ct` (
  `id` int(11) NOT NULL,
  `sarcov2_ctu_id` int(11) NOT NULL,
  `ten_khoa` varchar(255) NOT NULL,
  `so_luong` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sarcov2_ct`
--

INSERT INTO `sarcov2_ct` (`id`, `sarcov2_ctu_id`, `ten_khoa`, `so_luong`) VALUES
(1, 1, 'Khoa Truyền nhiễm', 37),
(2, 1, 'Khoa Nội TH CS1', 22),
(3, 1, 'Khoa Nhi CS1', 20),
(4, 1, 'Khoa CCCĐ', 14),
(5, 1, 'Khoa Ngoại CT', 8),
(6, 1, 'Khoa Lọc máu - TNT', 7),
(7, 1, 'Khoa Nội TM - NT', 5),
(8, 1, 'Khoa Ung bướu CS2', 4),
(9, 1, 'Khoa Nội TH CS2', 4),
(10, 1, 'Khoa HSTC', 4),
(11, 1, 'Khoa cấp cứu CS2', 2),
(12, 1, 'Khoa Ngoại TH', 1),
(13, 1, 'Khoa PT GMHS', 1),
(14, 2, 'Khoa Truyền nhiễm', 37),
(15, 2, 'Khoa Nội TH CS1', 20),
(16, 2, 'Khoa Nhi CS1', 18),
(17, 2, 'Khoa CCCĐ', 14),
(18, 2, 'Khoa Ngoại CT', 8),
(19, 2, 'Khoa Lọc máu - TNT', 7),
(20, 2, 'Khoa Nội TM - NT', 5),
(21, 2, 'Khoa Ung bướu CS2', 4),
(22, 2, 'Khoa HSTC', 4),
(23, 2, 'Khoa Nội TH CS2', 4),
(24, 2, 'Khoa Ngoại TH', 1),
(25, 2, 'Khoa cấp cứu CS2', 1),
(26, 2, 'Khoa PT GMHS', 1),
(27, 3, 'Khoa Truyền nhiễm', 38),
(28, 3, 'Khoa Nhi CS1', 17),
(29, 3, 'Khoa CCCĐ', 16),
(30, 3, 'Khoa Nội TH CS1', 15),
(31, 3, 'Khoa Lọc máu - TNT', 9),
(32, 3, 'Khoa Nội TM - NT', 5),
(33, 3, 'Khoa Nội TH CS2', 5),
(34, 3, 'Khoa Ung bướu CS2', 4),
(35, 3, 'Khoa Ngoại CT', 3),
(36, 3, 'Khoa HSTC', 3),
(37, 3, 'Khoa Ngoại TH', 2),
(38, 3, 'Khoa cấp cứu CS2', 1),
(39, 4, 'Khoa Truyền nhiễm', 38),
(40, 4, 'Khoa Nhi CS1', 19),
(41, 4, 'Khoa CCCĐ', 18),
(42, 4, 'Khoa Nội TH CS1', 15),
(43, 4, 'Khoa Lọc máu - TNT', 9),
(44, 4, 'Khoa Nội TM - NT', 5),
(45, 4, 'Khoa Nội TH CS2', 5),
(46, 4, 'Khoa Ung bướu CS2', 4),
(47, 4, 'Khoa HSTC', 3),
(48, 4, 'Khoa Ngoại CT', 2),
(49, 4, 'Khoa Ngoại TH', 2),
(50, 4, 'Khoa cấp cứu CS2', 1),
(51, 5, 'Khoa Truyền nhiễm', 37),
(52, 5, 'Khoa Nhi CS1', 20),
(53, 5, 'Khoa Nội TH CS1', 15),
(54, 5, 'Khoa Lọc máu - TNT', 9),
(55, 5, 'Khoa CCCĐ', 9),
(56, 5, 'Khoa Nội TM - NT', 5),
(57, 5, 'Khoa Nội TH CS2', 5),
(58, 5, 'Khoa Ung bướu CS2', 4),
(59, 5, 'Khoa HSTC', 3),
(60, 5, 'Khoa Ngoại CT', 2),
(61, 5, 'Khoa Ngoại TH', 2),
(62, 5, 'Khoa cấp cứu CS2', 1),
(63, 6, 'Khoa Truyền nhiễm', 36),
(64, 6, 'Khoa Nhi CS1', 28),
(65, 6, 'Khoa Nội TH CS1', 11),
(66, 6, 'Khoa Lọc máu - TNT', 10),
(67, 6, 'Khoa CCCĐ', 9),
(68, 6, 'Khoa Nội TH CS2', 6),
(69, 6, 'Khoa Nội TM - NT', 4),
(70, 6, 'Khoa Ung bướu CS2', 3),
(71, 6, 'Khoa Ngoại TH', 2),
(72, 6, 'Khoa Ngoại CT', 2),
(73, 6, 'Khoa HSTC', 2),
(74, 7, 'Khoa Truyền nhiễm', 34),
(75, 7, 'Khoa Lọc máu - TNT', 11),
(76, 7, 'Khoa Nội TH CS1', 10),
(77, 7, 'Khoa CCCĐ', 9),
(78, 7, 'Khoa Nhi CS1', 9),
(79, 7, 'Khoa Nội TH CS2', 6),
(80, 7, 'Khoa Nội TM - NT', 4),
(81, 7, 'Khoa Ung bướu CS2', 3),
(82, 7, 'Khoa Ngoại TH', 3),
(83, 7, 'Khoa Ngoại CT', 2),
(84, 7, 'Khoa HSTC', 1),
(85, 8, 'Khoa Truyền nhiễm', 31),
(86, 8, 'Khoa Lọc máu - TNT', 10),
(87, 8, 'Khoa Nhi CS1', 10),
(88, 8, 'Khoa CCCĐ', 8),
(89, 8, 'Khoa Nội TH CS2', 6),
(90, 8, 'Khoa Nội TH CS1', 5),
(91, 8, 'Khoa Nội TM - NT', 5),
(92, 8, 'Khoa Ngoại TH', 3),
(93, 8, 'Khoa Ung bướu CS2', 2),
(94, 8, 'Khoa Phụ Sản CS1', 1),
(95, 8, 'Khoa HSTC', 1),
(96, 8, 'Khoa Ngoại CT', 1),
(97, 8, 'Khoa PT GMHS', 1),
(98, 9, 'Khoa Truyền nhiễm', 31),
(99, 9, 'Khoa Lọc máu - TNT', 11),
(100, 9, 'Khoa CCCĐ', 9),
(101, 9, 'Khoa Nhi CS1', 6),
(102, 9, 'Khoa Nội TH CS2', 6),
(103, 9, 'Khoa Nội TH CS1', 5),
(104, 9, 'Khoa Nội TM - NT', 3),
(105, 9, 'Khoa Ngoại TH', 1),
(106, 9, 'Khoa Ung bướu CS2', 1),
(107, 9, 'Khoa PT GMHS', 1),
(108, 9, 'Khoa Ngoại CT', 1),
(109, 9, 'Khoa HSTC', 1),
(110, 10, 'Khoa Truyền nhiễm', 19),
(111, 10, 'Khoa CCCĐ', 9),
(112, 10, 'Khoa Lọc máu - TNT', 9),
(113, 10, 'Khoa Nội TH CS2', 5),
(114, 10, 'Khoa Nội TH CS1', 5),
(115, 10, 'Khoa Nhi CS1', 4),
(116, 10, 'Khoa Ngoại TH', 1),
(117, 10, 'Khoa Ung bướu CS2', 1),
(118, 10, 'Khoa PT GMHS', 1),
(119, 10, 'Khoa Nội TM - NT', 1),
(120, 10, 'Khoa Ngoại CT', 1),
(121, 10, 'Khoa HSTC', 1),
(122, 11, 'Khoa Truyền nhiễm', 19),
(123, 11, 'Khoa CCCĐ', 9),
(124, 11, 'Khoa Lọc máu - TNT', 8),
(125, 11, 'Khoa Nhi CS1', 7),
(126, 11, 'Khoa Nội TH CS1', 5),
(127, 11, 'Khoa Nội TH CS2', 5),
(128, 11, 'Khoa PT GMHS', 2),
(129, 11, 'Khoa Ngoại TH', 1),
(130, 11, 'Khoa Ung bướu CS2', 1),
(131, 11, 'Khoa Nội TM - NT', 1),
(132, 11, 'Khoa Ngoại CT', 1),
(133, 11, 'Khoa HSTC', 1),
(134, 12, 'Khoa Truyền nhiễm', 20),
(135, 12, 'Khoa CCCĐ', 8),
(136, 12, 'Khoa Lọc máu - TNT', 8),
(137, 12, 'Khoa Nội TH CS1', 6),
(138, 12, 'Khoa Nhi CS1', 6),
(139, 12, 'Khoa Nội TH CS2', 5),
(140, 12, 'Khoa PT GMHS', 2),
(141, 12, 'Khoa Ngoại TH', 1),
(142, 12, 'Khoa HSTC', 1),
(143, 12, 'Khoa Nội TM - NT', 1),
(144, 12, 'Khoa Ung bướu CS2', 1),
(145, 13, 'Khoa Truyền nhiễm', 17),
(146, 13, 'Khoa Nội TH CS1', 8),
(147, 13, 'Khoa Nhi CS1', 5),
(148, 13, 'Khoa CCCĐ', 5),
(149, 13, 'Khoa Nội TH CS2', 5),
(150, 13, 'Khoa Lọc máu - TNT', 5),
(151, 13, 'Khoa HSTC', 2),
(152, 13, 'Khoa Ngoại TH', 1),
(153, 13, 'Khoa Ung bướu CS2', 1),
(154, 13, 'Khoa Nội TM - NT', 1),
(155, 13, 'Khoa PT GMHS', 1),
(156, 14, 'Khoa Truyền nhiễm', 13),
(157, 14, 'Khoa Nội TH CS1', 6),
(158, 14, 'Khoa Nhi CS1', 6),
(159, 14, 'Khoa CCCĐ', 5),
(160, 14, 'Khoa Nội TH CS2', 5),
(161, 14, 'Khoa Lọc máu - TNT', 3),
(162, 14, 'Khoa HSTC', 2),
(163, 14, 'Khoa Ung bướu CS2', 1),
(164, 14, 'Khoa Nội TM - NT', 1),
(165, 14, 'Khoa PT GMHS', 1),
(166, 15, 'Khoa Truyền nhiễm', 11),
(167, 15, 'Khoa Nội TH CS1', 5),
(168, 15, 'Khoa Nhi CS1', 5),
(169, 15, 'Khoa Nội TH CS2', 3),
(170, 15, 'Khoa Lọc máu - TNT', 3),
(171, 15, 'Khoa CCCĐ', 2),
(172, 15, 'Khoa Nội TM - NT', 2),
(173, 15, 'Khoa HSTC', 1),
(174, 15, 'Khoa Ung bướu CS2', 1),
(175, 15, 'Khoa Ngoại CT', 1),
(176, 16, 'Khoa Truyền nhiễm', 9),
(177, 16, 'Khoa Nhi CS1', 7),
(178, 16, 'Khoa Nội TH CS1', 6),
(179, 16, 'Khoa Nội TH CS2', 3),
(180, 16, 'Khoa Lọc máu - TNT', 3),
(181, 16, 'Khoa Nội TM - NT', 2),
(182, 16, 'Khoa HSTC', 1),
(183, 16, 'Khoa Ung bướu CS2', 1),
(184, 16, 'Khoa CCCĐ', 1),
(185, 16, 'Khoa Ngoại CT', 1),
(186, 17, 'Khoa Truyền nhiễm', 12),
(187, 17, 'Khoa Nhi CS1', 4),
(188, 17, 'Khoa Ngoại CT', 3),
(189, 17, 'Khoa Nội TH CS1', 2),
(190, 17, 'Khoa Nội TM - NT', 2),
(191, 17, 'Khoa HSTC', 1),
(192, 17, 'Khoa Nội TH CS2', 1),
(193, 18, 'Khoa Truyền nhiễm', 12),
(194, 18, 'Khoa Nhi CS1', 4),
(195, 18, 'Khoa Ngoại CT', 3),
(196, 18, 'Khoa Nội TH CS1', 2),
(197, 18, 'Khoa Nội TM - NT', 2),
(198, 18, 'Khoa HSTC', 1),
(199, 18, 'Khoa Nội TH CS2', 1),
(200, 19, 'Khoa Truyền nhiễm', 16),
(201, 19, 'Khoa Nhi CS1', 4),
(202, 19, 'Khoa Ngoại CT', 4),
(203, 19, 'Khoa Nội TH CS1', 2),
(204, 19, 'Khoa Nội TM - NT', 2),
(205, 19, 'Khoa HSTC', 1),
(206, 19, 'Khoa Nội TH CS2', 1),
(207, 20, 'Khoa Truyền nhiễm', 13),
(208, 20, 'Khoa Ngoại CT', 2),
(209, 20, 'Khoa Nội TM - NT', 2),
(210, 20, 'Khoa Nhi CS1', 1),
(211, 20, 'Khoa HSTC', 1),
(212, 20, 'Khoa Lọc máu - TNT', 1),
(213, 20, 'Khoa Nội TH CS1', 1),
(214, 20, 'Khoa Nội TH CS2', 1),
(215, 21, 'Khoa Truyền nhiễm', 14),
(216, 21, 'Khoa Nội TM - NT', 2),
(217, 21, 'Khoa HSTC', 1),
(218, 21, 'Khoa Lọc máu - TNT', 1),
(219, 21, 'Khoa Nội TH CS1', 1),
(220, 21, 'Khoa Nội TH CS2', 1),
(221, 22, 'Khoa Truyền nhiễm', 16),
(222, 22, 'Khoa Nội TH CS1', 1),
(223, 22, 'Khoa Nội TH CS2', 1),
(224, 22, 'Khoa Lọc máu - TNT', 1),
(225, 22, 'Khoa Ngoại TH', 1),
(226, 22, 'Khoa Ngoại CT', 1),
(227, 22, 'Khoa HSTC', 1),
(228, 23, 'Khoa Truyền nhiễm', 15),
(229, 23, 'Khoa Nhi CS1', 3),
(230, 23, 'Khoa Ngoại CT', 2),
(231, 23, 'Khoa Lọc máu - TNT', 1),
(232, 23, 'Khoa HSTC', 1),
(233, 23, 'Khoa Nội TH CS2', 1),
(234, 23, 'Khoa Ngoại TH', 1),
(235, 24, 'Khoa Truyền nhiễm', 8),
(236, 24, 'Khoa Nhi CS1', 5),
(237, 24, 'Khoa Ngoại CT', 2),
(238, 24, 'Khoa Lọc máu - TNT', 1),
(239, 24, 'Khoa Nội TH CS2', 1),
(240, 24, 'Khoa HSTC', 1),
(241, 25, 'Khoa Truyền nhiễm', 10),
(242, 25, 'Khoa Nhi CS1', 4),
(243, 25, 'Khoa Ngoại CT', 2),
(244, 25, 'Khoa Lọc máu - TNT', 1),
(245, 25, 'Khoa Nội TH CS2', 1),
(246, 25, 'Khoa HSTC', 1),
(247, 26, 'Khoa Truyền nhiễm', 11),
(248, 26, 'Khoa Nhi CS1', 6),
(249, 26, 'Khoa Ngoại CT', 2),
(250, 26, 'Khoa Lọc máu - TNT', 1),
(251, 26, 'Khoa Nội TH CS2', 1),
(252, 26, 'Khoa HSTC', 1),
(253, 27, 'Khoa Truyền nhiễm', 11),
(254, 27, 'Khoa Nhi CS1', 6),
(255, 27, 'Khoa Ngoại CT', 2),
(256, 27, 'Khoa Lọc máu - TNT', 1),
(257, 27, 'Khoa Nội TH CS2', 1),
(258, 27, 'Khoa HSTC', 1),
(259, 28, 'Khoa Truyền nhiễm', 9),
(260, 28, 'Khoa Ngoại CT', 2),
(261, 28, 'Khoa Nhi CS1', 1),
(262, 28, 'Khoa Nội TH CS2', 1),
(263, 28, 'Khoa HSTC', 1),
(264, 29, 'Khoa Truyền nhiễm', 6),
(265, 29, 'Khoa Nhi CS1', 2),
(266, 29, 'TT CSSM theo yêu cầu', 1),
(267, 29, 'Khoa Ngoại CT', 1),
(268, 29, 'Khoa Nội TH CS2', 1),
(269, 30, 'Khoa Truyền nhiễm', 5),
(270, 30, 'Khoa Nhi CS1', 3),
(271, 30, 'TT CSSM theo yêu cầu', 1),
(272, 30, 'Khoa Ngoại CT', 1),
(273, 30, 'Khoa Nội TH CS2', 1),
(274, 31, 'Khoa Truyền nhiễm', 3),
(275, 31, 'Khoa Nhi CS1', 2),
(276, 31, 'Khoa Nội TH CS2', 1),
(277, 32, 'Khoa Truyền nhiễm', 3),
(278, 32, 'Khoa Nhi CS1', 2),
(279, 32, 'Khoa Nội TH CS2', 1),
(280, 33, 'Khoa Truyền nhiễm', 3),
(281, 33, 'Khoa Nhi CS1', 2),
(282, 33, 'Khoa Nội TH CS2', 1),
(283, 34, 'Khoa Nhi CS1', 2),
(284, 34, 'Khoa Truyền nhiễm', 1),
(285, 35, 'Khoa Truyền nhiễm', 2),
(286, 35, 'Khoa Nhi CS1', 2),
(287, 35, 'Khoa Lọc máu - TNT', 1),
(288, 36, 'Khoa CCCĐ', 13),
(289, 36, 'Khoa Truyền nhiễm', 9),
(290, 36, 'Khoa Nhi CS1', 1),
(291, 37, 'Khoa Truyền nhiễm', 10),
(292, 37, 'Khoa CCCĐ', 7),
(293, 37, 'Khoa Nhi CS1', 1),
(294, 37, 'Khoa HSTC', 1),
(295, 38, 'Khoa Truyền nhiễm', 15),
(296, 38, 'Khoa CCCĐ', 9),
(297, 38, 'Khoa Nhi CS1', 1),
(298, 38, 'Khoa HSTC', 1),
(299, 39, 'Khoa Truyền nhiễm', 14),
(300, 39, 'Khoa CCCĐ', 10),
(301, 39, 'Khoa Nhi CS1', 1),
(302, 39, 'Khoa HSTC', 1),
(303, 40, 'Khoa CCCĐ', 8),
(304, 40, 'Khoa Truyền nhiễm', 5),
(305, 40, 'Khoa Nhi CS1', 1),
(306, 40, 'Khoa Nội TH CS1', 1),
(307, 40, 'Khoa HSTC', 1),
(308, 41, 'Khoa CCCĐ', 9),
(309, 41, 'Khoa Truyền nhiễm', 6),
(310, 41, 'Khoa Nhi CS1', 2),
(311, 41, 'Khoa HSTC', 1),
(312, 42, 'Khoa CCCĐ', 9),
(313, 42, 'Khoa Truyền nhiễm', 7),
(314, 42, 'Khoa Nhi CS1', 2),
(315, 42, 'Khoa HSTC', 1),
(316, 43, 'Khoa CCCĐ', 9),
(317, 43, 'Khoa Truyền nhiễm', 5),
(318, 43, 'Khoa Nhi CS1', 1),
(319, 43, 'Khoa Nội TH CS1', 1),
(320, 43, 'Khoa HSTC', 1),
(321, 44, 'Khoa CCCĐ', 9),
(322, 44, 'Khoa Truyền nhiễm', 4),
(323, 44, 'Khoa Nhi CS1', 1),
(324, 44, 'Khoa Nội TH CS1', 1),
(325, 44, 'Khoa HSTC', 1),
(326, 45, 'Khoa CCCĐ', 9),
(327, 45, 'Khoa Truyền nhiễm', 8),
(328, 45, 'Khoa Nhi CS1', 1),
(329, 45, 'Khoa Nội TH CS1', 1),
(330, 45, 'Khoa HSTC', 1),
(331, 46, 'Khoa Truyền nhiễm', 7),
(332, 46, 'Khoa CCCĐ', 6),
(333, 46, 'Khoa Nhi CS1', 1),
(334, 46, 'Khoa Nội TH CS1', 1),
(335, 46, 'Khoa HSTC', 1),
(336, 47, 'Khoa CCCĐ', 5),
(337, 47, 'Khoa Truyền nhiễm', 4),
(338, 47, 'Khoa Nhi CS1', 1),
(339, 47, 'Khoa Nội TH CS1', 1),
(340, 48, 'Khoa Truyền nhiễm', 4),
(341, 48, 'Khoa CCCĐ', 3),
(342, 48, 'Khoa Nội TH CS1', 1),
(343, 48, 'Khoa Nhi CS1', 1),
(344, 49, 'Khoa Truyền nhiễm', 7),
(345, 49, 'Khoa CCCĐ', 4),
(346, 49, 'Khoa Nội TH CS1', 2),
(347, 49, 'Khoa Nhi CS1', 1),
(348, 50, 'Khoa Truyền nhiễm', 10),
(349, 50, 'Khoa Nhi CS1', 1),
(350, 50, 'Khoa Ngoại CT', 1),
(351, 50, 'Khoa Nội TH CS1', 1),
(352, 51, 'Khoa Truyền nhiễm', 9),
(353, 51, 'Khoa Nhi CS1', 1),
(354, 51, 'Khoa Nội TH CS1', 1),
(355, 51, 'Khoa CCCĐ', 1),
(356, 52, 'Khoa Truyền nhiễm', 7),
(357, 52, 'Khoa Nhi CS1', 1),
(358, 52, 'Khoa Nội TH CS1', 1),
(359, 52, 'Khoa CCCĐ', 1),
(360, 53, 'Khoa Truyền nhiễm', 7),
(361, 53, 'Khoa Nhi CS1', 1),
(362, 54, 'Khoa Truyền nhiễm', 5),
(363, 54, 'Khoa Nhi CS1', 1),
(364, 55, 'Khoa Truyền nhiễm', 5),
(365, 55, 'Khoa Nhi CS1', 1),
(366, 55, 'Khoa Ngoại CT', 1),
(367, 56, 'Khoa Truyền nhiễm', 7),
(368, 56, 'Khoa Nhi CS1', 1),
(369, 56, 'Khoa Ngoại CT', 1),
(370, 57, 'Khoa Truyền nhiễm', 6),
(371, 57, 'Khoa Nhi CS1', 1),
(372, 57, 'Khoa Ngoại CT', 1),
(373, 58, 'Khoa Truyền nhiễm', 6),
(374, 58, 'Khoa Ngoại CT', 1),
(375, 59, 'Khoa Truyền nhiễm', 6),
(376, 59, 'Khoa Ngoại CT', 1),
(377, 59, 'Khoa CCCĐ', 1),
(378, 60, 'Khoa Truyền nhiễm', 5),
(379, 60, 'Khoa Ngoại CT', 1),
(380, 61, 'Khoa Truyền nhiễm', 2),
(381, 61, 'Khoa Ngoại CT', 1),
(382, 62, 'Khoa Truyền nhiễm', 3),
(383, 62, 'Khoa Ngoại CT', 1),
(384, 63, 'Khoa Truyền nhiễm', 6),
(385, 63, 'Khoa Ngoại CT', 1),
(386, 64, 'Khoa Truyền nhiễm', 4),
(387, 64, 'Khoa Ngoại CT', 1),
(388, 65, 'Khoa Truyền nhiễm', 5),
(389, 65, 'Khoa Ngoại CT', 1),
(390, 66, 'Khoa Truyền nhiễm', 5),
(391, 67, 'Khoa Truyền nhiễm', 7),
(392, 68, 'Khoa Truyền nhiễm', 5),
(393, 69, 'Khoa Truyền nhiễm', 5),
(394, 70, 'Khoa Truyền nhiễm', 6),
(395, 71, 'Khoa Truyền nhiễm', 4),
(396, 71, 'Khoa CCCĐ', 1),
(397, 72, 'Khoa Truyền nhiễm', 1),
(398, 72, 'Khoa CCCĐ', 1),
(399, 73, 'Khoa Truyền nhiễm', 1),
(400, 73, 'Khoa CCCĐ', 1),
(401, 74, 'Khoa Truyền nhiễm', 1),
(402, 74, 'Khoa CCCĐ', 1),
(403, 75, 'Khoa Truyền nhiễm', 2),
(404, 75, 'Khoa CCCĐ', 1),
(405, 76, 'Khoa Truyền nhiễm', 3),
(406, 76, 'Khoa CCCĐ', 1),
(407, 77, 'Khoa Truyền nhiễm', 4),
(408, 77, 'Khoa CCCĐ', 1),
(409, 78, 'Khoa Truyền nhiễm', 1),
(410, 79, 'TT CSSM theo yêu cầu', 1),
(411, 80, 'Khoa Truyền nhiễm', 1),
(412, 80, 'Khoa Nhi CS1', 1),
(413, 80, 'Khoa Ngoại TH', 1),
(414, 81, 'Khoa Thận - Niệu - Lọc máu', 1),
(415, 81, 'Khoa Ngoại TH', 1),
(416, 81, 'Khoa Truyền nhiễm', 1),
(417, 82, 'Khoa Thận - Niệu - Lọc máu', 1),
(418, 82, 'Khoa Truyền nhiễm', 1),
(419, 82, 'Khoa Ngoại TH', 1),
(420, 83, 'Khoa Thận - Niệu - Lọc máu', 1),
(421, 84, 'Khoa Nhi CS1', 1),
(422, 85, 'Khoa Truyền nhiễm', 2),
(423, 86, 'Khoa Truyền nhiễm', 2),
(424, 87, 'Khoa Truyền nhiễm', 3),
(425, 87, 'Khoa Phụ Sản CS1', 1),
(426, 87, 'Khoa Nội TH CS1', 1),
(427, 88, 'Khoa Truyền nhiễm', 3),
(428, 88, 'Khoa Phụ Sản CS1', 1),
(429, 89, 'Khoa Truyền nhiễm', 1),
(430, 89, 'Khoa PT GMHS', 1),
(431, 90, 'Khoa Truyền nhiễm', 1),
(432, 90, 'TT CSSM theo yêu cầu', 1),
(433, 91, 'Khoa Truyền nhiễm', 3),
(434, 91, 'Khoa Phụ Sản CS1', 1),
(435, 92, 'Khoa Truyền nhiễm', 3),
(436, 92, 'Khoa Phụ Sản CS1', 1),
(437, 93, 'Khoa Truyền nhiễm', 2),
(438, 93, 'Khoa Phụ Sản CS1', 1),
(439, 93, 'Khoa Nhi CS1', 1),
(440, 94, 'Khoa Phụ Sản CS1', 1),
(441, 94, 'Khoa Nhi CS1', 1),
(442, 95, 'Khoa Phụ Sản CS1', 1),
(443, 95, 'Khoa Nhi CS1', 1),
(444, 96, 'Khoa Nhi CS1', 1),
(445, 97, 'Khoa Nhi CS1', 1),
(446, 97, 'Khoa Truyền nhiễm', 1),
(447, 98, 'Khoa Nhi CS1', 1),
(448, 98, 'Khoa Truyền nhiễm', 1),
(449, 99, 'Khoa Nhi CS1', 1),
(450, 99, 'Khoa Truyền nhiễm', 1),
(451, 100, 'Khoa Truyền nhiễm', 1),
(452, 101, 'Khoa Truyền nhiễm', 3),
(453, 102, 'Khoa Truyền nhiễm', 3),
(454, 103, 'Khoa Truyền nhiễm', 3),
(455, 104, 'Khoa Truyền nhiễm', 3),
(456, 105, 'Khoa Nhi CS1', 1),
(457, 106, 'Khoa Nhi CS1', 1),
(458, 106, 'Khoa Truyền nhiễm', 1),
(459, 107, 'Khoa Nhi CS1', 1),
(460, 107, 'Khoa Truyền nhiễm', 1),
(461, 108, 'Khoa Truyền nhiễm', 1),
(462, 109, 'Khoa Truyền nhiễm', 1),
(463, 109, 'Khoa Ngoại CT', 1),
(464, 110, 'Khoa Truyền nhiễm', 1),
(465, 110, 'TT CSSM theo yêu cầu', 1),
(466, 111, 'TT CSSM theo yêu cầu', 1),
(467, 112, 'Khoa Ngoại CT', 1),
(468, 113, 'Khoa Ngoại CT', 1),
(469, 114, 'Khoa Ngoại CT', 1),
(470, 115, 'Khoa Ngoại CT', 1),
(471, 116, 'Khoa Ngoại CT', 1),
(472, 117, 'Khoa Ngoại CT', 1),
(473, 118, 'Khoa TMH CS1', 1),
(474, 119, 'Khoa TMH CS1', 1),
(475, 120, 'Khoa TMH CS1', 1),
(476, 121, 'Khoa TMH CS1', 1),
(477, 122, 'Khoa TMH CS1', 1),
(478, 123, 'Khoa TMH CS1', 1),
(479, 124, 'Khoa TMH CS1', 1),
(480, 125, 'Khoa TMH CS1', 1),
(481, 126, 'Khoa HSTC', 1),
(482, 127, 'Khoa HSTC', 1),
(483, 128, 'Khoa Ngoại CT', 1),
(484, 129, 'Khoa Ngoại CT', 1),
(485, 130, 'Khoa Ngoại CT', 1),
(486, 131, 'Khoa Ngoại CT', 1),
(487, 132, 'Khoa Ngoại CT', 1),
(488, 133, 'Khoa Ngoại CT', 1),
(489, 134, 'Khoa Ngoại CT', 1),
(490, 135, 'Khoa Ngoại CT', 1),
(491, 136, 'Khoa Ngoại CT', 1),
(492, 137, 'Khoa Ngoại CT', 1),
(493, 138, 'Khoa Ngoại CT', 1),
(494, 139, 'Khoa Ngoại CT', 1),
(495, 140, 'Khoa Ngoại CT', 1),
(496, 141, 'Khoa Ngoại CT', 1),
(497, 142, 'Khoa Nhi CS1', 1),
(498, 143, 'Khoa Nội TH CS1', 1),
(499, 144, 'Khoa CCCĐ', 1),
(500, 145, 'Khoa CCCĐ', 1),
(501, 146, 'Khoa Nhi CS1', 1),
(502, 146, 'Khoa CCCĐ', 1),
(503, 147, 'Khoa Nhi CS1', 1),
(504, 148, 'Khoa Nhi CS1', 1),
(505, 149, 'Khoa Nhi CS1', 1),
(506, 150, 'Khoa Nhi CS1', 1),
(507, 151, 'Khoa Nhi CS1', 1),
(508, 151, 'Khoa Ngoại CT', 1),
(509, 152, 'Khoa Nhi CS1', 1),
(510, 152, 'Khoa Ngoại CT', 1),
(511, 153, 'Khoa Ngoại CT', 1),
(512, 154, 'Khoa Ngoại CT', 1),
(513, 155, 'Khoa Ngoại CT', 1),
(514, 156, 'Khoa Ngoại CT', 1),
(515, 157, 'Khoa Ngoại CT', 1),
(516, 158, 'Khoa Nhi CS1', 1),
(517, 158, 'Khoa Ngoại CT', 1),
(518, 159, 'Khoa Nhi CS1', 1),
(519, 159, 'Khoa Ngoại CT', 1),
(520, 160, 'Khoa Nhi CS1', 1),
(521, 161, 'Khoa Nhi CS1', 1),
(522, 162, 'Khoa Nhi CS1', 1),
(523, 163, 'Khoa Nhi CS1', 1),
(524, 164, 'Khoa Nhi CS1', 1),
(525, 165, 'Khoa Nhi CS1', 1),
(526, 166, 'Khoa Nhi CS1', 1),
(527, 167, 'Khoa Nhi CS1', 1),
(528, 168, 'Khoa Nhi CS1', 1),
(529, 169, 'Khoa Nhi CS1', 1),
(530, 170, 'Khoa Nhi CS1', 1),
(531, 171, 'Khoa HSTC', 1),
(532, 172, 'Khoa HSTC', 1),
(533, 172, 'Khoa CCCĐ', 1),
(534, 173, 'Khoa HSTC', 1),
(535, 174, 'Khoa HSTC', 1),
(536, 175, 'Khoa HSTC', 1),
(537, 176, 'Khoa HSTC', 1),
(538, 177, 'TT CSSM theo yêu cầu', 1),
(539, 178, 'TT CSSM theo yêu cầu', 1),
(540, 179, 'Khoa Phụ Sản CS1', 1),
(541, 179, 'Khoa Ngoại CT', 1),
(542, 180, 'Khoa CCCĐ', 2),
(543, 181, 'Khoa CCCĐ', 1),
(544, 182, 'Khoa CCCĐ', 1),
(545, 183, 'Khoa CCCĐ', 1),
(546, 183, 'Khoa Nội TM - NT', 1),
(547, 184, 'Khoa Truyền nhiễm', 2),
(548, 184, 'Khoa Nhi CS1', 1),
(549, 185, 'Khoa Truyền nhiễm', 3),
(550, 186, 'Khoa Truyền nhiễm', 4),
(551, 187, 'Khoa Truyền nhiễm', 5),
(552, 188, 'Khoa Truyền nhiễm', 5),
(553, 189, 'Khoa Truyền nhiễm', 2),
(554, 190, 'Khoa Truyền nhiễm', 2),
(555, 191, 'Khoa Truyền nhiễm', 3),
(556, 192, 'Khoa Truyền nhiễm', 1),
(557, 193, 'Khoa Truyền nhiễm', 4),
(558, 194, 'Khoa Truyền nhiễm', 6),
(559, 195, 'Khoa Truyền nhiễm', 6),
(560, 196, 'Khoa Truyền nhiễm', 5),
(561, 197, 'Khoa Truyền nhiễm', 5),
(562, 198, 'Khoa Truyền nhiễm', 4),
(563, 199, 'Khoa Truyền nhiễm', 4),
(564, 199, 'Khoa Thận - Niệu - Lọc máu', 1),
(565, 200, 'Khoa Truyền nhiễm', 4),
(566, 201, 'Khoa Truyền nhiễm', 3),
(567, 202, 'Khoa Nhi CS1', 1),
(568, 203, 'Khoa Truyền nhiễm', 2),
(569, 204, 'Khoa Truyền nhiễm', 2),
(570, 205, 'Khoa Truyền nhiễm', 3),
(571, 206, 'Khoa Truyền nhiễm', 2),
(572, 207, 'Khoa Truyền nhiễm', 3),
(573, 208, 'Khoa Truyền nhiễm', 7),
(574, 208, 'Khoa CCCĐ', 1),
(575, 209, 'Khoa Truyền nhiễm', 6),
(576, 209, 'Khoa CCCĐ', 1),
(577, 210, 'Khoa Truyền nhiễm', 8),
(578, 210, 'Khoa CCCĐ', 1),
(579, 211, 'Khoa Truyền nhiễm', 9),
(580, 211, 'Khoa CCCĐ', 1),
(581, 212, 'Khoa Truyền nhiễm', 5),
(582, 212, 'Khoa CCCĐ', 2),
(583, 213, 'Khoa Truyền nhiễm', 4),
(584, 213, 'Khoa CCCĐ', 3),
(585, 214, 'Khoa Truyền nhiễm', 4),
(586, 214, 'Khoa CCCĐ', 2),
(587, 215, 'Khoa Truyền nhiễm', 4),
(588, 215, 'Khoa CCCĐ', 3),
(589, 216, 'Khoa CCCĐ', 2),
(590, 216, 'Khoa Truyền nhiễm', 1),
(591, 217, 'Khoa Truyền nhiễm', 3),
(592, 217, 'Khoa CCCĐ', 2),
(593, 218, 'Khoa Truyền nhiễm', 4),
(594, 218, 'Khoa Nhi CS1', 1),
(595, 218, 'Khoa CCCĐ', 1),
(596, 219, 'Khoa Truyền nhiễm', 4),
(597, 219, 'Khoa Nhi CS1', 1),
(598, 219, 'Khoa CCCĐ', 1),
(599, 220, 'Khoa Truyền nhiễm', 6),
(600, 220, 'Khoa CCCĐ', 1),
(601, 221, 'Khoa Truyền nhiễm', 6),
(602, 221, 'Khoa CCCĐ', 1),
(603, 222, 'Khoa Truyền nhiễm', 7),
(604, 222, 'Khoa CCCĐ', 1),
(605, 223, 'Khoa Truyền nhiễm', 6),
(606, 223, 'Khoa CCCĐ', 1),
(607, 223, 'Khoa HSTC', 1),
(608, 224, 'Khoa Truyền nhiễm', 5),
(609, 224, 'Khoa HSTC', 1),
(610, 225, 'Khoa Truyền nhiễm', 5),
(611, 225, 'Khoa CCCĐ', 1),
(612, 225, 'Khoa HSTC', 1),
(613, 226, 'Khoa Truyền nhiễm', 2),
(614, 226, 'Khoa HSTC', 1),
(615, 227, 'Khoa Truyền nhiễm', 3),
(616, 227, 'Khoa HSTC', 1),
(617, 228, 'Khoa Truyền nhiễm', 3),
(618, 228, 'Khoa HSTC', 1),
(619, 229, 'Khoa Truyền nhiễm', 5),
(620, 229, 'Khoa HSTC', 1),
(621, 230, 'Khoa Truyền nhiễm', 3),
(622, 230, 'Khoa HSTC', 1),
(623, 231, 'Khoa Truyền nhiễm', 4),
(624, 231, 'Khoa Ngoại CT', 1),
(625, 231, 'Khoa HSTC', 1),
(626, 232, 'Khoa Truyền nhiễm', 4),
(627, 232, 'Khoa Ngoại CT', 1),
(628, 233, 'Khoa Truyền nhiễm', 3),
(629, 233, 'Khoa Ngoại CT', 1),
(630, 234, 'Khoa Truyền nhiễm', 2),
(631, 234, 'Khoa Ngoại CT', 2),
(632, 235, 'Khoa Truyền nhiễm', 2),
(633, 235, 'Khoa Ngoại CT', 2),
(634, 236, 'Khoa Ngoại CT', 2),
(635, 236, 'Khoa Truyền nhiễm', 1),
(636, 237, 'Khoa Truyền nhiễm', 3),
(637, 237, 'Khoa Ngoại CT', 2),
(638, 238, 'Khoa Truyền nhiễm', 2),
(639, 238, 'Khoa Ngoại CT', 2),
(640, 239, 'Khoa Truyền nhiễm', 4),
(641, 239, 'Khoa Ngoại CT', 2),
(642, 239, 'Khoa HSTC', 1),
(643, 240, 'Khoa Truyền nhiễm', 4),
(644, 240, 'Khoa HSTC', 1),
(645, 241, 'Khoa Truyền nhiễm', 1),
(646, 241, 'Khoa HSTC', 1),
(647, 242, 'Khoa Truyền nhiễm', 3),
(648, 242, 'Khoa HSTC', 1),
(649, 243, 'Khoa Truyền nhiễm', 4),
(650, 243, 'Khoa Ngoại CT', 1),
(651, 243, 'Khoa HSTC', 1),
(652, 244, 'Khoa Truyền nhiễm', 6),
(653, 244, 'Khoa Ngoại CT', 1),
(654, 244, 'Khoa HSTC', 1),
(655, 245, 'Khoa Truyền nhiễm', 6),
(656, 245, 'Khoa Ngoại CT', 1),
(657, 245, 'Khoa HSTC', 1),
(658, 246, 'Khoa Truyền nhiễm', 9),
(659, 246, 'Khoa Ngoại CT', 1),
(660, 247, 'Khoa Truyền nhiễm', 6),
(661, 247, 'Khoa Ngoại CT', 1),
(662, 248, 'Khoa Truyền nhiễm', 3),
(663, 249, 'Khoa Truyền nhiễm', 3),
(664, 249, 'Khoa Thận - Niệu - Lọc máu', 1),
(665, 250, 'Khoa Truyền nhiễm', 3),
(666, 250, 'Khoa Thận - Niệu - Lọc máu', 1),
(667, 251, 'Khoa Truyền nhiễm', 1),
(668, 252, 'Khoa Nhi CS1', 1),
(669, 252, 'Khoa Truyền nhiễm', 1),
(670, 253, 'Khoa Truyền nhiễm', 1),
(671, 254, 'Khoa CCCĐ', 1),
(672, 255, 'Khoa Nhi CS1', 1),
(673, 256, 'Khoa Truyền nhiễm', 4),
(674, 257, 'Khoa Truyền nhiễm', 4),
(675, 258, 'Khoa Truyền nhiễm', 2);

-- --------------------------------------------------------

--
-- Table structure for table `sarcov2_ctu`
--

CREATE TABLE `sarcov2_ctu` (
  `id` int(11) NOT NULL,
  `ngay_ctu` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `so_dky` int(11) DEFAULT NULL,
  `so_cky` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sarcov2_ctu`
--

INSERT INTO `sarcov2_ctu` (`id`, `ngay_ctu`, `so_dky`, `so_cky`) VALUES
(1, '2022-03-17 07:49:47', NULL, 129),
(2, '2022-03-17 10:00:01', 129, 124),
(3, '2022-03-18 10:00:02', 124, 118),
(4, '2022-03-19 10:00:02', 118, 121),
(5, '2022-03-20 10:00:02', 121, 112),
(6, '2022-03-21 10:00:02', 112, 113),
(7, '2022-03-22 10:00:02', 113, 92),
(8, '2022-03-23 10:00:02', 92, 84),
(9, '2022-03-24 10:00:02', 84, 76),
(10, '2022-03-25 10:00:02', 76, 57),
(11, '2022-03-26 10:00:02', 57, 60),
(12, '2022-03-27 10:00:02', 60, 59),
(13, '2022-03-28 10:00:02', 59, 51),
(14, '2022-03-29 10:00:02', 51, 43),
(15, '2022-03-30 10:00:02', 43, 34),
(16, '2022-03-31 10:00:02', 34, 34),
(17, '2022-04-01 10:00:02', 34, 25),
(18, '2022-04-02 10:00:02', 25, 25),
(19, '2022-04-03 10:00:02', 25, 30),
(20, '2022-04-04 10:00:03', 30, 22),
(21, '2022-04-05 10:00:03', 22, 20),
(22, '2022-04-06 10:00:03', 20, 22),
(23, '2022-04-07 10:00:03', 22, 24),
(24, '2022-04-08 10:00:02', 24, 18),
(25, '2022-04-09 10:00:02', 18, 19),
(26, '2022-04-10 10:00:02', 19, 22),
(27, '2022-04-11 10:00:02', 22, 22),
(28, '2022-04-12 10:00:03', 22, 14),
(29, '2022-04-13 10:00:02', 14, 11),
(30, '2022-04-14 10:00:03', 11, 11),
(31, '2022-04-15 10:00:02', 11, 6),
(32, '2022-04-16 10:00:03', 6, 6),
(33, '2022-04-17 10:00:03', 6, 6),
(34, '2022-04-18 10:00:03', 6, 3),
(35, '2022-04-19 10:00:02', 3, 5),
(36, '2023-04-24 13:56:08', 5, 23),
(37, '2023-04-25 09:00:03', 23, 19),
(38, '2023-04-26 09:00:03', 19, 26),
(39, '2023-04-27 09:00:03', 26, 26),
(40, '2023-04-28 09:00:03', 26, 16),
(41, '2023-04-29 09:00:03', 16, 18),
(42, '2023-04-30 09:00:03', 18, 19),
(43, '2023-05-01 09:00:03', 19, 17),
(44, '2023-05-02 09:00:03', 17, 16),
(45, '2023-05-03 09:00:03', 16, 20),
(46, '2023-05-04 09:00:03', 20, 16),
(47, '2023-05-05 09:00:03', 16, 11),
(48, '2023-05-06 09:00:03', 11, 9),
(49, '2023-05-07 09:00:02', 9, 14),
(50, '2023-05-08 09:00:02', 14, 13),
(51, '2023-05-09 09:00:03', 13, 12),
(52, '2023-05-10 09:00:03', 12, 10),
(53, '2023-05-11 09:00:03', 10, 8),
(54, '2023-05-12 09:00:02', 8, 6),
(55, '2023-05-13 09:00:02', 6, 7),
(56, '2023-05-14 09:00:02', 7, 9),
(57, '2023-05-15 09:00:03', 9, 8),
(58, '2023-05-16 09:00:02', 8, 7),
(59, '2023-05-17 09:00:02', 7, 8),
(60, '2023-05-18 09:00:02', 8, 6),
(61, '2023-05-19 09:00:02', 6, 3),
(62, '2023-05-20 09:00:02', 3, 4),
(63, '2023-05-21 09:00:03', 4, 7),
(64, '2023-05-22 09:00:03', 7, 5),
(65, '2023-05-23 09:00:04', 5, 6),
(66, '2023-05-24 09:00:03', 6, 5),
(67, '2023-05-25 09:00:02', 5, 7),
(68, '2023-05-26 09:00:02', 7, 5),
(69, '2023-05-27 09:00:03', 5, 5),
(70, '2023-05-28 09:00:03', 5, 6),
(71, '2023-05-29 09:00:03', 6, 5),
(72, '2023-05-30 09:00:03', 5, 2),
(73, '2023-05-31 09:00:03', 2, 2),
(74, '2023-06-01 09:00:02', 2, 2),
(75, '2023-06-02 09:00:03', 2, 3),
(76, '2023-06-03 09:00:03', 3, 4),
(77, '2023-06-04 09:00:03', 4, 5),
(78, '2023-06-05 09:00:03', 5, 1),
(79, '2023-06-08 09:00:03', 1, 1),
(80, '2023-06-09 09:00:03', 1, 3),
(81, '2023-06-10 09:00:03', 3, 3),
(82, '2023-06-11 09:00:03', 3, 3),
(83, '2023-06-12 09:00:03', 3, 1),
(84, '2023-06-18 09:00:03', 1, 1),
(85, '2023-06-26 09:00:03', 1, 2),
(86, '2023-06-27 09:00:03', 2, 2),
(87, '2023-06-28 09:00:02', 2, 5),
(88, '2023-06-29 09:00:02', 5, 4),
(89, '2023-06-30 09:00:02', 4, 2),
(90, '2023-07-01 09:00:02', 2, 2),
(91, '2023-07-02 09:00:02', 2, 4),
(92, '2023-07-03 09:00:02', 4, 4),
(93, '2023-07-04 09:00:02', 4, 4),
(94, '2023-07-06 09:00:02', 4, 2),
(95, '2023-07-07 09:00:02', 2, 2),
(96, '2023-07-08 09:00:02', 2, 1),
(97, '2023-07-09 09:00:02', 1, 2),
(98, '2023-07-10 09:00:03', 2, 2),
(99, '2023-07-11 09:00:03', 2, 2),
(100, '2023-07-12 09:00:03', 2, 1),
(101, '2023-07-13 09:00:02', 1, 3),
(102, '2023-07-14 09:00:02', 3, 3),
(103, '2023-07-15 09:00:02', 3, 3),
(104, '2023-07-16 09:00:02', 3, 3),
(105, '2023-07-18 09:00:02', 3, 1),
(106, '2023-07-19 09:00:02', 1, 2),
(107, '2023-07-20 09:00:02', 2, 2),
(108, '2023-07-21 09:00:02', 2, 1),
(109, '2023-07-22 09:00:03', 1, 2),
(110, '2023-07-23 09:00:02', 2, 2),
(111, '2023-07-24 09:00:03', 2, 1),
(112, '2023-07-25 09:00:03', 1, 1),
(113, '2023-07-26 09:00:03', 1, 1),
(114, '2023-07-27 09:00:03', 1, 1),
(115, '2023-07-28 09:00:03', 1, 1),
(116, '2023-07-29 09:00:03', 1, 1),
(117, '2023-07-30 09:00:03', 1, 1),
(118, '2023-07-31 09:00:03', 1, 1),
(119, '2023-08-01 09:00:03', 1, 1),
(120, '2023-08-02 09:00:02', 1, 1),
(121, '2023-08-03 09:00:03', 1, 1),
(122, '2023-08-04 09:00:03', 1, 1),
(123, '2023-08-05 09:00:03', 1, 1),
(124, '2023-08-06 09:00:03', 1, 1),
(125, '2023-08-07 09:00:03', 1, 1),
(126, '2023-08-15 09:00:03', 1, 1),
(127, '2023-08-16 09:00:03', 1, 1),
(128, '2023-08-17 09:00:03', 1, 1),
(129, '2023-08-18 09:00:03', 1, 1),
(130, '2023-08-19 09:00:03', 1, 1),
(131, '2023-08-20 09:00:03', 1, 1),
(132, '2023-08-21 09:00:03', 1, 1),
(133, '2023-08-22 09:00:03', 1, 1),
(134, '2023-08-23 09:00:03', 1, 1),
(135, '2023-08-24 09:00:03', 1, 1),
(136, '2023-08-29 09:00:04', 1, 1),
(137, '2023-08-31 09:00:03', 1, 1),
(138, '2023-09-01 09:00:03', 1, 1),
(139, '2023-09-02 09:00:04', 1, 1),
(140, '2023-09-03 09:00:07', 1, 1),
(141, '2023-09-04 09:00:05', 1, 1),
(142, '2023-09-11 09:00:03', 1, 1),
(143, '2023-09-19 09:00:03', 1, 1),
(144, '2023-09-25 09:00:03', 1, 1),
(145, '2023-10-01 09:00:04', 1, 1),
(146, '2023-10-02 09:00:03', 1, 2),
(147, '2023-10-07 09:00:04', 2, 1),
(148, '2023-10-08 09:00:04', 1, 1),
(149, '2023-10-09 09:00:03', 1, 1),
(150, '2023-10-10 09:00:05', 1, 1),
(151, '2023-10-16 09:00:03', 1, 2),
(152, '2023-10-17 09:00:05', 2, 2),
(153, '2023-10-18 09:00:05', 2, 1),
(154, '2023-10-19 09:00:03', 1, 1),
(155, '2023-10-20 09:00:04', 1, 1),
(156, '2023-10-21 09:00:04', 1, 1),
(157, '2023-10-22 09:00:05', 1, 1),
(158, '2023-10-29 09:00:03', 1, 2),
(159, '2023-10-30 09:00:03', 2, 2),
(160, '2023-10-31 09:00:03', 2, 1),
(161, '2023-11-12 09:00:04', 1, 1),
(162, '2023-11-13 09:00:03', 1, 1),
(163, '2023-11-20 09:00:04', 1, 1),
(164, '2023-11-21 09:00:05', 1, 1),
(165, '2023-11-22 09:00:03', 1, 1),
(166, '2023-11-23 09:00:04', 1, 1),
(167, '2023-11-24 09:00:06', 1, 1),
(168, '2023-11-25 09:00:02', 1, 1),
(169, '2023-11-26 09:00:04', 1, 1),
(170, '2023-11-27 09:00:02', 1, 1),
(171, '2023-11-29 09:00:04', 1, 1),
(172, '2023-11-30 09:00:02', 1, 2),
(173, '2023-12-01 09:00:04', 2, 1),
(174, '2023-12-02 09:00:04', 1, 1),
(175, '2023-12-03 09:00:02', 1, 1),
(176, '2023-12-04 09:00:05', 1, 1),
(177, '2023-12-10 09:00:04', 1, 1),
(178, '2023-12-11 09:00:03', 1, 1),
(179, '2023-12-12 09:00:03', 1, 2),
(180, '2023-12-15 09:00:04', 2, 2),
(181, '2023-12-16 09:00:03', 2, 1),
(182, '2023-12-17 09:00:02', 1, 1),
(183, '2023-12-18 09:00:02', 1, 2),
(184, '2023-12-21 09:00:03', 2, 3),
(185, '2023-12-22 09:00:02', 3, 3),
(186, '2023-12-23 09:00:03', 3, 4),
(187, '2023-12-24 09:00:03', 4, 5),
(188, '2023-12-25 09:00:02', 5, 5),
(189, '2023-12-26 09:00:03', 5, 2),
(190, '2023-12-27 09:00:03', 2, 2),
(191, '2023-12-28 09:00:03', 2, 3),
(192, '2023-12-29 09:00:04', 3, 1),
(193, '2023-12-30 09:00:05', 1, 4),
(194, '2023-12-31 09:00:04', 4, 6),
(195, '2024-01-01 09:00:05', 6, 6),
(196, '2024-01-02 09:00:03', 6, 5),
(197, '2024-01-03 09:00:03', 5, 5),
(198, '2024-01-04 09:00:03', 5, 4),
(199, '2024-01-05 09:00:03', 4, 5),
(200, '2024-01-06 09:00:03', 5, 4),
(201, '2024-01-07 09:00:05', 4, 3),
(202, '2024-01-08 09:00:04', 3, 1),
(203, '2024-01-09 09:00:03', 1, 2),
(204, '2024-01-10 09:00:03', 2, 2),
(205, '2024-01-11 09:00:03', 2, 3),
(206, '2024-01-12 09:00:03', 3, 2),
(207, '2024-01-13 09:00:03', 2, 3),
(208, '2024-01-14 09:00:03', 3, 8),
(209, '2024-01-15 09:00:03', 8, 7),
(210, '2024-01-16 09:00:03', 7, 9),
(211, '2024-01-17 09:00:03', 9, 10),
(212, '2024-01-18 09:00:03', 10, 7),
(213, '2024-01-19 09:00:03', 7, 7),
(214, '2024-01-20 09:00:03', 7, 6),
(215, '2024-01-21 09:00:03', 6, 7),
(216, '2024-01-22 09:00:03', 7, 3),
(217, '2024-01-23 09:00:03', 3, 5),
(218, '2024-01-24 09:00:03', 5, 6),
(219, '2024-01-25 09:00:03', 6, 6),
(220, '2024-01-26 09:00:03', 6, 7),
(221, '2024-01-27 09:00:04', 7, 7),
(222, '2024-01-28 09:00:03', 7, 8),
(223, '2024-01-29 09:00:03', 8, 8),
(224, '2024-01-30 09:00:03', 8, 6),
(225, '2024-01-31 09:00:03', 6, 7),
(226, '2024-02-01 09:00:03', 7, 3),
(227, '2024-02-02 09:00:03', 3, 4),
(228, '2024-02-03 09:00:03', 4, 4),
(229, '2024-02-04 09:00:05', 4, 6),
(230, '2024-02-05 09:00:04', 6, 4),
(231, '2024-02-06 09:00:03', 4, 6),
(232, '2024-02-07 09:00:05', 6, 5),
(233, '2024-02-08 09:00:05', 5, 4),
(234, '2024-02-09 09:00:05', 4, 4),
(235, '2024-02-10 09:00:04', 4, 4),
(236, '2024-02-11 09:00:05', 4, 3),
(237, '2024-02-12 09:00:05', 3, 5),
(238, '2024-02-13 09:00:03', 5, 4),
(239, '2024-02-14 09:00:03', 4, 7),
(240, '2024-02-15 09:00:04', 7, 5),
(241, '2024-02-16 09:00:03', 5, 2),
(242, '2024-02-17 09:00:03', 2, 4),
(243, '2024-02-18 09:00:03', 4, 6),
(244, '2024-02-19 09:00:03', 6, 8),
(245, '2024-02-20 09:00:03', 8, 8),
(246, '2024-02-21 09:00:05', 8, 10),
(247, '2024-02-22 09:00:03', 10, 7),
(248, '2024-02-23 09:00:03', 7, 3),
(249, '2024-02-24 09:00:03', 3, 4),
(250, '2024-02-25 09:00:03', 4, 4),
(251, '2024-02-27 09:00:04', 4, 1),
(252, '2024-02-28 09:00:03', 1, 2),
(253, '2024-03-10 09:00:03', 2, 1),
(254, '2024-03-11 09:00:03', 1, 1),
(255, '2024-03-13 09:00:03', 1, 1),
(256, '2024-03-14 09:00:03', 1, 4),
(257, '2024-03-15 09:00:03', 4, 4),
(258, '2024-03-16 09:00:04', 4, 2);

-- --------------------------------------------------------

--
-- Table structure for table `service_catalogs`
--

CREATE TABLE `service_catalogs` (
  `id` int(10) UNSIGNED NOT NULL,
  `ma_dich_vu` varchar(255) NOT NULL,
  `ten_dich_vu` varchar(255) NOT NULL,
  `don_gia` decimal(18,2) NOT NULL,
  `quy_trinh` varchar(255) NOT NULL,
  `cskcb_cgkt` varchar(255) DEFAULT NULL,
  `cskcb_cls` varchar(255) DEFAULT NULL,
  `tu_ngay` varchar(255) NOT NULL,
  `den_ngay` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `share_expires`
--

CREATE TABLE `share_expires` (
  `id` int(11) NOT NULL,
  `active` int(4) NOT NULL,
  `document_code` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `share_expires`
--

INSERT INTO `share_expires` (`id`, `active`, `document_code`, `created_at`, `updated_at`) VALUES
(1, 0, '000003711595', '2021-07-22 08:26:10', '2021-07-22 08:26:39'),
(2, 1, '000003736275', '2021-07-22 08:26:36', '2021-07-22 08:26:36'),
(3, 0, '000003711595', '2021-07-22 08:26:39', '2021-07-22 08:31:19'),
(4, 0, '000003711595', '2021-07-22 08:31:19', '2021-07-22 08:39:38'),
(5, 1, '000003711595', '2021-07-22 08:39:38', '2021-07-22 08:39:38'),
(6, 1, '000003791322', '2021-07-23 01:37:26', '2021-07-23 01:37:26'),
(7, 0, '000003803521', '2021-07-23 02:31:41', '2021-07-23 02:31:49'),
(8, 1, '000003803521', '2021-07-23 02:31:49', '2021-07-23 02:31:49'),
(9, 0, '000003808047', '2021-07-24 01:06:06', '2021-07-24 01:53:30'),
(10, 0, '000003808047', '2021-07-24 01:53:30', '2021-07-24 01:55:25'),
(11, 0, '000003808047', '2021-07-24 01:55:25', '2021-07-24 01:57:50'),
(12, 0, '000003808047', '2021-07-24 01:57:50', '2021-07-24 02:02:10'),
(13, 0, '000003808047', '2021-07-24 02:02:10', '2021-07-24 02:03:57'),
(14, 0, '000003808047', '2021-07-24 02:03:57', '2021-07-24 02:04:54'),
(15, 0, '000003808047', '2021-07-24 02:04:54', '2021-07-24 02:12:44'),
(16, 0, '000003807996', '2021-07-24 02:07:28', '2021-07-24 02:08:13'),
(17, 0, '000003807996', '2021-07-24 02:08:13', '2021-07-24 02:11:34'),
(18, 0, '000003807996', '2021-07-24 02:11:34', '2021-07-24 02:29:13'),
(19, 0, '000003808047', '2021-07-24 02:12:44', '2021-07-24 02:17:18'),
(20, 0, '000003808047', '2021-07-24 02:17:18', '2021-07-24 02:17:50'),
(21, 0, '000003808047', '2021-07-24 02:17:50', '2021-07-24 02:21:40'),
(22, 0, '000003808047', '2021-07-24 02:21:40', '2021-07-24 02:28:17'),
(23, 0, '000003808047', '2021-07-24 02:28:17', '2021-07-24 02:28:59'),
(24, 0, '000003808047', '2021-07-24 02:28:59', '2021-07-24 02:33:39'),
(25, 0, '000003807996', '2021-07-24 02:29:13', '2021-07-24 02:29:17'),
(26, 0, '000003807996', '2021-07-24 02:29:17', '2021-07-24 02:35:30'),
(27, 0, '000003808047', '2021-07-24 02:33:39', '2021-07-24 02:34:07'),
(28, 0, '000003808047', '2021-07-24 02:34:07', '2021-07-24 02:34:46'),
(29, 0, '000003808047', '2021-07-24 02:34:46', '2021-07-24 02:35:16'),
(30, 0, '000003808047', '2021-07-24 02:35:16', '2021-07-24 02:35:50'),
(31, 0, '000003807996', '2021-07-24 02:35:30', '2021-07-24 02:35:51'),
(32, 0, '000003808047', '2021-07-24 02:35:50', '2021-07-24 02:35:54'),
(33, 0, '000003807996', '2021-07-24 02:35:51', '2021-07-24 02:37:16'),
(34, 0, '000003808047', '2021-07-24 02:35:54', '2021-07-24 02:37:13'),
(35, 1, '000003808852', '2021-07-24 02:36:07', '2021-07-24 02:36:07'),
(36, 0, '000003808047', '2021-07-24 02:37:13', '2021-07-24 02:37:20'),
(37, 0, '000003807996', '2021-07-24 02:37:16', '2021-07-24 02:37:21'),
(38, 0, '000003808047', '2021-07-24 02:37:20', '2021-07-24 02:37:24'),
(39, 1, '000003807996', '2021-07-24 02:37:21', '2021-07-24 02:37:21'),
(40, 1, '000003808047', '2021-07-24 02:37:24', '2021-07-24 02:37:24'),
(41, 0, '000001436028', '2021-07-26 05:35:59', '2021-07-26 05:36:42'),
(42, 0, '000001437630', '2021-07-26 05:36:04', '2021-07-26 05:36:39'),
(43, 0, '000001437630', '2021-07-26 05:36:39', '2021-07-26 05:36:45'),
(44, 0, '000001436028', '2021-07-26 05:36:42', '2021-07-26 05:36:48'),
(45, 0, '000001437630', '2021-07-26 05:36:45', '2021-07-26 05:38:11'),
(46, 0, '000001436028', '2021-07-26 05:36:48', '2021-07-26 05:41:53'),
(47, 1, '000001435824', '2021-07-26 05:37:24', '2021-07-26 05:37:24'),
(48, 0, '000001437630', '2021-07-26 05:38:11', '2021-07-26 05:41:48'),
(49, 1, '000001435826', '2021-07-26 05:38:30', '2021-07-26 05:38:30'),
(50, 1, '000001435825', '2021-07-26 05:39:16', '2021-07-26 05:39:16'),
(51, 1, '000001437630', '2021-07-26 05:41:48', '2021-07-26 05:41:48'),
(52, 1, '000001436028', '2021-07-26 05:41:53', '2021-07-26 05:41:53'),
(53, 1, '000001436681', '2021-07-26 05:44:18', '2021-07-26 05:44:18'),
(54, 0, '000001443147', '2021-07-26 06:58:34', '2021-07-26 06:58:44'),
(55, 0, '000001443147', '2021-07-26 06:58:44', '2021-07-26 07:01:13'),
(56, 0, '000001443147', '2021-07-26 07:01:13', '2021-07-26 07:01:43'),
(57, 0, '000001443147', '2021-07-26 07:01:43', '2021-07-26 07:02:08'),
(58, 0, '000001443147', '2021-07-26 07:02:08', '2021-07-26 07:03:09'),
(59, 1, '000001443530', '2021-07-26 07:02:50', '2021-07-26 07:02:50'),
(60, 0, '000001443147', '2021-07-26 07:03:09', '2021-07-26 07:03:32'),
(61, 0, '000001443147', '2021-07-26 07:03:32', '2021-07-26 08:45:17'),
(62, 1, '000003732343', '2021-07-26 07:03:58', '2021-07-26 07:03:58'),
(63, 1, '000003719096', '2021-07-26 07:04:06', '2021-07-26 07:04:06'),
(64, 1, '000001443147', '2021-07-26 08:45:17', '2021-07-26 08:45:17'),
(65, 1, '000003828592', '2021-07-28 00:32:58', '2021-07-28 00:32:58'),
(66, 0, '000003648258', '2021-07-29 05:24:18', '2021-07-29 05:24:29'),
(67, 0, '000003648258', '2021-07-29 05:24:29', '2021-07-29 05:26:23'),
(68, 0, '000003648258', '2021-07-29 05:26:23', '2021-07-29 05:29:21'),
(69, 0, '000003648258', '2021-07-29 05:29:21', '2021-07-29 05:31:22'),
(70, 0, '000003648743', '2021-07-29 05:29:28', '2021-07-29 05:48:22'),
(71, 0, '000003648258', '2021-07-29 05:31:22', '2021-07-29 05:32:24'),
(72, 0, '000003648258', '2021-07-29 05:32:24', '2021-07-29 05:44:46'),
(73, 0, '000003648258', '2021-07-29 05:44:46', '2021-07-29 05:48:18'),
(74, 0, '000003647906', '2021-07-29 05:47:15', '2021-07-29 05:47:28'),
(75, 0, '000003647906', '2021-07-29 05:47:28', '2021-07-29 05:48:00'),
(76, 0, '000003647906', '2021-07-29 05:48:00', '2021-07-29 05:55:32'),
(77, 0, '000003648258', '2021-07-29 05:48:18', '2021-07-29 05:53:06'),
(78, 0, '000003648743', '2021-07-29 05:48:22', '2021-07-29 05:53:25'),
(79, 0, '000003648258', '2021-07-29 05:53:06', '2021-07-29 05:54:29'),
(80, 0, '000003648743', '2021-07-29 05:53:25', '2021-07-29 05:55:36'),
(81, 0, '000003648258', '2021-07-29 05:54:29', '2021-07-29 05:55:39'),
(82, 0, '000003648370', '2021-07-29 05:55:30', '2021-07-29 06:03:42'),
(83, 0, '000003647906', '2021-07-29 05:55:32', '2021-07-29 06:02:14'),
(84, 0, '000003648743', '2021-07-29 05:55:36', '2021-07-29 06:03:33'),
(85, 0, '000003648258', '2021-07-29 05:55:39', '2021-07-29 05:57:35'),
(86, 0, '000003648258', '2021-07-29 05:57:35', '2021-07-29 05:58:13'),
(87, 0, '000003648258', '2021-07-29 05:58:13', '2021-07-29 06:03:29'),
(88, 0, '000003647906', '2021-07-29 06:02:14', '2021-07-29 06:03:36'),
(89, 0, '000003648258', '2021-07-29 06:03:29', '2021-07-29 06:03:39'),
(90, 1, '000003648743', '2021-07-29 06:03:33', '2021-07-29 06:03:33'),
(91, 0, '000003647906', '2021-07-29 06:03:36', '2021-07-29 06:04:08'),
(92, 0, '000003648258', '2021-07-29 06:03:39', '2021-07-29 06:04:14'),
(93, 0, '000003648370', '2021-07-29 06:03:42', '2021-07-29 06:06:27'),
(94, 0, '000003647906', '2021-07-29 06:04:08', '2021-07-29 06:06:22'),
(95, 0, '000003648258', '2021-07-29 06:04:14', '2021-07-29 06:04:26'),
(96, 0, '000003648258', '2021-07-29 06:04:26', '2021-07-29 06:04:34'),
(97, 0, '000003648258', '2021-07-29 06:04:34', '2021-07-29 06:05:37'),
(98, 0, '000003648258', '2021-07-29 06:05:37', '2021-07-29 06:05:53'),
(99, 0, '000003648258', '2021-07-29 06:05:53', '2021-07-29 06:06:06'),
(100, 0, '000003648258', '2021-07-29 06:06:06', '2021-07-29 06:06:09'),
(101, 1, '000003648258', '2021-07-29 06:06:09', '2021-07-29 06:06:09'),
(102, 1, '000003647906', '2021-07-29 06:06:22', '2021-07-29 06:06:22'),
(103, 1, '000003648370', '2021-07-29 06:06:27', '2021-07-29 06:06:27'),
(104, 1, '000003690792', '2021-07-29 06:18:52', '2021-07-29 06:18:52'),
(105, 1, '000003701350', '2021-07-29 06:18:57', '2021-07-29 06:18:57'),
(106, 1, '000003837114', '2021-07-29 07:18:57', '2021-07-29 07:18:57'),
(107, 1, '000003189574', '2021-07-29 07:19:16', '2021-07-29 07:19:16'),
(108, 1, '000003270809', '2021-07-29 08:59:01', '2021-07-29 08:59:01'),
(109, 0, '000003838349', '2021-07-30 01:40:25', '2021-07-30 01:41:21'),
(110, 0, '000003838349', '2021-07-30 01:41:21', '2021-07-30 01:41:53'),
(111, 0, '000003838349', '2021-07-30 01:41:53', '2021-07-30 01:42:25'),
(112, 1, '000003838349', '2021-07-30 01:42:25', '2021-07-30 01:42:25'),
(113, 1, '000003841276', '2021-07-30 02:43:08', '2021-07-30 02:43:08'),
(114, 1, '000003803710', '2021-07-30 07:15:40', '2021-07-30 07:15:40'),
(115, 0, '000003656686', '2021-07-30 07:18:44', '2021-07-30 07:21:04'),
(116, 1, '000003656686', '2021-07-30 07:21:04', '2021-07-30 07:21:04'),
(117, 1, '000003656678', '2021-07-30 07:21:09', '2021-07-30 07:21:09'),
(118, 1, '000003658399', '2021-07-30 07:44:47', '2021-07-30 07:44:47'),
(119, 1, '000003652325', '2021-07-30 07:45:02', '2021-07-30 07:45:02'),
(120, 1, '000003647508', '2021-07-31 10:34:12', '2021-07-31 10:34:12'),
(121, 1, '000003833421', '2021-08-04 01:36:32', '2021-08-04 01:36:32'),
(122, 0, '000002625259', '2021-08-04 10:18:19', '2021-08-04 10:18:29'),
(123, 1, '000002625259', '2021-08-04 10:18:29', '2021-08-04 10:18:29'),
(124, 1, '000002355917', '2021-08-04 15:56:24', '2021-08-04 15:56:24'),
(125, 1, '000003870972', '2021-08-05 13:23:23', '2021-08-05 13:23:23'),
(126, 1, '000003818473', '2021-08-06 10:04:41', '2021-08-06 10:04:41'),
(127, 1, '000003913255', '2021-08-14 02:08:56', '2021-08-14 02:08:56'),
(128, 1, '000003901246', '2021-08-14 02:09:21', '2021-08-14 02:09:21'),
(129, 1, '000003902000', '2021-08-14 02:09:38', '2021-08-14 02:09:38'),
(130, 0, '000002459645', '2021-08-16 04:34:54', '2021-08-16 04:35:00'),
(131, 1, '000002459645', '2021-08-16 04:35:00', '2021-08-16 04:35:00'),
(132, 1, '000003921627', '2021-08-16 08:32:23', '2021-08-16 08:32:23'),
(133, 1, '000003925343', '2021-08-17 00:56:25', '2021-08-17 00:56:25'),
(134, 1, '000003925410', '2021-08-17 00:58:56', '2021-08-17 00:58:56'),
(135, 1, '000003941042', '2021-08-20 00:57:18', '2021-08-20 00:57:18'),
(136, 1, '000003814341', '2021-08-23 04:30:26', '2021-08-23 04:30:26'),
(137, 1, '000003853853', '2021-09-01 04:52:19', '2021-09-01 04:52:19'),
(138, 1, '000003855969', '2021-09-01 04:52:54', '2021-09-01 04:52:54'),
(139, 1, '000003856147', '2021-09-01 04:58:02', '2021-09-01 04:58:02'),
(140, 1, '000003985228', '2021-09-05 14:15:03', '2021-09-05 14:15:03'),
(141, 1, '000003813631', '2021-09-09 03:45:15', '2021-09-09 03:45:15'),
(142, 1, '000003813730', '2021-09-09 03:45:47', '2021-09-09 03:45:47'),
(143, 1, '000003826252', '2021-09-09 03:46:34', '2021-09-09 03:46:34'),
(144, 0, '000004014540', '2021-09-13 06:38:46', '2021-09-13 06:39:38'),
(145, 0, '000004014540', '2021-09-13 06:39:38', '2021-09-13 07:15:45'),
(146, 1, '000004014540', '2021-09-13 07:15:45', '2021-09-13 07:15:45'),
(147, 1, '000004059794', '2021-09-22 05:20:15', '2021-09-22 05:20:15'),
(148, 1, '000004059294', '2021-09-22 05:20:41', '2021-09-22 05:20:41'),
(149, 1, '000004059419', '2021-09-22 05:20:56', '2021-09-22 05:20:56'),
(150, 1, '000004077661', '2021-09-28 01:24:10', '2021-09-28 01:24:10'),
(151, 1, '000004094680', '2021-09-28 01:25:31', '2021-09-28 01:25:31'),
(152, 0, '000004078676', '2021-09-28 01:25:52', '2021-09-28 01:26:15'),
(153, 1, '000004078676', '2021-09-28 01:26:15', '2021-09-28 01:26:15'),
(154, 1, '000004078669', '2021-09-28 01:26:33', '2021-09-28 01:26:33'),
(155, 1, '000004078668', '2021-09-28 01:26:47', '2021-09-28 01:26:47'),
(156, 1, '000004187361', '2021-10-14 02:26:38', '2021-10-14 02:26:38'),
(157, 1, '000004166881', '2021-10-14 02:27:03', '2021-10-14 02:27:03'),
(158, 1, '000004144228', '2021-10-14 02:27:27', '2021-10-14 02:27:27'),
(159, 1, '000004156481', '2021-10-14 02:28:01', '2021-10-14 02:28:01'),
(160, 1, '000004173439', '2021-10-14 08:27:07', '2021-10-14 08:27:07'),
(161, 0, '000004175740', '2021-10-14 08:30:25', '2021-10-14 08:32:32'),
(162, 1, '000004175740', '2021-10-14 08:32:32', '2021-10-14 08:32:32'),
(163, 1, '000004173142', '2021-10-14 08:36:32', '2021-10-14 08:36:32'),
(164, 1, '000004173982', '2021-10-14 08:36:52', '2021-10-14 08:36:52'),
(165, 1, '000004174185', '2021-10-14 08:39:19', '2021-10-14 08:39:19'),
(166, 1, '000004173307', '2021-10-14 08:39:34', '2021-10-14 08:39:34'),
(167, 1, '000004175916', '2021-10-14 08:40:05', '2021-10-14 08:40:05'),
(168, 1, '000004173406', '2021-10-14 08:49:14', '2021-10-14 08:49:14'),
(169, 1, '000004173346', '2021-10-14 08:49:44', '2021-10-14 08:49:44'),
(170, 1, '000004173451', '2021-10-14 08:50:37', '2021-10-14 08:50:37'),
(171, 1, '000004173308', '2021-10-14 08:50:46', '2021-10-14 08:50:46'),
(172, 1, '000004243584', '2021-10-20 03:34:47', '2021-10-20 03:34:47'),
(173, 1, '000004267585', '2021-10-25 08:46:50', '2021-10-25 08:46:50'),
(174, 1, '000004309129', '2021-10-28 03:11:39', '2021-10-28 03:11:39'),
(175, 1, '000004309768', '2021-10-28 07:45:52', '2021-10-28 07:45:52'),
(176, 1, '000004188033', '2021-11-02 07:47:16', '2021-11-02 07:47:16'),
(177, 1, '000004292568', '2021-11-04 03:42:49', '2021-11-04 03:42:49'),
(178, 1, '000004291933', '2021-11-04 03:44:14', '2021-11-04 03:44:14'),
(179, 1, '000004383214', '2021-11-09 00:34:17', '2021-11-09 00:34:17'),
(180, 0, '000004363036', '2021-11-09 02:02:00', '2021-11-09 02:02:06'),
(181, 0, '000004363036', '2021-11-09 02:02:06', '2021-11-09 02:02:33'),
(182, 1, '000004363036', '2021-11-09 02:02:33', '2021-11-09 02:02:33'),
(183, 0, '000004393090', '2021-11-09 03:30:25', '2021-11-09 03:30:36'),
(184, 1, '000004393090', '2021-11-09 03:30:36', '2021-11-09 03:30:36'),
(185, 1, '000004391184', '2021-11-09 04:00:09', '2021-11-09 04:00:09'),
(186, 0, '000004393892', '2021-11-09 04:18:32', '2021-11-09 04:20:33'),
(187, 1, '000004393892', '2021-11-09 04:20:33', '2021-11-09 04:20:33'),
(188, 0, '000004368239', '2021-11-11 07:06:47', '2021-11-11 07:37:22'),
(189, 1, '000004354861', '2021-11-11 07:31:15', '2021-11-11 07:31:15'),
(190, 1, '000004368239', '2021-11-11 07:37:22', '2021-11-11 07:37:22'),
(191, 0, '000004416486', '2021-11-12 01:54:44', '2021-11-12 01:58:37'),
(192, 1, '000004416486', '2021-11-12 01:58:37', '2021-11-12 01:58:37'),
(193, 1, '000004431627', '2021-11-14 13:40:44', '2021-11-14 13:40:44'),
(194, 1, '000004430767', '2021-11-14 13:42:38', '2021-11-14 13:42:38'),
(195, 1, '000004431937', '2021-11-14 13:44:02', '2021-11-14 13:44:02'),
(196, 1, '000004431427', '2021-11-14 13:44:55', '2021-11-14 13:44:55'),
(197, 1, '000004470088', '2021-11-19 01:34:50', '2021-11-19 01:34:50'),
(198, 1, '000004480075', '2021-11-20 03:52:52', '2021-11-20 03:52:52'),
(199, 1, '000004480063', '2021-11-20 03:53:10', '2021-11-20 03:53:10'),
(200, 1, '000004478930', '2021-11-20 14:00:04', '2021-11-20 14:00:04'),
(201, 0, '000004475194', '2021-12-09 06:21:04', '2021-12-09 06:22:14'),
(202, 1, '000004475194', '2021-12-09 06:22:14', '2021-12-09 06:22:14'),
(203, 1, '000004463123', '2021-12-09 06:22:24', '2021-12-09 06:22:24'),
(204, 1, '000004684052', '2021-12-30 03:09:22', '2021-12-30 03:09:22'),
(205, 1, '000005210994', '2022-03-16 06:37:19', '2022-03-16 06:37:19'),
(206, 1, '000004434832', '2022-04-13 02:54:18', '2022-04-13 02:54:18'),
(207, 1, '000005451141', '2022-04-16 03:23:44', '2022-04-16 03:23:44'),
(208, 1, '000005442285', '2022-04-16 03:34:02', '2022-04-16 03:34:02'),
(209, 1, '000005446393', '2022-04-28 07:53:03', '2022-04-28 07:53:03'),
(210, 1, '000005281484', '2022-05-06 06:38:50', '2022-05-06 06:38:50'),
(211, 1, '000005471374', '2022-05-11 04:01:50', '2022-05-11 04:01:50'),
(212, 1, '000005596165', '2022-05-11 04:01:52', '2022-05-11 04:01:52'),
(213, 0, '000005675947', '2022-06-07 05:48:14', '2022-06-07 05:49:28'),
(214, 0, '000005675947', '2022-06-07 05:49:28', '2022-06-07 05:51:05'),
(215, 0, '000005675947', '2022-06-07 05:51:05', '2022-06-07 05:51:29'),
(216, 0, '000005675947', '2022-06-07 05:51:29', '2022-06-07 05:52:20'),
(217, 0, '000005675947', '2022-06-07 05:52:20', '2022-06-07 05:53:16'),
(218, 0, '000005675947', '2022-06-07 05:53:16', '2022-06-07 05:54:08'),
(219, 1, '000005675947', '2022-06-07 05:54:08', '2022-06-07 05:54:08'),
(220, 1, '000005906712', '2022-06-07 05:56:34', '2022-06-07 05:56:34'),
(221, 0, '000005957780', '2022-06-12 04:34:51', '2022-06-12 04:35:18'),
(222, 0, '000005957780', '2022-06-12 04:35:18', '2022-06-12 04:36:56'),
(223, 0, '000005879378', '2022-06-12 04:35:58', '2022-06-12 04:36:02'),
(224, 1, '000005879378', '2022-06-12 04:36:02', '2022-06-12 04:36:02'),
(225, 1, '000005957780', '2022-06-12 04:36:56', '2022-06-12 04:36:56'),
(226, 1, '000005924414', '2022-06-25 14:57:33', '2022-06-25 14:57:33'),
(227, 1, '000005924299', '2022-06-25 15:00:16', '2022-06-25 15:00:16'),
(228, 1, '000006385824', '2022-07-24 04:53:10', '2022-07-24 04:53:10'),
(229, 1, '000006391391', '2022-07-25 07:08:52', '2022-07-25 07:08:52'),
(230, 1, 'MDAwMDA2Mzk4NDUw', '2022-07-25 07:50:14', '2022-07-25 07:50:14'),
(231, 0, '000006398450', '2022-07-25 07:53:27', '2022-07-25 08:26:42'),
(232, 1, '000006398450', '2022-07-25 08:26:42', '2022-07-25 08:26:42'),
(233, 1, '000006573533', '2022-08-10 02:52:52', '2022-08-10 02:52:52'),
(234, 1, '000006573908', '2022-08-10 02:52:57', '2022-08-10 02:52:57'),
(235, 1, '000006586726', '2022-08-11 03:55:32', '2022-08-11 03:55:32'),
(236, 1, '000006647155', '2022-08-17 03:15:10', '2022-08-17 03:15:10'),
(237, 1, '000006647156', '2022-08-17 03:15:33', '2022-08-17 03:15:33'),
(238, 1, '000006682740', '2022-08-22 09:26:15', '2022-08-22 09:26:15'),
(239, 1, '000006900275', '2022-09-09 09:11:44', '2022-09-09 09:11:44'),
(240, 1, '000006926959', '2022-09-12 09:14:40', '2022-09-12 09:14:40'),
(241, 1, '000007030937', '2022-09-22 07:38:19', '2022-09-22 07:38:19'),
(242, 1, '000007316263', '2022-10-17 13:55:49', '2022-10-17 13:55:49'),
(243, 1, '000007315855', '2022-10-19 07:55:33', '2022-10-19 07:55:33'),
(244, 1, '000007354771', '2022-10-21 01:58:56', '2022-10-21 01:58:56'),
(245, 1, '000007606807', '2022-11-09 08:21:22', '2022-11-09 08:21:22'),
(246, 1, '000005543274', '2022-11-17 03:02:20', '2022-11-17 03:02:20'),
(247, 1, '000007589715', '2022-11-21 00:48:02', '2022-11-21 00:48:02'),
(248, 1, '000007749447', '2022-11-22 08:00:23', '2022-11-22 08:00:23'),
(249, 1, '000007746174', '2022-11-22 08:00:37', '2022-11-22 08:00:37'),
(250, 1, '000007634868', '2022-12-01 08:19:22', '2022-12-01 08:19:22'),
(251, 1, '000008026138', '2023-01-04 05:10:24', '2023-01-04 05:10:24'),
(252, 1, '000008189485', '2023-01-06 03:12:09', '2023-01-06 03:12:09'),
(253, 1, '000008857515', '2023-03-25 01:23:35', '2023-03-25 01:23:35'),
(254, 1, '000008911567', '2023-03-27 12:09:44', '2023-03-27 12:09:44'),
(255, 1, '000008922876', '2023-03-29 08:49:26', '2023-03-29 08:49:26'),
(256, 1, '000009033831', '2023-04-07 07:26:04', '2023-04-07 07:26:04'),
(257, 0, '000009108801', '2023-04-14 02:54:19', '2023-04-14 02:57:52'),
(258, 1, '000009108801', '2023-04-14 02:57:52', '2023-04-14 02:57:52'),
(259, 0, '000009097143', '2023-04-14 13:57:02', '2023-04-14 13:57:12'),
(260, 1, '000009097143', '2023-04-14 13:57:12', '2023-04-14 13:57:12'),
(261, 1, '000008880566', '2023-04-18 07:25:16', '2023-04-18 07:25:16'),
(262, 1, '000008879913', '2023-04-18 07:25:31', '2023-04-18 07:25:31'),
(263, 1, '000009320299', '2023-05-05 09:00:38', '2023-05-05 09:00:38'),
(264, 1, '000009400410', '2023-05-13 06:44:27', '2023-05-13 06:44:27'),
(265, 1, '000009403489', '2023-05-17 02:36:40', '2023-05-17 02:36:40'),
(266, 1, '000009457551', '2023-05-18 04:47:45', '2023-05-18 04:47:45'),
(267, 1, '000009794408', '2023-06-22 10:03:40', '2023-06-22 10:03:40'),
(268, 1, '000010178767', '2023-07-21 20:02:03', '2023-07-21 20:02:03'),
(269, 1, '000010224782', '2023-08-04 01:44:45', '2023-08-04 01:44:45'),
(270, 1, '000010224495', '2023-08-04 01:45:26', '2023-08-04 01:45:26'),
(271, 1, '000010224416', '2023-08-04 01:45:38', '2023-08-04 01:45:38'),
(272, 1, '000010594981', '2023-08-24 09:15:46', '2023-08-24 09:15:46'),
(273, 1, '000010595483', '2023-08-24 09:16:07', '2023-08-24 09:16:07'),
(274, 1, '000011012663', '2023-09-29 00:46:52', '2023-09-29 00:46:52'),
(275, 1, '000011012671', '2023-09-29 00:50:37', '2023-09-29 00:50:37'),
(276, 1, '000011017230', '2023-09-29 01:10:18', '2023-09-29 01:10:18'),
(277, 1, '000011165522', '2023-10-10 03:17:39', '2023-10-10 03:17:39'),
(278, 1, '000011164781', '2023-10-10 03:18:16', '2023-10-10 03:18:16'),
(279, 1, '000011165119', '2023-10-10 03:18:31', '2023-10-10 03:18:31'),
(280, 1, '000011165002', '2023-10-10 03:18:40', '2023-10-10 03:18:40'),
(281, 1, '000011271591', '2023-10-17 08:38:49', '2023-10-17 08:38:49'),
(282, 1, '000011737606', '2023-11-20 08:07:34', '2023-11-20 08:07:34'),
(283, 1, '000011369763', '2023-11-22 07:54:45', '2023-11-22 07:54:45'),
(284, 1, '000011824044', '2023-11-26 03:22:14', '2023-11-26 03:22:14'),
(285, 1, '000011826385', '2023-11-28 03:28:26', '2023-11-28 03:28:26'),
(286, 0, '000011921611', '2023-12-04 01:16:24', '2023-12-04 01:33:48'),
(287, 1, '000011921611', '2023-12-04 01:33:48', '2023-12-04 01:33:48'),
(288, 1, '000011843548', '2023-12-22 09:24:03', '2023-12-22 09:24:03'),
(289, 1, '000012429756', '2024-01-20 12:41:44', '2024-01-20 12:41:44'),
(290, 1, '000012804570', '2024-02-23 08:05:41', '2024-02-23 08:05:41'),
(291, 1, '000012834700', '2024-02-26 10:45:52', '2024-02-26 10:45:52'),
(292, 1, '000012926944', '2024-03-06 03:05:08', '2024-03-06 03:05:08'),
(293, 1, '000012928376', '2024-03-06 03:05:15', '2024-03-06 03:05:15'),
(294, 0, '000013069816', '2024-03-18 01:41:59', '2024-03-18 01:43:08'),
(295, 1, '000013069816', '2024-03-18 01:43:08', '2024-03-18 01:43:08');

-- --------------------------------------------------------

--
-- Table structure for table `sticky_notes`
--

CREATE TABLE `sticky_notes` (
  `id` int(11) NOT NULL,
  `note_name` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sticky_notes`
--

INSERT INTO `sticky_notes` (`id`, `note_name`, `content`, `created_at`, `updated_at`) VALUES
(1, 'khth', '<p><span style=\"font-size:72px\">DASHBOARD V1.0.2</span></p>', NULL, '2023-05-05 08:45:58');

-- --------------------------------------------------------

--
-- Table structure for table `symptoms`
--

CREATE TABLE `symptoms` (
  `id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `name` varchar(254) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `symptoms`
--

INSERT INTO `symptoms` (`id`, `code`, `name`, `created_at`, `updated_at`) VALUES
(1, '1', 'Đau bụng', '2018-09-04 03:44:53', '0000-00-00 00:00:00'),
(2, '2', 'Huyết áp tăng', '2018-09-04 03:44:53', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `sys_params`
--

CREATE TABLE `sys_params` (
  `id` int(10) NOT NULL,
  `param_code` varchar(50) NOT NULL,
  `param_name` varchar(255) DEFAULT NULL,
  `param_description` varchar(1024) DEFAULT NULL,
  `param_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `sys_params`
--

INSERT INTO `sys_params` (`id`, `param_code`, `param_name`, `param_description`, `param_value`, `created_at`, `updated_at`) VALUES
(1, 'cskcb_dung_tuyen', 'Danh sách cơ sở KCB đúng tuyến (Phân cách bởi dấu ,)', 'Mục đích là để phân biệt những hồ sơ có Mã ĐKBĐ khác với những mã khai báo ở đây. Phục vụ cho việc thống kê tình hình khám chữa bệnh BHYT không đăng ký tại cơ sở KCB', '01013,01061', '2019-05-31 17:00:00', '2019-06-25 01:12:02'),
(2, 'ma_thuoc_ko_sodk', 'Mã thuốc (3 ký tự đầu) không cần số đăng ký', 'Mục đích để khi quét lỗi số đăng ký thì bỏ qua những mã thuốc này', '05V', NULL, NULL),
(3, 'service_req_type_xnsh', 'Xét nghiệm sinh hóa', 'Phần nhóm xét nghiệm sinh hóa, các xét nghiệm phân biệt bởi dấu ,', 'SH001,SH002,SH003,SH004,SH,SH005,SH006,SH007,SH008,SH009,SH010,SH011,SH012,SH013,SH014,SH015,SH016,SH017,SH018,SH019,SH020,SH021,SH022,SH023,SH024,SH025,SH026,SH027,SH028,SH029,SH030,SH031,SH032,SH033,SH034,SH035,SH036,SH037,SH038,SH039,SH040,SH041,SH042,SH043,SH044,SH045,SH046,SH047,SH048,SH049,SH050,SH051,SH052,SH053,SH054,SH055,SH056,SH057,SH058,SH059,SH060,SH061,SH062,SH063,SH064,SH065,SH066,SH067,SH068,SH069,SH070,SH071,SH072,SH073,SH074,SH075,SH076,SH077,SH078,SH079,SH080,SH081,SH082,SH083,SH084,SH087,SH086,SH085,BS059', NULL, '2020-02-05 07:12:03'),
(4, 'service_req_type_xnhh', 'Xét nghiệm huyết học', 'Xét nghiệm huyết học, các xét nghiệm phân biệt bởi dấu \",\"', 'HH,HH001,HH002,HH003,HH004,HH005,HH006,HH007,HH008,HH009,HH010,HH011,HH012,HH013,HH014,HH015,HH016,HH017,HH018,HH019,HH020,HH021,HH022,HH023,HH024,HH025,HH026,HH027,HHD001,XN001,DM001,DM002,DM003', NULL, '2020-02-05 07:17:57'),
(5, 'service_req_type_xnyc', 'Xét nghiệm yêu cầu', 'Nhóm xét nghiệm yêu cầu, các dịch vụ cách nhau bởi dấu \",\"', 'VS019,GBYC02,903QD45,903QD46,903QD47,903QD48,903QD49,903QD50,903QD51,903QD52,903QD53,903QD54,903QD55,903QD56,903QD57,903QD58,903QD59,903QD65,903QD66,903QD67,903QD68,903QD69,903QD70,903QD71,903QD72,903QD73,903QD74,903QD75,903QD76,903QD77,903QD78,903QD01,903QD02,903QD03,903QD04,903QD05,903QD06,903QD07,903QD08,903QD10,903QD11,903QD12,903QD13,903QD14,903QD15,903QD16,903QD17,903QD18,903QD19,903QD20,903QD21,903QD22,903QD23,903QD24,903QD25,903QD26,903QD27,903QD28,903QD29,903QD30,903QD31,903QD32,903QD33,903QD34,903QD35,903QD36,903QD37,903QD38,903QD39,903QD40,903QD41,903QD42,903QD43,903QD44,QD151311,XNYC41,XNYC42,XNYC43,XNYC44,XNYC45,XNYC46,XNYC47,XNYC48,XNYC49,XNYC50,XNYC51,XNYC52,XNYC53,XNYC54,XNYC55,XNYC56,XNYC01,XNYC02,XNYC03,XNYC04,XNYC05,XNYC06,XNYC07,XNYC08,XNYC09,XNYC10,XNYC11,XNYC12,XNYC13,XNYC14,XNYC15,XNYC16,XNYC17,XNYC18,XNYC19,XNYC20,XNYC21,XNYC22,XNYC23,XNYC24,XNYC25,XNYC26,XNYC27,XNYC28,XNYC29,XNYC30,XNYC31,XNYC32,XNYC33,XNYC34,XNYC35,XNYC36,XNYC37,XNYC38,XNYC39,XNYC40', NULL, '2020-02-05 07:50:26'),
(6, 'service_req_type_xnkhac', 'Nhóm dịch vụ xét nghiệm khác', 'Nhóm dịch vụ xét nghiệm khác', 'VS,DM,VS001,VS002,VS003,VS004,VS005,VS006,VS007,VS008,VS009,VS010,VS011,VS012,VS013,VS014,VS015,VS016,VS017,VS018,VS020,VS021,VS022,VS023,VS024,VS025,VS026,DM004,DM005,NT001,CDNT,QD838XN,BS076', NULL, '2020-02-05 07:57:42'),
(7, 'service_req_type_xq', 'Loại dịch vụ XQ', 'Loại dịch vụ XQ', 'XQ001,XQ,XQ088,XQ089,XQ090,XQ091,XQ092,XQ093,XQ094,XQ095,XQ096,XQ097,XQ098,XQ099,XQ100,XQ101,XQ102,XQ103,XQ104,XQ105,XQ106,XQ107,XQ108,XQ109,XQ110,XQ111,XQ112,XQ113,XQ114,XQ115,XQ116,XQ117,XQ118,XQ119,XQ120,XQ121,XQ122,XQ123,XQ124,XQ0001,XQ002,XQ003,XQ004,XQ005,XQ006,XQ007,XQ008,XQ009,XQ010,XQ011,XQ012,XQ013,XQ014,XQ015,XQ016,XQ017,XQ018,XQ019,XQ020,XQ021,XQ022,XQ023,XQ024,XQ025,XQ026,XQ027,XQ028,XQ029,XQ030,XQ031,XQ032,XQ033,XQ034,XQ035,XQ036,XQ037,XQ038,XQ039,XQ040,XQ041,XQ042,XQ043,XQ044,XQ045,XQ046,XQ047,XQ048,XQ049,XQ050,XQ051,XQ052,XQ053,XQ054,XQ055,XQ056,XQ057,XQ058,XQ059,XQ060,XQ061,XQ062,XQ063,XQ064,XQ065,XQ066,XQ067,XQ068,XQ069,XQ070,XQ071,XQ072,XQ073,XQ074,XQ075,XQ076,XQ077,XQ078,XQ079,XQ080,XQ081,XQ082,XQ083,XQ084,XQ085,XQ086,XQ087,XQTP,XQ999', NULL, '2020-02-06 04:39:02'),
(8, 'service_req_type_ct', 'Loại dịch vụ CT', 'Loại dịch vụ CT', 'CT001,CT002,CT003,CT004,CT005,CT006,CT007,CT008,CT009,CT010,CT011,CT012,CT013,CT014,CT015,CT016,CT017,CT018,CT019,CT020,CT021,CT022,CT023,CT024,CT025,CT026,CT027,CT028,CT029,CT030,CT031,CT032,CT033,CT034,CT035,CT036,CT037,CT038,CT039,CT040,CT041,CT042,CT043,CT044,CTScanner,CT045,595QD19060501', NULL, '2020-02-06 04:42:28'),
(9, 'service_req_type_mri', 'Loại dịch vụ MRI', 'Loại dịch vụ MRI', 'MRI,MRI001,MRI002,MRI003,MRI004,MRI005,MRI006,MRI007,MRI008,MRI009,MRI010,MRI011,MRI012,MRI013,MRI014,MRI015,MRI016,MRI017,MRI018,MRI019,MRI020,MRI021,MRI022,MRI023,MRI024,MRI025,MRI026,MRI027,MRI028,MRI029,MRI030,MRI031,MRI032,MRI033,MRI034,MRI035,MRI036,MRI037,MRI038,MRI039,MRI040,MRI041,MRI042,MRI043,MRI044,MRI045,MRI046,MRI047,MRI048,MRI049,MRI050,MRI051,MRI052,MRI053,MRI054,MRI055,MRI056,MRI057,MRI058,MRI059,MRI060,MRI061,MRI062,MRI063,MRI064,HAYC01,TTT010,CDHA001', NULL, '2020-02-06 04:43:42'),
(10, 'service_req_type_taikham', 'Dịch vụ tái khám', 'Dịch vụ tái khám, cách nhau bởi dấu \",\"', 'TMH_TK,KN_TK01,TKL', NULL, NULL),
(11, 'ma_thuoc_oxy', 'Mã thuốc là oxy', 'Mã thuốc là oxy (cách nhau bởi dấu \",\")', '40.17', NULL, NULL),
(12, 'ds_bs_nhan_sms_xn', 'Danh sách BS nhận kết quả SMS khi có KQ XN của BN, cách nhau bởi dấu \',\'', 'Danh sách BS nhận kết quả SMS khi có KQ XN của BN, cách nhau bởi dấu \',\'', 'duongdh-kcccd, thanhpt-kn, thanhvd-kcccd, tranghc-khth, datlt-ngth, dungvv-kkb, longpd-ks, haimv-kn, huannh-kcccd, quynhnt-kcccd, truongtx-kn, tracnn, tungnt-ngth', NULL, '2020-08-08 15:22:17'),
(13, 'ds_khoa_nhan_sms_xn', 'Danh sách khoa nhận kết quả xét nghiệm', 'Danh sách khoa nhận kết quả xét nghiệm', 'K01, K02, PDD, KNGTH', NULL, '2020-08-08 01:51:04'),
(14, 'service_req_type_covid', 'Xét nghiệm test nhanh Covid', 'Xét nghiệm test nhanh Covid', '832QD01,VS030,XN0D01,VS036', NULL, '2022-11-17 08:08:56'),
(15, 'view_pdf_type', 'Cách xem PDF', '1: Xuất nội dung PDF về frontend; #1: Sử dụng pdfjs để xem', NULL, NULL, '2022-07-04 15:01:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Super Administrator', 'sa@app.com', '$2y$10$C7O3EZDp.rE7u6nshFBlWO51K4WXVYSEMx8EeXlXeWg67oZfsCy5q', 'HTPo6pwDA6pI9tWYV4AwWnwjK8tyK8dqKQ94YhkeA20l7FzWN9MiWWQoNFKA', '2017-10-23 19:00:57', '2018-04-04 06:21:57'),
(2, 'Administrator', 'administrator@app.com', '$2y$10$wifr4qBKTtDaLs7CVqgCKOJYCZkHLjLpEOe.DL2f8FN3cF3Q1AB3q', 'TewXrNfb3NHYpdyhXPmU3ahXA7kXwcBBq3h5bm9PbUFlvTmpMaioVOKkLtCs', '2017-10-23 19:00:58', '2017-10-23 19:00:58'),
(3, 'User', 'user@app.com', '$2y$10$7zYAB03oFb49jVy1BK0r2.aBKRrMWAXFVNdc0tHk8dz1.RbgNAvEi', NULL, '2017-10-23 19:00:58', '2017-10-23 19:00:58'),
(4, 'Cru User', 'cru_user@app.com', '$2y$10$/VQXJ7JmZ5gC.lLrcUVEBuMO847zIRx630AEXQLPde8O8VYcvXzzu', 'YHc9TgjqzB', '2017-10-23 19:00:59', '2017-10-23 19:00:59'),
(5, 'Build-in Administrator', 'tracnn20021979@gmail.com', '$2y$10$sSJOkWv3nM8muucYWlpr9.Ntb/EBff9yaiYr4sqexnZU6R6kf8REq', 'GIfvaswh6xKuARHw5REzaATohiAx3ayOngpYoG5q4CwMjNoKf6u2HlMc4r7r', '2017-10-23 19:25:39', '2022-12-23 06:33:13'),
(6, 'Hùng Trần', 'hungtran@gmail.com', '$2y$10$YHdJbFanvrhxNoZlccMnreabsaUNEjCimUe896LtVKxt739PtV2Vq', 'kLuVfhwOQvwxGYmYKidzTznm7Lvnh1KXPxaxr1KPE5wx74unWVESJyiC546k', '2017-10-31 19:52:19', '2017-10-31 19:52:19'),
(7, 'BattleCity', 'BattleCity@gmail.com', '$2y$10$/53pFqm4WLiMrvc/plGKmuhKW1wEUbGvTsoMWXbhy.1dLRiRitI/S', 'EWu1aKoIIvwmwaCPROBhBHX1ng9dZejnqkGNiurcHneGUdTQNzXDye5UOa0f', '2017-10-31 21:17:23', '2017-10-31 21:17:23'),
(8, 'Nguyễn Thành Luân', 'luannt@gmail.com', '$2y$10$o29a2IL1upTCPPH93xcpZeHZqgxvB5O4xlvk5DUSLxTzOgfP7GDPG', 'M6o0h7kizzW5wjKXoDhu7jYrZ2F9MkzY9nLA9Ixwb5W1PEcgFmJuZX4QaM4V', '2018-10-22 05:54:54', '2018-10-22 05:54:54'),
(9, 'Nguyễn Đình Vũ', 'vund@gmail.com', '$2y$10$sSJOkWv3nM8muucYWlpr9.Ntb/EBff9yaiYr4sqexnZU6R6kf8REq', 'RSN1p0fz8eziuog08zLb1xr9yUox6wVNuEUMYFj4KsEnOZ8WnjGtwuTBc0bI', '2018-10-22 06:00:38', '2018-10-22 06:00:38'),
(10, 'Hoàng Mạnh Hùng', 'hoanghungnh@gmail.com', '$2y$10$CFOcXkaqBv53.kis907f.u607V3XxeFVY8pYFLMWiaHocQIBxU8Tm', 'aINOrtNtXtkTwxyx7LDRCOw5v6VwqXuNdSDFJdeuB4c2luCprduE9Hxj2zHc', '2018-10-22 06:34:24', '2018-10-22 06:34:24'),
(11, 'Dr Hà Hữu Tùng', 'hahuutung.200564@gmail.com', '$2y$10$/53pFqm4WLiMrvc/plGKmuhKW1wEUbGvTsoMWXbhy.1dLRiRitI/S', NULL, NULL, NULL),
(12, 'Bệnh viện Đa khoa Nông nghiệp', 'bvdknn@gmail.com', '$2y$10$sSJOkWv3nM8muucYWlpr9.Ntb/EBff9yaiYr4sqexnZU6R6kf8REq', 'e4rTBiWHf4NdTf9JflYZNJKbF9aAgkmss1Ut8pnPNPSECggjq8vBY3zaG2bx', NULL, NULL),
(13, 'Trung tâm tiêm chủng BVĐKNN', 'vacxin@gmail.com', '$2y$10$sSJOkWv3nM8muucYWlpr9.Ntb/EBff9yaiYr4sqexnZU6R6kf8REq', 'WuZVDc8yp83qrF6e13xM5YMa7TVrwkaLiAsGiKvTMlrFc1GyddckQty48ePa', NULL, NULL),
(14, 'Khám sức khỏe', 'kskbvnn@gmail.com', '$2y$10$iurP4tKZeb.7SGBlUB8dCuLQAMi7suZrM7sdvFz0.cVNGNAvKxqR2', 'tl3aUpnFix0KqA7oQxQ1ZJms6JG0KZFYVbo4r7t7Fa0t6J4r8ubTfrIWeqsm', NULL, '2023-10-11 09:34:08'),
(15, 'Khám sức khỏe - Mắt', 'kskmat@gmail.com', '$2y$10$sSJOkWv3nM8muucYWlpr9.Ntb/EBff9yaiYr4sqexnZU6R6kf8REq', 'MLp4lSqhJyUyjiRQ7ly1v6PgcxRWBoMw4sgTXXOmmvEuU9lCRs23V8c23d44', NULL, '2022-09-20 08:35:48');

-- --------------------------------------------------------

--
-- Table structure for table `user_companies`
--

CREATE TABLE `user_companies` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `user_companies`
--

INSERT INTO `user_companies` (`id`, `user_id`, `company_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '2017-11-23 17:00:00', '2017-11-23 17:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `vaccinations`
--

CREATE TABLE `vaccinations` (
  `id` int(10) UNSIGNED NOT NULL,
  `patient_id` int(10) UNSIGNED NOT NULL,
  `vaccine_id` int(10) UNSIGNED NOT NULL,
  `date_of_vaccination` date NOT NULL,
  `dose_number` int(11) NOT NULL,
  `administered_amount` varchar(255) NOT NULL,
  `administered_by` varchar(255) DEFAULT NULL,
  `description_effect` text DEFAULT NULL,
  `severity_effect` varchar(255) DEFAULT NULL,
  `date_noted_effect` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vaccines`
--

CREATE TABLE `vaccines` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `recommended_age` varchar(255) DEFAULT NULL,
  `dose_interval` int(11) DEFAULT NULL,
  `storage_requirements` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wards`
--

CREATE TABLE `wards` (
  `id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `ma_qhuyen` varchar(10) NOT NULL,
  `name` varchar(254) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `wards`
--

INSERT INTO `wards` (`id`, `code`, `ma_qhuyen`, `name`, `created_at`, `updated_at`) VALUES
(1, '1052109', '10521', 'Xã Nhị Khê', '2018-08-30 03:10:06', '0000-00-00 00:00:00'),
(2, '1052111', '10521', 'Xã Hoà Bình', '2018-08-31 01:06:55', '0000-00-00 00:00:00'),
(3, '1050111', '10501', 'Xã Vạn Phúc', '2018-08-31 01:10:26', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `xml1s`
--

CREATE TABLE `xml1s` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ma_lk` varchar(100) NOT NULL,
  `stt` int(11) DEFAULT NULL,
  `ma_bn` varchar(100) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `ngay_sinh` varchar(100) NOT NULL,
  `gioi_tinh` int(11) NOT NULL,
  `dia_chi` varchar(1024) NOT NULL,
  `ma_the` varchar(255) NOT NULL,
  `ma_dkbd` varchar(255) NOT NULL,
  `gt_the_tu` varchar(255) NOT NULL,
  `gt_the_den` varchar(255) DEFAULT NULL,
  `mien_cung_ct` varchar(100) DEFAULT NULL,
  `ten_benh` varchar(2000) NOT NULL,
  `ma_benh` varchar(255) NOT NULL,
  `ma_benhkhac` varchar(255) DEFAULT NULL,
  `ma_lydo_vvien` int(11) NOT NULL,
  `ma_noi_chuyen` varchar(5) DEFAULT NULL,
  `ma_tai_nan` int(11) DEFAULT NULL,
  `ngay_vao` varchar(12) NOT NULL,
  `ngay_ra` varchar(12) NOT NULL,
  `so_ngay_dtri` int(11) NOT NULL,
  `ket_qua_dtri` int(11) NOT NULL,
  `tinh_trang_rv` int(11) NOT NULL,
  `ngay_ttoan` varchar(12) DEFAULT NULL,
  `t_thuoc` double DEFAULT NULL,
  `t_vtyt` double DEFAULT NULL,
  `t_tongchi` double NOT NULL,
  `t_bntt` double DEFAULT NULL,
  `t_bncct` double DEFAULT NULL,
  `t_bhtt` double NOT NULL,
  `t_nguonkhac` double DEFAULT NULL,
  `t_ngoaids` double DEFAULT NULL,
  `nam_qt` int(11) NOT NULL,
  `thang_qt` int(11) NOT NULL,
  `ma_loai_kcb` int(11) NOT NULL,
  `ma_khoa` varchar(15) NOT NULL,
  `ma_cskcb` varchar(5) NOT NULL,
  `ma_khuvuc` varchar(2) DEFAULT NULL,
  `ma_pttt_qt` varchar(255) DEFAULT NULL,
  `can_nang` double DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `xml2s`
--

CREATE TABLE `xml2s` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ma_lk` varchar(100) NOT NULL,
  `stt` int(11) DEFAULT NULL,
  `ma_thuoc` varchar(255) NOT NULL,
  `ma_nhom` int(11) NOT NULL,
  `ten_thuoc` varchar(1024) NOT NULL,
  `don_vi_tinh` varchar(50) NOT NULL,
  `ham_luong` varchar(1024) DEFAULT NULL,
  `duong_dung` varchar(4) DEFAULT NULL,
  `lieu_dung` varchar(255) DEFAULT NULL,
  `so_dang_ky` varchar(100) DEFAULT NULL,
  `tt_thau` varchar(255) DEFAULT NULL,
  `pham_vi` int(11) NOT NULL,
  `so_luong` double NOT NULL,
  `don_gia` double NOT NULL,
  `tyle_tt` double NOT NULL,
  `thanh_tien` double NOT NULL,
  `muc_huong` double NOT NULL,
  `t_nguon_khac` double DEFAULT NULL,
  `t_bntt` double DEFAULT NULL,
  `t_bhtt` double NOT NULL,
  `t_bncct` double DEFAULT NULL,
  `t_ngoaids` double DEFAULT NULL,
  `ma_khoa` varchar(15) NOT NULL,
  `ma_bac_si` varchar(255) NOT NULL,
  `ma_benh` varchar(255) NOT NULL,
  `ngay_yl` varchar(12) NOT NULL,
  `ma_pttt` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `xml3s`
--

CREATE TABLE `xml3s` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ma_lk` varchar(100) NOT NULL,
  `stt` int(11) DEFAULT NULL,
  `ma_dich_vu` varchar(255) DEFAULT NULL,
  `ma_vat_tu` varchar(255) DEFAULT NULL,
  `ma_nhom` int(11) NOT NULL,
  `goi_vtyt` varchar(2) DEFAULT NULL,
  `ten_vat_tu` varchar(1024) DEFAULT NULL,
  `ten_dich_vu` varchar(1024) DEFAULT NULL,
  `don_vi_tinh` varchar(50) DEFAULT NULL,
  `pham_vi` int(11) NOT NULL,
  `so_luong` double NOT NULL,
  `don_gia` double NOT NULL,
  `tt_thau` varchar(255) DEFAULT NULL,
  `tyle_tt` int(11) NOT NULL,
  `thanh_tien` double NOT NULL,
  `t_trantt` double DEFAULT NULL,
  `muc_huong` int(11) NOT NULL,
  `t_nguonkhac` double DEFAULT NULL,
  `t_bntt` double DEFAULT NULL,
  `t_bhtt` double NOT NULL,
  `t_bncct` double DEFAULT NULL,
  `t_ngoaids` double DEFAULT NULL,
  `ma_khoa` varchar(15) NOT NULL,
  `ma_giuong` varchar(14) DEFAULT NULL,
  `ma_bac_si` varchar(255) NOT NULL,
  `ma_benh` varchar(255) NOT NULL,
  `ngay_yl` varchar(12) NOT NULL,
  `ngay_kq` varchar(12) DEFAULT NULL,
  `ma_pttt` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `xml4s`
--

CREATE TABLE `xml4s` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ma_lk` varchar(100) NOT NULL,
  `stt` int(11) DEFAULT NULL,
  `ma_dich_vu` varchar(50) NOT NULL,
  `ma_chi_so` varchar(50) DEFAULT NULL,
  `ten_chi_so` varchar(255) DEFAULT NULL,
  `gia_tri` varchar(255) DEFAULT NULL,
  `ma_may` varchar(50) DEFAULT NULL,
  `mo_ta` text DEFAULT NULL,
  `ket_luan` text DEFAULT NULL,
  `ngay_kq` varchar(12) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `xml5s`
--

CREATE TABLE `xml5s` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ma_lk` varchar(100) NOT NULL,
  `stt` int(11) DEFAULT NULL,
  `dien_bien` text NOT NULL,
  `hoi_chan` text DEFAULT NULL,
  `phau_thuat` text DEFAULT NULL,
  `ngay_yl` varchar(12) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `xml_error_catalogs`
--

CREATE TABLE `xml_error_catalogs` (
  `id` int(10) UNSIGNED NOT NULL,
  `xml` varchar(255) NOT NULL,
  `error_code` varchar(255) NOT NULL,
  `error_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `xml_error_checks`
--

CREATE TABLE `xml_error_checks` (
  `id` int(10) UNSIGNED NOT NULL,
  `xml` varchar(255) NOT NULL,
  `ma_lk` varchar(255) NOT NULL,
  `stt` int(11) NOT NULL,
  `ngay_yl` varchar(255) DEFAULT NULL,
  `ngay_kq` varchar(255) DEFAULT NULL,
  `error_code` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_log_log_name_index` (`log_name`);

--
-- Indexes for table `cat_cond_pharmas`
--
ALTER TABLE `cat_cond_pharmas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pharma_code` (`pharma_code`);

--
-- Indexes for table `cat_cond_services`
--
ALTER TABLE `cat_cond_services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `service_code` (`service_code`);

--
-- Indexes for table `check_by_dates`
--
ALTER TABLE `check_by_dates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `NGAY_DL` (`NGAY_DL`);

--
-- Indexes for table `check_hein_cards`
--
ALTER TABLE `check_hein_cards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_lk` (`ma_lk`),
  ADD KEY `check_hein_cards_ma_the_index` (`ma_the`),
  ADD KEY `check_hein_cards_ma_lk_index` (`ma_lk`),
  ADD KEY `check_hein_cards_ma_tracuu_index` (`ma_tracuu`),
  ADD KEY `check_hein_cards_ma_kiemtra_index` (`ma_kiemtra`),
  ADD KEY `check_hein_cards_ma_ketqua_index` (`ma_ketqua`),
  ADD KEY `check_hein_cards_created_at_updated_at_index` (`created_at`,`updated_at`);

--
-- Indexes for table `check_insurances`
--
ALTER TABLE `check_insurances`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `check_treatment_records`
--
ALTER TABLE `check_treatment_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `treatment_code` (`treatment_code`(191));

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_tinh` (`code`);

--
-- Indexes for table `clinics`
--
ALTER TABLE `clinics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_pk` (`code`);

--
-- Indexes for table `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `consultants`
--
ALTER TABLE `consultants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `icd_code` (`icd_code`(191));

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `MA_KHOA` (`MA_KHOA`);

--
-- Indexes for table `department_bed_catalogs`
--
ALTER TABLE `department_bed_catalogs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `department_bed_catalogs_ma_khoa_unique` (`ma_khoa`);

--
-- Indexes for table `districts`
--
ALTER TABLE `districts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_qhuyen` (`code`);

--
-- Indexes for table `email_receive_reports`
--
ALTER TABLE `email_receive_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `equipment_catalogs`
--
ALTER TABLE `equipment_catalogs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `equipment_catalogs_ma_may_unique` (`ma_may`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `insurance_cards`
--
ALTER TABLE `insurance_cards`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `medical_staffs`
--
ALTER TABLE `medical_staffs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `medical_staffs_ma_bhxh_unique` (`ma_bhxh`);

--
-- Indexes for table `medical_supply_catalogs`
--
ALTER TABLE `medical_supply_catalogs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `medical_supply_catalogs_ma_vat_tu_tt_thau_unique` (`ma_vat_tu`,`tt_thau`),
  ADD KEY `ma_vat_tu` (`ma_vat_tu`),
  ADD KEY `tt_thau` (`tt_thau`);

--
-- Indexes for table `medicine_catalogs`
--
ALTER TABLE `medicine_catalogs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `medicine_catalogs_ma_thuoc_ham_luong_so_dang_ky_tt_thau_unique` (`ma_thuoc`,`ten_thuoc`,`ham_luong`,`so_dang_ky`,`tt_thau`) USING HASH;

--
-- Indexes for table `medicine_searchs`
--
ALTER TABLE `medicine_searchs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `med_regs`
--
ALTER TABLE `med_regs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `patient_send_mails`
--
ALTER TABLE `patient_send_mails`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_req_code` (`service_req_code`),
  ADD KEY `intruction_time` (`intruction_time`);

--
-- Indexes for table `patient_send_sms`
--
ALTER TABLE `patient_send_sms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `service_req_code` (`service_req_code`),
  ADD KEY `intruction_time` (`intruction_time`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_unique` (`name`);

--
-- Indexes for table `permission_role`
--
ALTER TABLE `permission_role`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `permission_role_role_id_foreign` (`role_id`);

--
-- Indexes for table `permission_user`
--
ALTER TABLE `permission_user`
  ADD PRIMARY KEY (`user_id`,`permission_id`,`user_type`),
  ADD KEY `permission_user_permission_id_foreign` (`permission_id`);

--
-- Indexes for table `pre_vaccination_checks`
--
ALTER TABLE `pre_vaccination_checks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pre_vaccination_checks_patient_id_foreign` (`patient_id`),
  ADD KEY `pre_vaccination_checks_vaccine_id_foreign` (`vaccine_id`);

--
-- Indexes for table `qd130_xml1s`
--
ALTER TABLE `qd130_xml1s`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ma_lk_stt` (`ma_lk`,`stt`),
  ADD KEY `qd130_xml1s_ma_lk_index` (`ma_lk`),
  ADD KEY `qd130_xml1s_ma_bn_index` (`ma_bn`),
  ADD KEY `qd130_xml1s_ma_the_bhyt_index` (`ma_the_bhyt`),
  ADD KEY `qd130_xml1s_ngay_vao_index` (`ngay_vao`),
  ADD KEY `qd130_xml1s_ngay_ra_index` (`ngay_ra`),
  ADD KEY `qd130_xml1s_ngay_ttoan_index` (`ngay_ttoan`),
  ADD KEY `qd130_xml1s_ma_loai_kcb_index` (`ma_loai_kcb`),
  ADD KEY `qd130_xml1s_ma_khoa_index` (`ma_khoa`),
  ADD KEY `qd130_xml1s_ngay_tai_kham_index` (`ngay_tai_kham`),
  ADD KEY `qd130_xml1s_created_at_index` (`created_at`),
  ADD KEY `qd130_xml1s_updated_at_index` (`updated_at`),
  ADD KEY `ngay_vao_noi_tru` (`ngay_vao_noi_tru`);

--
-- Indexes for table `qd130_xml2s`
--
ALTER TABLE `qd130_xml2s`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ma_lk_stt` (`ma_lk`,`stt`),
  ADD KEY `qd130_xml2s_ma_lk_index` (`ma_lk`),
  ADD KEY `qd130_xml2s_ma_thuoc_index` (`ma_thuoc`),
  ADD KEY `qd130_xml2s_ma_nhom_index` (`ma_nhom`),
  ADD KEY `qd130_xml2s_ma_bac_si_index` (`ma_bac_si`),
  ADD KEY `qd130_xml2s_ngay_yl_index` (`ngay_yl`),
  ADD KEY `qd130_xml2s_ngay_th_yl_index` (`ngay_th_yl`),
  ADD KEY `qd130_xml2s_created_at_index` (`created_at`),
  ADD KEY `qd130_xml2s_updated_at_index` (`updated_at`);

--
-- Indexes for table `qd130_xml3s`
--
ALTER TABLE `qd130_xml3s`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ma_lk_stt` (`ma_lk`,`stt`),
  ADD KEY `qd130_xml3s_ma_lk_index` (`ma_lk`),
  ADD KEY `qd130_xml3s_ma_dich_vu_index` (`ma_dich_vu`),
  ADD KEY `qd130_xml3s_ma_vat_tu_index` (`ma_vat_tu`),
  ADD KEY `qd130_xml3s_ma_nhom_index` (`ma_nhom`),
  ADD KEY `qd130_xml3s_ma_bac_si_index` (`ma_bac_si`),
  ADD KEY `qd130_xml3s_ngay_yl_index` (`ngay_yl`),
  ADD KEY `qd130_xml3s_ngay_th_yl_index` (`ngay_th_yl`),
  ADD KEY `qd130_xml3s_ngay_kq_index` (`ngay_kq`),
  ADD KEY `qd130_xml3s_created_at_index` (`created_at`),
  ADD KEY `qd130_xml3s_updated_at_index` (`updated_at`);

--
-- Indexes for table `qd130_xml4s`
--
ALTER TABLE `qd130_xml4s`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ma_lk_stt` (`ma_lk`,`stt`),
  ADD KEY `qd130_xml4s_ma_lk_index` (`ma_lk`),
  ADD KEY `qd130_xml4s_ma_dich_vu_index` (`ma_dich_vu`),
  ADD KEY `qd130_xml4s_ma_chi_so_index` (`ma_chi_so`),
  ADD KEY `qd130_xml4s_ngay_kq_index` (`ngay_kq`),
  ADD KEY `qd130_xml4s_ma_bs_doc_kq_index` (`ma_bs_doc_kq`),
  ADD KEY `qd130_xml4s_created_at_index` (`created_at`),
  ADD KEY `qd130_xml4s_updated_at_index` (`updated_at`);

--
-- Indexes for table `qd130_xml5s`
--
ALTER TABLE `qd130_xml5s`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ma_lk_stt` (`ma_lk`,`stt`),
  ADD KEY `qd130_xml5s_ma_lk_index` (`ma_lk`),
  ADD KEY `qd130_xml5s_thoi_diem_dbls_index` (`thoi_diem_dbls`),
  ADD KEY `qd130_xml5s_nguoi_thuc_hien_index` (`nguoi_thuc_hien`),
  ADD KEY `qd130_xml5s_created_at_index` (`created_at`),
  ADD KEY `qd130_xml5s_updated_at_index` (`updated_at`);

--
-- Indexes for table `qd130_xml_error_catalogs`
--
ALTER TABLE `qd130_xml_error_catalogs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `qd130_xml_error_catalogs_xml_error_code_unique` (`xml`,`error_code`),
  ADD KEY `qd130_xml_error_catalogs_error_code_index` (`error_code`);

--
-- Indexes for table `qd130_xml_error_results`
--
ALTER TABLE `qd130_xml_error_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_xml_ma_lk_stt_error_code` (`xml`,`ma_lk`,`stt`,`error_code`),
  ADD KEY `qd130_xml_error_results_xml_index` (`xml`),
  ADD KEY `qd130_xml_error_results_ma_lk_index` (`ma_lk`),
  ADD KEY `qd130_xml_error_results_error_code_index` (`error_code`),
  ADD KEY `qd130_xml_error_results_created_at_index` (`created_at`),
  ADD KEY `qd130_xml_error_results_updated_at_index` (`updated_at`);

--
-- Indexes for table `queue_numbers`
--
ALTER TABLE `queue_numbers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_code` (`department_code`),
  ADD KEY `phone_number` (`phone_number`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_unique` (`name`);

--
-- Indexes for table `role_user`
--
ALTER TABLE `role_user`
  ADD PRIMARY KEY (`user_id`,`role_id`,`user_type`),
  ADD KEY `role_user_role_id_foreign` (`role_id`);

--
-- Indexes for table `sarcov2_ct`
--
ALTER TABLE `sarcov2_ct`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sarcov2_ctu_id` (`sarcov2_ctu_id`);

--
-- Indexes for table `sarcov2_ctu`
--
ALTER TABLE `sarcov2_ctu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `service_catalogs`
--
ALTER TABLE `service_catalogs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `service_catalogs_ma_dich_vu_don_gia_quy_trinh_tu_ngay_unique` (`ma_dich_vu`,`don_gia`,`quy_trinh`,`tu_ngay`);

--
-- Indexes for table `share_expires`
--
ALTER TABLE `share_expires`
  ADD PRIMARY KEY (`id`),
  ADD KEY `document_code` (`document_code`(191));

--
-- Indexes for table `sticky_notes`
--
ALTER TABLE `sticky_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `note_name` (`note_name`);

--
-- Indexes for table `symptoms`
--
ALTER TABLE `symptoms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `sys_params`
--
ALTER TABLE `sys_params`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `param_code` (`param_code`),
  ADD KEY `param_code_2` (`param_code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `user_companies`
--
ALTER TABLE `user_companies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vaccinations`
--
ALTER TABLE `vaccinations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vaccinations_patient_id_foreign` (`patient_id`),
  ADD KEY `vaccinations_vaccine_id_foreign` (`vaccine_id`);

--
-- Indexes for table `vaccines`
--
ALTER TABLE `vaccines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vaccines_code_unique` (`code`);

--
-- Indexes for table `wards`
--
ALTER TABLE `wards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_pxa` (`code`);

--
-- Indexes for table `xml1s`
--
ALTER TABLE `xml1s`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `xml1s_ma_lk_unique` (`ma_lk`),
  ADD UNIQUE KEY `unique_ma_lk_stt` (`ma_lk`,`stt`),
  ADD KEY `xml1s_ma_khoa_index` (`ma_khoa`),
  ADD KEY `xml1s_ma_loai_kcb_index` (`ma_loai_kcb`),
  ADD KEY `xml1s_ngay_ttoan_index` (`ngay_ttoan`),
  ADD KEY `xml1s_ngay_ra_index` (`ngay_ra`),
  ADD KEY `xml1s_ngay_vao_index` (`ngay_vao`),
  ADD KEY `xml1s_created_at_index` (`created_at`),
  ADD KEY `xml1s_updated_at_index` (`updated_at`);

--
-- Indexes for table `xml2s`
--
ALTER TABLE `xml2s`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ma_lk_stt` (`ma_lk`,`stt`),
  ADD KEY `xml2s_ma_lk_index` (`ma_lk`),
  ADD KEY `xml2s_ngay_yl_index` (`ngay_yl`),
  ADD KEY `xml2s_created_at_index` (`created_at`),
  ADD KEY `xml2s_updated_at_index` (`updated_at`);

--
-- Indexes for table `xml3s`
--
ALTER TABLE `xml3s`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ma_lk_stt` (`ma_lk`,`stt`),
  ADD KEY `xml3s_ma_lk_index` (`ma_lk`),
  ADD KEY `xml3s_ngay_yl_index` (`ngay_yl`),
  ADD KEY `xml3s_ngay_kq_index` (`ngay_kq`),
  ADD KEY `xml3s_created_at_index` (`created_at`),
  ADD KEY `xml3s_updated_at_index` (`updated_at`);

--
-- Indexes for table `xml4s`
--
ALTER TABLE `xml4s`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ma_lk_stt` (`ma_lk`,`stt`),
  ADD KEY `xml4s_ma_lk_index` (`ma_lk`),
  ADD KEY `xml4s_ngay_kq_index` (`ngay_kq`),
  ADD KEY `xml4s_created_at_index` (`created_at`),
  ADD KEY `xml4s_updated_at_index` (`updated_at`);

--
-- Indexes for table `xml5s`
--
ALTER TABLE `xml5s`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ma_lk_stt` (`ma_lk`,`stt`),
  ADD KEY `xml5s_ma_lk_index` (`ma_lk`),
  ADD KEY `xml5s_ngay_yl_index` (`ngay_yl`),
  ADD KEY `xml5s_created_at_index` (`created_at`),
  ADD KEY `xml5s_updated_at_index` (`updated_at`);

--
-- Indexes for table `xml_error_catalogs`
--
ALTER TABLE `xml_error_catalogs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `xml_error_catalogs_xml_error_code_unique` (`xml`,`error_code`);

--
-- Indexes for table `xml_error_checks`
--
ALTER TABLE `xml_error_checks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `xml_error_checks_xml_ma_lk_stt_error_code_unique` (`xml`,`ma_lk`,`stt`,`error_code`),
  ADD KEY `xml_error_checks_xml_index` (`xml`),
  ADD KEY `xml_error_checks_ma_lk_index` (`ma_lk`),
  ADD KEY `xml_error_checks_error_code_index` (`error_code`),
  ADD KEY `xml_error_checks_created_at_updated_at_index` (`created_at`,`updated_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cat_cond_pharmas`
--
ALTER TABLE `cat_cond_pharmas`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `cat_cond_services`
--
ALTER TABLE `cat_cond_services`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `check_by_dates`
--
ALTER TABLE `check_by_dates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `check_hein_cards`
--
ALTER TABLE `check_hein_cards`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `check_insurances`
--
ALTER TABLE `check_insurances`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `check_treatment_records`
--
ALTER TABLE `check_treatment_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `clinics`
--
ALTER TABLE `clinics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `consultants`
--
ALTER TABLE `consultants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `department_bed_catalogs`
--
ALTER TABLE `department_bed_catalogs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `districts`
--
ALTER TABLE `districts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `email_receive_reports`
--
ALTER TABLE `email_receive_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `equipment_catalogs`
--
ALTER TABLE `equipment_catalogs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=205;

--
-- AUTO_INCREMENT for table `insurance_cards`
--
ALTER TABLE `insurance_cards`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3389;

--
-- AUTO_INCREMENT for table `medical_staffs`
--
ALTER TABLE `medical_staffs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medical_supply_catalogs`
--
ALTER TABLE `medical_supply_catalogs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicine_catalogs`
--
ALTER TABLE `medicine_catalogs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicine_searchs`
--
ALTER TABLE `medicine_searchs`
  MODIFY `id` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=528;

--
-- AUTO_INCREMENT for table `med_regs`
--
ALTER TABLE `med_regs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_send_mails`
--
ALTER TABLE `patient_send_mails`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patient_send_sms`
--
ALTER TABLE `patient_send_sms`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `pre_vaccination_checks`
--
ALTER TABLE `pre_vaccination_checks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qd130_xml1s`
--
ALTER TABLE `qd130_xml1s`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qd130_xml2s`
--
ALTER TABLE `qd130_xml2s`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qd130_xml3s`
--
ALTER TABLE `qd130_xml3s`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qd130_xml4s`
--
ALTER TABLE `qd130_xml4s`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qd130_xml5s`
--
ALTER TABLE `qd130_xml5s`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qd130_xml_error_catalogs`
--
ALTER TABLE `qd130_xml_error_catalogs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qd130_xml_error_results`
--
ALTER TABLE `qd130_xml_error_results`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `queue_numbers`
--
ALTER TABLE `queue_numbers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `sarcov2_ct`
--
ALTER TABLE `sarcov2_ct`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=676;

--
-- AUTO_INCREMENT for table `sarcov2_ctu`
--
ALTER TABLE `sarcov2_ctu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=259;

--
-- AUTO_INCREMENT for table `service_catalogs`
--
ALTER TABLE `service_catalogs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `share_expires`
--
ALTER TABLE `share_expires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=296;

--
-- AUTO_INCREMENT for table `sticky_notes`
--
ALTER TABLE `sticky_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `symptoms`
--
ALTER TABLE `symptoms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sys_params`
--
ALTER TABLE `sys_params`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `user_companies`
--
ALTER TABLE `user_companies`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `vaccinations`
--
ALTER TABLE `vaccinations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vaccines`
--
ALTER TABLE `vaccines`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wards`
--
ALTER TABLE `wards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `xml1s`
--
ALTER TABLE `xml1s`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `xml2s`
--
ALTER TABLE `xml2s`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `xml3s`
--
ALTER TABLE `xml3s`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `xml4s`
--
ALTER TABLE `xml4s`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `xml5s`
--
ALTER TABLE `xml5s`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `xml_error_catalogs`
--
ALTER TABLE `xml_error_catalogs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `xml_error_checks`
--
ALTER TABLE `xml_error_checks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `permission_role`
--
ALTER TABLE `permission_role`
  ADD CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `permission_user`
--
ALTER TABLE `permission_user`
  ADD CONSTRAINT `permission_user_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pre_vaccination_checks`
--
ALTER TABLE `pre_vaccination_checks`
  ADD CONSTRAINT `pre_vaccination_checks_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pre_vaccination_checks_vaccine_id_foreign` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_user`
--
ALTER TABLE `role_user`
  ADD CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `vaccinations`
--
ALTER TABLE `vaccinations`
  ADD CONSTRAINT `vaccinations_patient_id_foreign` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vaccinations_vaccine_id_foreign` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
