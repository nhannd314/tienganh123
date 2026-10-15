# Handoff: FunEnglish — Chi tiết bài viết

## Overview
Trang đọc bài viết (blog/mẹo học tập cho phụ huynh và học sinh) của FunEnglish, website dạy tiếng Anh cho trẻ em. UI tiếng Việt. Gồm: bài viết chính (ảnh bìa, meta, nội dung, mẹo, chia sẻ), sidebar (bài liên quan + CTA học thử) và danh sách khóa học gợi ý cuối trang.

## About the Design Files
Các file trong gói là **bản thiết kế tham chiếu viết bằng HTML**, không phải code production. Hãy **dựng lại** trong codebase hiện có (React/Vue/Next.js…) theo pattern sẵn có; nếu chưa có codebase, gợi ý React + Next.js.

Mở file: chạy static server ở thư mục này (`npx serve .`) rồi mở `Chi tiet bai viet.dc.html`. File `.dc.html` dùng runtime `support.js`; template là markup inline-style, logic nằm trong `<script data-dc-script>` cuối file (`renderVals()` chứa toàn bộ dữ liệu mẫu). Component UI (NavBar, Breadcrumbs, Button, TipCard, LessonCard) lấy từ bundle `_ds/.../_ds_bundle.js` — đọc file này để lấy style chính xác. Token ở `_ds/.../tokens/*.css`. `image-slot.js` chỉ là ô kéo-thả ảnh trong prototype — thay bằng `<img>` thật.

## Fidelity
**High-fidelity.** Màu, type, spacing, radius, tương tác là bản cuối. Nội dung bài, ảnh và link là dữ liệu mẫu.

## Layout
- Nền `--surface-page` #F6F7FA, font Lexend.
- Header: ≥1280px dùng **NavBar** 80px (5 mục: Trang chủ `house`, Khóa học `book-open`, Từ vựng `book-a`, Bài kiểm tra `clipboard-check`, Trò chơi `gamepad-2`; chuông badge 2). <1280px: header sticky 64px, nền trắng, viền dưới 1px #EEF0F5, `--shadow-xs`, logo chữ "FunEnglish" 700 24px #0F62E0.
- `main`: max-width 1200px, căn giữa, padding 24px 20px 48px, column gap 24px.
- Breadcrumbs: Trang chủ › Bài viết › 5 mẹo nhớ từ vựng lâu hơn.
- Hàng chính: flex wrap, gap 24px, align start. Article `flex 999 1 600px`; aside `flex 1 1 320px` (≈320–360px khi nằm cạnh). Khi aside rơi xuống dưới (≈<984px) nó full width và 2 card bên trong xếp grid `repeat(auto-fit, minmax(min(100%,320px),1fr))`.

