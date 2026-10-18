# Handoff: Course Detail Page (v4), HọcTiếngAnh

## Overview
This is the course detail page for **HọcTiếngAnh** (hoctienganh.pro.vn), a site that teaches English to Vietnamese children. On this page a learner sees:
- the course summary: title, level/age tags, description, key numbers and cover image;
- every unit, with its lessons and the learner's status on each lesson;
- a sidebar with course information and benefits.

All UI copy is in Vietnamese. Lesson content is in English.

## About the Design Files
The files in this bundle are **design references built in HTML**. They are prototypes that show the intended look and behaviour; they are not production code to copy.

Your task is to **rebuild this design in the target codebase**, using its existing framework, components and patterns. If there is no codebase yet, pick the framework that best suits the project (for example React or Next.js) and build it there.

`Chi tiet khoa hoc v4.dc.html` opens directly in a browser. It loads `support.js` and the design-system bundle from `_ds/`. The markup uses inline styles and `var(--token)` references, with the token values listed below. The JS class at the bottom of the file holds the sample data and the status logic.

## Fidelity
**High-fidelity.** Colours, typography, spacing, radii, shadows and states are final. Rebuild it pixel-accurately, using the codebase's own components where they already exist.

## Screen: Course Detail

### Global layout
- Page background: `#F6F7FA` (`--surface-page`).
- Font: **Lexend** (Google Fonts), weights 400, 500, 600 and 700.
- Main container:
  - `max-width: 1536px`, centred;
  - padding `24px 24px 64px`;
  - a vertical column with `gap: 32px` between blocks.
- Desktop/mobile breakpoint: **1200px**. Below it the header collapses and the hero stacks.

### 1. Header (sticky, top 0, z-index 30)
- Container:
  - background `#FFFFFF`;
  - bottom border `1px #EEF0F5`;
  - shadow `0 1px 2px rgba(27,31,59,.06)`;
  - inner row: height 80px, max-width 1536px, padding `0 24px`, `gap 24px`, items centred.
- Logo: `assets/logo/logo-1a-primary.svg`, height 44px, links to the home page.
  - The SVG still contains live `<text>`. Convert it to outlines before production.
- Nav, desktop only:
  - Five pill links, each height 48px, padding `0 16px`, radius 999px, `gap 8px` between icon and label.
  - Label style: 16px, weight 500.
  - Each link has a 20px Lucide icon:
    - Trang chủ (`house`)
    - Khóa học (`book-open`)
    - Từ vựng (`book-a`)
    - Flashcard (`layers`)
    - Blog tiếng Anh (`newspaper`)
  - Active item ("Khóa học"): background `#EAF3FE`, text `#0B4DB3`.
  - Other items: transparent background, text `#33385A`.
  - Hover: background `#EAF3FE`, text `#0B4DB3`.
- Search, desktop only: pill input, width 260px, height 48px, placeholder "Tìm kiếm...".
- Search, mobile: a 40px circular ghost icon button instead of the input.
- Avatar:
  - 44px circle with background `#FFF0F7` and a 2px `#FBC7E0` border;
  - initials "MA" in `#B3226E`, 16px, weight 500;
  - links to the profile page;
  - replace with the user's photo when one is available.

### 2. Breadcrumbs
Trang chủ / Khóa học / Tiếng Anh cho người mới bắt đầu
- Links are `#0F62E0`. The current page is in strong text.

### 3. Course hero card
- Card:
  - background `#FFFFFF`, radius 24px;
  - shadow `0 6px 24px rgba(27,31,59,.08), 0 1px 3px rgba(27,31,59,.05)`;
  - `overflow:hidden`.
- Grid: desktop `3fr 2fr` (text 60%, image 40%). Below 1200px it is a single column with the image below the text.
- **Text column**: padding 32px, vertical column with `gap 20px`, left-aligned.
  - Tags (small pills, `gap 8px`):
    - Each pill: height 32px, padding `0 12px`, radius 999px, 1px border, 14px, weight 500.
    - "Cơ bản" (success): background `#E8F9EE`, text `#11793A`, border `#A3E6BA`.
    - "Tiểu học" and "THCS" (primary): background `#EAF3FE`, text `#0B4DB3`, border `#B3D3FF`.
  - Title (h1) "Tiếng Anh cho người mới bắt đầu":
    - 44px, weight 700, line-height 1.15;
    - letter-spacing -0.01em, colour `#1B1F3B`, `text-wrap: balance`.
  - Description:
    - **17px**, weight 400, line-height 1.5, colour `#33385A`;
    - full column width;
    - 3 sentences. Exact copy is in the HTML file.
  - Stats row: flex-wrap, gap `20px` vertical and `48px` horizontal. Three items:
    - Each item: a 44px rounded-14 icon tile (background `#EAF3FE`, 20px icon in `#0F62E0`) with `gap 12px` to the text.
    - Value: 20px, weight 600, `#1B1F3B`.
    - Label: 14px, `#5A5F7D`.
    - Items:
      - `layers`: 30 / Unit
      - `book-open`: 120 / Bài học
      - `book-a`: 600+ / Từ vựng
