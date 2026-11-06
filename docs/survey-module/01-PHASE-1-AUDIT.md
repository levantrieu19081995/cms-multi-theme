# Phase 1 — Rà soát và chuẩn hóa nền khảo sát

## 1. Phạm vi đã thực hiện
Đọc toàn bộ phần khảo sát hiện có: 6 migration `ks_*`, 5 model, `KhaoSatController/Service/DataProvider`, 2 view, menu, `PermissionsEnum`, `PermissionSeeder`, route (CmsBackend + CmsFrontend), dashboard dùng khảo sát, cơ chế `PermissionAvailability` / Developer Settings / `ScreenTheme`.

Đã kiểm tra **chỉ đọc** DB `cms_workspace` (`Schema::getColumns`, `count`): 5 bảng `ks_*` **đã tồn tại, 0 dòng**; không có bản ghi `ks_*` trong bảng `migrations` (bảng được tạo ngoài sổ migration; `CMS_REGISTER_MIGRATIONS=false` nên migration trong `Modules/CmsBackend/database/migrations` không tự nạp). DB đã có permission `khaosat-manager`, `khaosat-view`.

## 2. Hiện trạng (nền cũ)
Nền cũ là bộ khảo sát *định hướng nghề nghiệp* một lần: người dùng nhập họ tên + SĐT, trả lời câu hỏi radio, kết quả (`ksu_ket_qua`) là tên ngành/trường. Không có: trạng thái khảo sát, thời gian mở/đóng, nhóm câu hỏi, thứ tự, bắt buộc, kiểu câu hỏi ngoài `radio`, liên kết lượt tham gia với khảo sát, trạng thái hoàn thành, nhiều lựa chọn, văn bản tự do. Không có route/UI tạo khảo sát; `store/update/destroy` của controller rỗng; chỉ có `index` (danh sách người làm) và `show`.

## 3. Bảng ánh xạ

| Nghiệp vụ | Bảng/cột hiện tại | Tái sử dụng | Cần bổ sung (migration mới) | Không còn phù hợp |
|---|---|---|---|---|
| Khảo sát | `ks_khao_sat`: `ks_id`, `ks_ten`, `ks_ghichu`, `ks_trang_thai`(bool), `ks_created_at`, `ks_updated_at` | `ks_id`, `ks_ten`, `ks_ghichu` (ghi chú nội bộ), timestamps | `ks_ma`(slug, unique), `ks_mo_ta_ngan`, `ks_gioi_thieu`, `ks_loai`, `ks_status`(draft/open/paused/ended), `ks_bat_dau`, `ks_ket_thuc`, `ks_cho_phep_an_danh`, `ks_yeu_cau_thong_tin`, `ks_so_lan_gui`, `ks_noi_dung_hoan_thanh`, `ks_created_by`, `ks_updated_by`, `ks_deleted_at` | `ks_trang_thai` (bool) không đủ 4 trạng thái → giữ làm cờ tương thích (`= status==open`) |
| Nhóm/phần câu hỏi | (chưa có) | — | bảng mới `ks_nhom` (`nhom_id`, `ks_id`, `nhom_ma`, `nhom_ten`, `nhom_mo_ta`, `nhom_thu_tu`, `nhom_deleted_at`) + `ks_cau_hoi.nhom_id` | — |
| Câu hỏi | `ks_cau_hoi`: `ch_id`, `ks_id`, `ch_type`, `ch_noidung`, `ch_trang_thai`, timestamps | tất cả cột; `ch_trang_thai` = bật/tắt; `ch_type='radio'` = alias "một lựa chọn" | `ch_ma`(UUID), `nhom_id`, `ch_thu_tu`, `ch_bat_buoc`, `ch_mo_ta`, `ch_cau_hinh`(json: thang điểm…), `ch_deleted_at` | — |
| Lựa chọn | `ks_tra_loi`: `trl_id`, `ch_id`, `trl_noidung`, `trl_value`, timestamps | tất cả; `trl_value` = mã lưu vào kết quả | `trl_ma`(UUID), `trl_thu_tu`, `trl_trang_thai`, `trl_deleted_at` | Tên "tra_loi" dễ nhầm với câu trả lời — giữ nguyên tên, ghi rõ trong tài liệu |
| Lượt tham gia | `ks_khao_sat_users`: `ksu_id`, `ksu_ho_ten`(NOT NULL), `ksu_so_dien_thoai`(NOT NULL), `school_key/name`, `ksu_created_at`, `ksu_updated_at`, `ksu_ket_qua` | `ksu_id`, họ tên/SĐT (chuỗi rỗng = không khai), `ksu_created_at` (= lúc bắt đầu) | `ks_id`, `ksu_ma`(UUID), `ksu_trang_thai`(in_progress/completed), `ksu_hoan_thanh_at`, `ksu_thoi_luong`, `ksu_an_danh`, `ksu_email`, `ksu_dau_vet`(HMAC), `ksu_ip_hash`, `ksu_user_agent`, `ksu_deleted_at` | `ksu_ket_qua`, `school_*`: chỉ phục vụ định hướng nghề nghiệp/biểu đồ dashboard — **giữ nguyên, không dùng cho khảo sát chung** |
| Câu trả lời thực tế | `ks_ket_qua`: `kskq_id`, `ks_id`, `ch_id`, `ksu_id`, `kskq_trl_value`(varchar 255, NOT NULL) | tất cả cột; 1 dòng = 1 giá trị | `trl_id`(lựa chọn thật), `kskq_van_ban`(text), `kskq_created_at`; index `(ksu_id)`, `(ks_id,ch_id)` | — |
| Kết quả | `ksu_ket_qua` (chuỗi) | — | Không có thuật toán trong phạm vi này | Dành cho Định hướng nghề nghiệp (sau) |
| Trạng thái | `ks_trang_thai` bool | đồng bộ tương thích | `ks_status` | — |