## Article (card trắng, radius 18px, `--shadow-card`, overflow hidden)
1. **Ảnh bìa**: tỉ lệ 16:8, nền placeholder #EAF3FE, object-fit cover.
2. **Thân bài**: padding 32px 32px 40px, max-width 760px, column gap 20px.
   - Hàng meta (wrap, gap 12px): nhãn chuyên mục "Mẹo học tập" (cao 32px, padding 0 12px, radius 10px, nền #FFF0F7, chữ #B3226E, 14px 600) · `calendar` 16px + "12/10/2026" · `clock` + "5 phút đọc" (14px #5A5F7D, gap 4px).
   - H1 `--type-h1` (700 44px/1.15) #1B1F3B, text-wrap balance.
   - Sapo `--type-body-lg` (18px/1.5) #33385A.
   - 5 phần: H2 `--type-h2` (600 24px) + đoạn body-lg; mỗi phần gap 12px, padding-top 8px.
   - **TipCard** (component DS) tiêu đề "Mẹo nhỏ".
   - Footer bài (viền trên 1px #EEF0F5, padding-top 20px, space-between, wrap):
     - Trái: "Chia sẻ:" (500 14px #5A5F7D) + 4 nút tròn 40px, icon trắng 20px, cạnh dưới cứng `0 3px 0 <role-strong>`, hover nền → role-strong:
       Facebook `facebook` #0F62E0 (primary) · YouTube `youtube` danger · Instagram `instagram` secondary #CC2A7E · Email `mail` success. Mỗi nút có `aria-label`.
     - Phải: Button outline sm (40px) `link` "Sao chép link" → khi bấm copy URL, đổi thành variant success `check` "Đã sao chép" trong 1.8s.

## Sidebar (sticky, top = header + 20px, chỉ khi nằm cạnh bài)
- **Bài viết liên quan** — card trắng radius 18px shadow-card padding 20px gap 16px; H2 `--type-h3` (600 20px). Mỗi item là link flex align center gap 12px, padding 12px 0, viền trên 1px #EEF0F5 (trừ item đầu), hover opacity .85:
  - Thumbnail 72×72 radius 14px, nền role-bg của chuyên mục, ảnh contain.
  - Tên bài 500 16px #1B1F3B, clamp 2 dòng; dòng dưới 12px: chuyên mục (600, màu role-text) · "·" · ngày (#5A5F7D).
  - Mẫu: Phát âm (primary) · Mẹo học tập (secondary) · Ôn thi (success) · Trò chơi (warning).
- **Học thử miễn phí** — nền #EAF3FE, radius 18px, padding 20px, gap 16px. Hàng đầu: vòng tròn 48px nền trắng icon `gift` 24px #0F62E0 + tiêu đề h3 + "Khóa Tiếng Anh lớp 1" (14px). Button primary lg (56px) full width "Học ngay" icon phải `arrow-right` → trang chi tiết khóa học.

## Khóa học dành cho bé (cuối trang, full width main)
- Hàng tiêu đề: H2 "Khóa học dành cho bé" + link "Xem tất cả" `arrow-right` (500 16px, màu link) căn phải.
- Grid `repeat(auto-fill, minmax(min(100%,280px),1fr))`, gap 24px, 3 **LessonCard** (DS) với thumbnail contain, tone theo khóa, title + nhãn giá ngay dưới title:
  - Nhãn: cao 24px, padding 0 8px, radius 6px, viền 1px, 12px 600. Miễn phí → nền #E8F9EE / viền #A3E6BA / chữ #11793A. Có giá → nền #EAF3FE / viền #B3D3FF / chữ #0B4DB3.
  - Mẫu: Tiếng Anh lớp 1 (Miễn phí, danger) · lớp 2 (50,000đ, primary) · lớp 3 (60,000đ, success).

## Interactions & State
- `copied: boolean` cho nút Sao chép link (timeout 1.8s).
- `isMobile` theo breakpoint 1280px (đổi NavBar ↔ header mobile).
- Link chia sẻ: thay bằng URL share thật kèm URL bài (`facebook.com/sharer/sharer.php?u=…`, `mailto:?subject=…&body=…`); YouTube/Instagram nên là link kênh.
- Dữ liệu cần từ API: bài viết {title, category, date, readTime, cover, sapo, sections[], tip}, related[] {title, category, date, thumb, slug}, courses[] {title, description, thumb, tone, price|free}.

## Design Tokens
- Màu: primary #0F62E0 (strong #0B4DB3, bg #EAF3FE, border #B3D3FF, text #0B4DB3) · secondary #CC2A7E (bg #FFF0F7, border #FBC7E0, text #B3226E) · accent/warning #FFD23F (bg #FFF8E6, border #FFE38A, text #8A6400) · success #14903F (bg #E8F9EE, border #A3E6BA, text #11793A) · danger #DC2627 (bg #FFEDED, border #FFB3B3, text #A81B1C). Giá trị chính xác xem `tokens/colors.css`.
- Text: strong #1B1F3B · body #33385A · muted #5A5F7D. Surface: page #F6F7FA · card #FFFFFF · hover #EEF0F5. Border subtle #EEF0F5.
- Spacing 4·8·12·16·20·24·32·40·48·64 · Radius 6·10·14·18·24·999 · Control 40·48·56·64 · Icon 16·20·24·32.
- Type Lexend 12·14·16·18·20·24·30·36·44; dùng composite `--type-*`.

## Assets
- Icon: Lucide (font `lucide-static` qua CDN trong `tokens/fonts.css`).
- `assets/vocab/*.png` — ảnh minh họa tạm cho thumbnail bài liên quan và khóa học; thay bằng ảnh thật.

## Files
- `Chi tiet bai viet.dc.html` — thiết kế.
- `support.js`, `image-slot.js` — runtime prototype.
- `_ds/…` — token CSS + bundle component.
- `assets/vocab/` — ảnh mẫu.