- **Image column**:
  - `assets/courses/cambridge-global-7.png`, a 16:9 cover banner;
  - fills the full height of the card with `object-fit: cover`;
  - min-height 280px.

### 4. Body: Units and Sidebar
- Flex-wrap row with `gap 32px`, aligned to the top.
- Units column: `flex: 999 1 640px`.
- Sidebar: `flex: 1 1 340px`, sticky at top `80px + 24px`.

#### Unit block (repeats; `gap 32px` between units)
- Heading h2 "Unit {n}: {title}": 24px, weight 600, line-height 1.3, `#1B1F3B`.
- Description: 16px, `#5A5F7D`, `2px` below the heading.
- `16px` below the heading is the lesson grid:
  - **2 columns**, `gap 16px`;
  - the grid drops to 1 column when a card would be narrower than 260px;
  - lesson numbers run continuously across units (1–12).

#### Lesson card (anchor; links to the lesson page)
- Card:
  - flex row, items centred, `gap 16px`, padding 16px, radius 18px;
  - background `#FFFFFF`, border `1px #EEF0F5`, shadow `0 1px 2px rgba(27,31,59,.06)`;
  - hover: border `#B5B9CC`, shadow becomes the card shadow (see Hero), transition 120ms `cubic-bezier(.22,1,.36,1)`.
- **Number tile**: 56×56px, radius 14px, number at 24px weight 600. Colours by status:

  | Status | Background | Text |
  |---|---|---|
  | Hoàn thành (done) | `#E8F9EE` | `#11793A` |
  | Đang học (in progress) | `#EAF3FE` | `#0B4DB3` |
  | Chưa học / Khóa (todo / locked) | `#FFF0F7` | `#B3226E` |

- **Text** (flex 1, `gap 4px`):
  - Title: 18px, weight 500, `#1B1F3B`, single line with an ellipsis; the native tooltip shows the full title.
  - Meta row (`gap 12px`, 14px, `#5A5F7D`):
    - Lucide `tags` icon (20px) + "{n} từ mới";
    - status label at weight 600:
      - Hoàn thành: `#11793A`
      - Đang học: `#0B4DB3`
      - Chưa học: `#5A5F7D`
  - In-progress lessons only: a progress bar below the meta row (`4px` top padding).
    - Max width 200px, height 6px.
    - Fill `#0F62E0` on a `#E0E4EE` track.
- **Action circle**: 40px, radius 999px, 20px icon.

  | Status | Icon | Background | Icon colour | Hover |
  |---|---|---|---|---|
  | Done | `check` | `#E8F9EE` | `#11793A` | — |
  | In progress / next | `play` | `#EAF3FE` | `#0F62E0` | — |
  | Locked | `lock` | `#F6F7FA` | `#5A5F7D` | icon only turns `#CC2A7E` when **hovering the circle itself** (not the card); background unchanged |

#### Sidebar card "Thông tin khóa học"
- Card: background `#FFFFFF`, radius 18px, card shadow, padding 24px, `gap 20px`.
- h2: 20px, weight 600.
- Info grid: 2×2, `gap 12px`.
  - Each tile: padding 16px, radius 14px, background `#F6F7FA`.
  - Contents: 20px icon in `#0F62E0`, a 12px `#5A5F7D` label, and a 16px weight-500 `#1B1F3B` value.
  - Tiles:
    - `layers`: Số unit / 30 unit
    - `book-open`: Số bài học / 120 bài
    - `graduation-cap`: Trình độ / Cơ bản
    - `users`: Độ tuổi / Tiểu học, THCS
