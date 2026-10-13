# Handoff: FunEnglish — Hồ sơ cá nhân

## Overview
FunEnglish là website dạy tiếng Anh cho trẻ em (UI tiếng Việt, nội dung học tiếng Anh). Trang **Hồ sơ cá nhân** cho học sinh/phụ huynh xem thành tích học tập, sửa thông tin cá nhân, xem các khóa học đã mua và quản lý số dư token (nạp tiền, lịch sử giao dịch). Cấu trúc: sidebar trái (avatar + menu 4 mục + Đăng xuất) và vùng nội dung phải đổi theo mục đang chọn.

## About the Design Files
Các file trong gói là **bản thiết kế tham chiếu viết bằng HTML** — prototype thể hiện giao diện và hành vi mong muốn, **không phải code production để copy nguyên**. Nhiệm vụ là **dựng lại** thiết kế trong codebase hiện có (React, Vue, Next.js…) theo pattern và thư viện sẵn có; nếu chưa có codebase, chọn framework phù hợp (gợi ý React + Vite/Next.js).

Mở file: chạy static server ở thư mục này (`npx serve .`) rồi mở `Ho so ca nhan.dc.html`. File `.dc.html` dùng runtime `support.js` (template + class logic kiểu React; logic nằm trong `<script data-dc-script>` cuối file). Component UI (NavBar, Button, IconButton, Icon, Pill, ProgressBar, TextInput, Alert) đến từ bundle design system `_ds/.../_ds_bundle.js` — đọc file này để lấy chính xác style từng component. Token ở `_ds/.../tokens/*.css`.

## Fidelity
**High-fidelity.** Màu, typography, spacing, radius và tương tác là bản cuối. Dữ liệu là dữ liệu mẫu; nạp tiền và lưu thông tin đang mô phỏng (chưa có API).

## Screens / Views

### Layout tổng
- Nền `--surface-page` #F6F7FA, font Lexend (`--font-sans`).
- Header: ≥1280px dùng **NavBar** cao 80px (logo "FunEnglish", 5 mục: Trang chủ `house`, Khóa học `book-open`, Từ vựng `book-a`, Bài kiểm tra `clipboard-check`, Trò chơi `gamepad-2`; chuông thông báo badge 2). <1280px: header sticky cao 64px, nền trắng, viền dưới 1px #EEF0F5, `--shadow-xs`; logo "FunEnglish" (700 24px, #0F62E0, link Trang chủ) trái, IconButton `user` soft tròn 40px (active) phải.
- `main`: max-width 1536px, căn giữa, padding 40px 20px, column gap 24px.
- H1 "Hồ sơ của bạn": `--type-h1` (700 44px/1.15), #1B1F3B.
- Hàng nội dung: flex wrap, gap 24px, align start. Sidebar `flex 1 1 360px; max-width 360px`; nội dung `flex 999 1 560px`. **<900px**: sidebar full width, menu chuyển thành hàng ngang (flex-direction row, wrap).