Ràng buộc FK: nền cũ có FK bị comment; **không thêm FK** (tránh lỗi với dữ liệu mồ côi, bảng tạo ngoài migration). Chỉ thêm index.

## 4. Kiến trúc được chọn
Cộng thêm cột/bảng bằng migration mới trong `database/migrations` (thư mục được nạp), mỗi migration có guard `Schema::hasTable/hasColumn` nên chạy lại an toàn và chạy được cả khi bảng nền chưa có (tạo bảng nền theo đúng schema cũ rồi mới thêm cột). Không `->change()` cột cũ: họ tên/SĐT `NOT NULL` được xử lý bằng chuỗi rỗng + cờ `ksu_an_danh`.

## 5. Danh sách file thêm mới (Phase 1)
`docs/survey-module/*` (hồ sơ). Migration được viết sau khi bản ánh xạ này hoàn tất (xem Phase 2 mục 5).

## 6. Danh sách file chỉnh sửa
Không có.

## 7. Migration đã viết
Chưa (Phase 1 chỉ ánh xạ, đúng yêu cầu).

## 8. Quan hệ dữ liệu (đích)
`ks_khao_sat 1─n ks_nhom 1─n ks_cau_hoi 1─n ks_tra_loi`; `ks_khao_sat 1─n ks_khao_sat_users(ksu) 1─n ks_ket_qua`; `ks_ket_qua n─1 ks_cau_hoi`, `n─0..1 ks_tra_loi`. `ks_cau_hoi.nhom_id` có thể null (câu hỏi không thuộc nhóm).

## 9. Route mới/thay đổi
Chưa. Hiện có `GET /cms/khao-sat` và `/cms/khao-sat/{ksu_id}` khai báo hai lần (CmsBackend `cms.dashboard.khao-sat`, CmsFrontend `cms.khao-sat`/`cms.khao-sat.show`) — sẽ gom ở Phase 2.

## 10. Permission mới/thay đổi
Hiện có `khaosat-manager` (cha), `khaosat-view`. Chưa đổi (Phase 3).

## 11. Giữ lại từ code cũ
Tên bảng/cột, 5 model (`Modules\CmsFrontend\Models`), khung `KhaoSatService/DataProvider` (các method `getUsersWithFilter`, `getUserSurveyDetail`, `saveSurvey` mà dashboard dùng), slug quyền, mục menu `cms.khao-sat`, tên route `cms.khao-sat` / `cms.khao-sat.show`.

## 12. Không sử dụng và lý do
- `ksu_ket_qua`, `school_key`, `school_name`: thuộc định hướng nghề nghiệp, ngoài phạm vi.
- Biểu đồ nghề nghiệp ở dashboard: giữ, chỉ lọc bản ghi có `ksu_ket_qua`.
- Module `CaoDangNgheSocTrang`: không sao chép.

## 13. Rủi ro còn lại
- Quyền mới cần chạy `PermissionSeeder` (người dùng quyết định).
- `ch_trang_thai` mặc định `false` ở nền cũ: câu hỏi cũ (nếu có) ở trạng thái tắt; hiện bảng rỗng nên không ảnh hưởng.
- Dashboard đếm `ks_khao_sat_users`; xử lý ở Phase 2 (D12).

## 14. Hướng dẫn kiểm tra
- Đối chiếu bảng mục 3 với 5 migration cũ và `Schema::getColumns` (đã dùng khi rà soát).
- Xác nhận 5 bảng 0 dòng bằng `select count(*)`.
- Xác nhận migration cũ không bị sửa (không có `dropIfExists` mới).

## 15. Xác nhận
Không build, không migrate, không seed, không deploy. Chỉ có truy vấn đọc (Schema, count).
