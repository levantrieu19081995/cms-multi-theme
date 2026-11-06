# Khảo sát dùng chung — Kế hoạch tổng

> **⚠ ĐÃ BỊ THAY THẾ (D27).** Mọi nội dung trong tài liệu này về `config/features.php`, `ThemeFeatures`, `RequireThemeFeature`, `cms.feature:khao-sat` hoặc "chức năng phụ thuộc theme" là **lỗi thời**. Cơ chế chính thức là **Feature Registry**:
> - Definition ở `Modules/CmsBackend/app/Features/Definitions/` (khảo sát: `SurveyFeature`, mã `survey`);
> - Trạng thái bật/tắt của bản triển khai nằm ở bảng `feature_settings`;
> - Route dùng middleware `cms.feature:<feature-key>` (ví dụ `cms.feature:survey`);
> - Theme chỉ phụ trách giao diện, không quyết định chức năng; dữ liệu nghiệp vụ không gắn theme.
> Xem `DECISIONS.md` mục D27.


Mục tiêu: biến nền `ks_*` có sẵn thành chức năng Khảo sát dùng chung (quản trị, phân quyền/theme, trang công khai, phản hồi/thống kê/xuất). **Không** gồm Định hướng nghề nghiệp.

> Ghi chú: yêu cầu nhắc `AGENTS.md`; thư mục gốc không có file này (chỉ có `CLAUDE.md`; `Modules/CmsBackend/AGENTS.md` đã lỗi thời). Đã dùng `CLAUDE.md` + `README.md` làm căn cứ.

## Nguyên tắc cố định
- Mỗi khách hàng = 1 theme + 1 database + 1 container. Dữ liệu khảo sát **không** có `project_key`/`theme_key`, không lọc theo theme.
- Theme chỉ quyết định Khảo sát có **khả dụng** hay không (cổng ở `PermissionAvailability` + middleware `cms.feature`). Đổi theme không động vào dữ liệu.
- Chỉ viết migration mới (cộng thêm, có guard `hasTable/hasColumn`); không drop/rename; không chạy migrate/seed/build/deploy.
- Luồng: Controller → FormRequest → Service → DataProvider → Model. Không truy vấn trong Blade.

## Phase
| Phase | Nội dung | Báo cáo |
|---|---|---|
| 1 | Rà soát, ánh xạ schema | 01-PHASE-1-AUDIT.md |
| 2 | Quản trị khảo sát, trình dựng câu hỏi | 02-PHASE-2-ADMIN.md |
| 3 | Quyền + khả dụng theo theme | 03-PHASE-3-PERMISSIONS.md |
| 4 | Trang công khai | 04-PHASE-4-PUBLIC.md |
| 5 | Phản hồi, thống kê, xuất Excel | 05-PHASE-5-REPORTING.md |

Quyết định xuyên phase: `DECISIONS.md`. Review độc lập: `CODEX-REVIEW.md` (chỉ đọc). Xử lý lỗi: `CLAUDE-FIX-LOG.md`. Tổng kết: `FINAL-STATUS.md`.