### Sidebar
Card trắng, radius 18px, `--shadow-card`, padding 20px, column gap 20px.
1. **Hồ sơ**: avatar tròn 64px (nền #EAF3FE, viền 2px #B3D3FF, chữ cái đầu của tên — lấy từ cuối họ tên, vd "An" → "A", 600 24px #0B4DB3) + cột gap 4px:
   - Tên "Nguyễn Minh An" — `--type-h3` (600 20px) #1B1F3B.
   - Nhãn **"Thành viên"**: inline-flex, cao 24px, padding 0 8px, radius 6px, nền #FFF8E6, viền 1px #FFE38A, chữ #8A6400, 12px 600, icon `crown` 16px, gap 4px.
2. **Menu** (viền trên 1px #EEF0F5, padding-top 16px, gap 4px). Mỗi item là `<button>` cao 56px, padding 0 16px, radius 14px, icon 24px + label, gap 12px:
   - Thành tích học tập (`trophy`) · Thông tin cá nhân (`user-round-pen`) · Khóa học đã mua (`shopping-bag`) · Số dư token (`wallet`).
   - Active: nền #EAF3FE, chữ #0B4DB3 `--type-label-lg` (500 18px), icon #0F62E0, `aria-current="page"`.
   - Thường: nền trong suốt, chữ #33385A `--type-body-lg` (400 18px), icon #5A5F7D; hover nền #EEF0F5. Transition `--transition-interactive`.
3. **Đăng xuất** (viền trên 1px #EEF0F5, padding-top 16px): link cao 56px, icon `log-out` 24px, chữ #A81B1C 16px; hover nền #FFEDED. Đi tới trang Đăng nhập.

### Tab 1 — Thành tích học tập (mặc định)
Column gap 24px.
- **3 thẻ thống kê**: grid `repeat(auto-fit, minmax(min(100%,200px),1fr))`, gap 16px. Card trắng radius 18px shadow-card padding 20px gap 12px: icon tròn 48px (nền role-bg, icon 24px màu role) · số 600 36px/1.15 #1B1F3B tabular-nums · nhãn 14px #5A5F7D.
  - `book-check` "Bài học đã hoàn thành" 18 (primary) · `book-a` "Từ vựng đã học" 146 (secondary/hồng) · `star` "Tổng điểm" 2.350 (accent — icon #8A6400).
- **Tiến độ khóa học** — card padding 24px gap 20px; h2 (600 24px). Mỗi khóa: hàng tên (500 18px #1B1F3B) + % bên phải (500 16px, màu `--{tone}-text`); ProgressBar cao 12px tone theo khóa; dòng phụ 14px #5A5F7D ("13/18 bài học").
  - Hello English – Lớp 3 · 72% · primary · My Family · 40% · secondary · At school · 0% · success ("Chưa bắt đầu").
- **Kết quả ôn tập gần đây** — card padding 24px gap 16px. Mỗi dòng `li`: grid `1fr | auto`, gap 16px, padding 16px 0, viền dưới 1px #EEF0F5.
  - Trái: tên bài (500 18px) · "20 từ" (14px #5A5F7D) · hàng progress (margin-top 4px, gap 12px): ProgressBar cao 8px max-width 420px (tone success khi 100%, primary khi chưa) + "% hoàn thành" (500 14px #5A5F7D, min-width 40px).
  - Phải: **Pill** điểm "9/10" — tone success + icon `circle-check` nếu ≥8; warning + `star` nếu 6–7; danger + `star` nếu <6.
  - Dữ liệu mẫu: Chào hỏi bạn mới 9 / 100% · Giới thiệu bản thân 8 / 100% · Các con số 1–10 6 / 80% · Màu sắc quanh em 10 / 100% · Ôn tập Hello 7 / 60%.

### Tab 2 — Thông tin cá nhân
Card padding 24px gap 24px. Hàng tiêu đề: h2 "Thông tin cá nhân" + Button outline md (48px) icon `pencil` "Chỉnh sửa" (chỉ hiện ở chế độ xem).
- **Chế độ xem**: `dl` grid auto-fit min 260px, gap 20px 24px. Mỗi ô: nhãn 14px #5A5F7D + giá trị 18px #1B1F3B, padding-bottom 16px, viền dưới 1px #EEF0F5. Trường rỗng hiện "—".
- **Chế độ sửa**: cùng grid, mỗi trường là TextInput có label + placeholder. Nút phải: "Hủy" (outline) · "Lưu thay đổi" (primary, icon `check`), gap 12px.
- Trường: Họ và tên · Ngày sinh (dd/mm/yyyy) · Lớp (Ví dụ: Lớp 3) · Trường · Email phụ huynh (ten@email.com) · Số điện thoại phụ huynh.
- Sau khi lưu: Alert success "Đã lưu thông tin" — "Thông tin của bạn đã được cập nhật." (ẩn khi đổi tab hoặc bấm Chỉnh sửa).

### Tab 3 — Khóa học đã mua
Column gap 16px; h2 "Khóa học đã mua". Danh sách gap 12px; mỗi item là card trắng radius 18px shadow-card padding 16px 20px, flex wrap align center gap 16px:
- Cột chữ (flex 1 1 240px): tên khóa (500 18px #1B1F3B) + "Mua ngày 15/09/2026" (14px #5A5F7D).
- Nhãn giá (cùng kiểu nhãn ở thẻ khóa học trang chủ): cao 24px, padding 0 8px, radius 6px, viền 1px, 12px 600, nowrap. Có giá → "60,000đ" nền #EAF3FE / viền #B3D3FF / chữ #0B4DB3. Miễn phí → "Miễn phí" nền #E8F9EE / viền #A3E6BA / chữ #11793A.
- Button outline sm (40px) "Vào học" icon phải `arrow-right` → trang Chi tiết khóa học.
- Mẫu: Tiếng Anh lớp 1 (Miễn phí, 01/09/2026) · Tiếng Anh lớp 3 (60,000đ, 15/09/2026) · Tiếng Anh lớp 4 (70,000đ, 02/10/2026).

### Tab 4 — Số dư token
Column gap 24px.
- **Thẻ số dư**: nền #EAF3FE, viền 1px #B3D3FF, radius 18px, padding 24px, flex space-between. "Số dư hiện tại" (500 16px #0B4DB3) + số "150.000 đ" (700 44px/1.15 #1B1F3B tabular-nums); icon `wallet` 32px #0F62E0 bên phải.
- Alert success "Nạp tiền thành công" — "Đã nạp 100.000 đ qua chuyển khoản." sau khi nạp.
- **Card Nạp tiền** (padding 24px gap 24px), h2 "Nạp tiền":
  - "Chọn số tiền": radiogroup grid auto-fit min 130px gap 12px; 4 nút cao 56px radius 14px viền 2px: 50.000 đ · 100.000 đ (mặc định) · 200.000 đ · 500.000 đ. Chọn: viền #0F62E0, nền #EAF3FE, chữ #0B4DB3; thường: viền #E0E4EE, nền trắng, chữ #1B1F3B; hover viền #B5B9CC.
  - TextInput "Hoặc nhập số tiền khác (đ)", placeholder "Ví dụ: 150000", chỉ nhận số; hint "Tối thiểu 10.000 đ."; <10.000 → state wrong + "Số tiền nạp tối thiểu là 10.000 đ.". Nhập số tùy chọn sẽ bỏ chọn nút preset.
  - "Phương thức thanh toán": radiogroup grid auto-fit min 200px; nút min-height 64px radius 14px viền 2px, icon 24px + label + icon trạng thái 20px (`circle-check` khi chọn, `circle` khi không): Chuyển khoản ngân hàng (`landmark`, mặc định) · Ví MoMo (`smartphone`) · Thẻ ATM nội địa (`credit-card`).
  - Hàng tổng (viền trên 1px #EEF0F5, padding-top 20px, space-between): "Số tiền nạp" (14px #5A5F7D) + số (600 24px) · Button primary lg (56px) icon `plus` "Nạp tiền" — disabled khi chưa có số tiền hợp lệ, loading ~1s khi bấm.
- **Lịch sử giao dịch** (card padding 24px gap 16px): mỗi dòng grid `40px | 1fr | auto` gap 16px padding 16px 0 viền dưới #EEF0F5. Icon tròn 40px: nạp tiền `arrow-down-left` nền #E8F9EE icon #14903F, số "+200.000 đ" #11793A; chi tiêu `shopping-bag` nền #FFF0F7 icon #CC2A7E, số "-199.000 đ" #1B1F3B. Tiêu đề 500 16px, ngày 14px #5A5F7D.

## Interactions & Behavior
- Bấm menu → đổi tab (reset Alert đã lưu / nạp tiền). Nên đồng bộ tab với URL (vd `/ho-so?tab=wallet`) trong bản thật.
- Hover button/menu: chỉ đổi nền; nhấn button lún xuống cạnh dưới (`--button-edge-pressed`). Focus-visible: vòng 3px #B3D3FF.
- Nạp tiền: cộng vào số dư, thêm dòng mới lên đầu lịch sử, hiện Alert. Bản thật cần chuyển sang trang thanh toán QR (xem `Dang ky khoa hoc.dc.html` trong project) hoặc cổng thanh toán.
- Responsive: <1280px đổi NavBar → header mobile; <900px sidebar full width, menu ngang.

## State Management
- `tab`: 'achieve' | 'info' | 'purchased' | 'wallet'.
- `profile` {name, birthday, grade, school, email, phone}; `editing`, `draft`, `saved`.
- `balance`, `amount` (preset), `custom` (chuỗi số), `method` ('bank'|'momo'|'card'), `paying`, `topupDone`, `history[]` {title, date, amount (âm = chi)}.
- API cần: GET hồ sơ + thống kê + tiến độ + kết quả ôn tập; PUT hồ sơ; GET khóa học đã mua; GET số dư + lịch sử; POST tạo giao dịch nạp.

## Design Tokens
- **Màu**: primary #0F62E0 (strong #0B4DB3, bg #EAF3FE, bg-hover #D6E7FF, border #B3D3FF, text #0B4DB3) · secondary #CC2A7E (bg #FFF0F7, border #FBC7E0, text #B3226E) · accent/warning #FFD23F (bg #FFF8E6, border #FFE38A, text #8A6400) · success #14903F (bg #E8F9EE, border #A3E6BA, text #11793A) · danger #DC2627 (bg #FFEDED, border #FFB3B3, text #A81B1C).
- Text: strong #1B1F3B · body #33385A · muted #5A5F7D · disabled #878BA2. Surface: page #F6F7FA · card/header #FFFFFF · hover #EEF0F5 · track #E0E4EE. Border: subtle #EEF0F5 · default #E0E4EE · hover #B5B9CC · strong #878BA2.
- **Spacing** (4px base): 4 · 8 · 12 · 16 · 20 · 24 · 32 · 40 · 48 · 64.
- **Type** Lexend: 12 · 14 · 16 · 18 · 20 · 24 · 30 · 36 · 44; line-height 1 / 1.15 / 1.3 / 1.5.
- **Radius**: 6 · 10 · 14 · 18 · 24 · pill 999. **Control height**: 40 · 48 · 56 · 64. **Icon**: 16 · 20 · 24 · 32.
- **Shadow**: xs (header), card, raised — xem `tokens/effects.css`. Button dùng cạnh dưới cứng `0 4px 0 <role-strong>`, không blur.

## Assets
- Icon: Lucide (font `lucide-static` từ CDN, khai báo trong `tokens/fonts.css`).
- Không dùng ảnh trên trang này; avatar là chữ cái đầu.

## Files
- `Ho so ca nhan.dc.html` — thiết kế (template + logic).
- `support.js` — runtime để mở file `.dc.html`.
- `_ds/funenglish-design-system-41741dfb-b285-40a1-b85c-44b1e3e3a481/` — token CSS + bundle component.