- Divider: `1px #EEF0F5` with 20px top padding.
- Subheading "Học vừa vui mà lại nhớ bài nhanh": 16px, weight 500.
- Benefits list (`gap 12px`): 14px, `#33385A`, each line led by a 20px `circle-check` icon in `#14903F`.
  - Nhớ bài nhanh hơn, chắc hơn và lâu hơn
  - Bài học sinh động như trò chơi, hào hứng học mỗi ngày
  - Nghe phát âm chuẩn, luyện nói theo từng câu

### 5. Floating chat button
- Fixed position, 24px from the right and bottom, z-index 40.
- 60px circle, background `#0F62E0`, 32px white `message-circle` icon.
- Shadow `0 10px 30px rgba(27,31,59,.14)`.
- Hover: background `#0B4DB3`.
- Point it at the Messenger/Zalo link.

## Interactions & Behavior
- Lesson card click opens the lesson page. Locked lessons should instead show a sign-up/login prompt; this is not designed yet.
- Hover states are listed per component above. All transitions use 120ms `cubic-bezier(.22,1,.36,1)`.
- Focus-visible: a 3px `#B3D3FF` outline ring on every interactive element.
- Responsive:
  - Below 1200px: the nav and search input are hidden, a search icon is shown, and the hero becomes one column.
  - The sidebar wraps below the units when the row is too narrow.
- Respect `prefers-reduced-motion`.

## State / Data
- Each lesson has `{ title, newWords, progress 0–100 }`. Derived status:
  - `progress === 100`: done;
  - `0 < progress < 100`: in progress;
  - `progress === 0`: todo.
- Locked rule in the prototype: every todo lesson is locked, except the first todo lesson after the learner has completed at least one lesson; that one gets a play button. Confirm the real business rule (paid or unpaid, sequential unlock).
- Each unit has `{ title, desc, lessons[] }`.
- The course has `{ title, description, tags, unitCount, lessonCount, wordCount, level, ageGroups, coverImage }`.
- Fetch the course together with the learner's per-lesson progress.
- All data in the prototype is **sample data**. The design shows 3 units of 4 lessons; the stats say 30/120/600+.

## Design Tokens
- **Colours**:
  - Blue: 50 `#EAF3FE`, 100 `#D6E7FF`, 200 `#B3D3FF`, 500 `#1675FE`, 600 `#0F62E0` (primary), 700 `#0B4DB3`.
  - Pink: 50 `#FFF0F7`, 200 `#FBC7E0`, 600 `#CC2A7E` (secondary), 700 `#B3226E`.
  - Green: 50 `#E8F9EE`, 200 `#A3E6BA`, 600 `#14903F` (success), 700 `#11793A`.
  - Yellow: 50 `#FFF8E6`, 500 `#FFD23F`, 700 `#8A6400`.
  - Ink: 50 `#F6F7FA`, 100 `#EEF0F5`, 200 `#E0E4EE`, 300 `#B5B9CC`, 400 `#878BA2`, 500 `#5A5F7D`, 700 `#33385A`, 900 `#1B1F3B`.
- **Type** (Lexend): 12, 14, 16, 18, 20, 24, 30, 36, 44px (plus a one-off 17px for the hero description). Line-heights 1, 1.15, 1.3, 1.5.
- **Spacing**: 4, 8, 12, 16, 20, 24, 32, 40, 48, 64px.
- **Radii**: 6, 10, 14, 18, 24, 999px.
- **Shadows**:
  - xs `0 1px 2px rgba(27,31,59,.06)`
  - card `0 6px 24px rgba(27,31,59,.08), 0 1px 3px rgba(27,31,59,.05)`
  - raised `0 10px 30px rgba(27,31,59,.14)`
- **Controls**: heights 40, 48, 56, 64px. Icons 16, 20, 24, 32px.
- The full token CSS is in `_ds/.../tokens/`.

## Assets
- `assets/logo/logo-1a-primary.svg`: HọcTiếngAnh logo (blue speech bubble with "Aa"). Convert its text to outlines.
- `assets/courses/cambridge-global-7.png`: course cover banner supplied by the client.
- Icons: **Lucide**, used in the prototype as the `lucide-static` icon font. Use `lucide-react` or an equivalent.

## Files
- `Chi tiet khoa hoc v4.dc.html`: the design reference (markup, plus sample data and status logic in the script at the bottom).
- `support.js`, `_ds/…`: the runtime and design-system bundle the prototype needs to open in a browser.
