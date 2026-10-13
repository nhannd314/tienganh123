# FunEnglish Design System

**FunEnglish — "Learn • Play • Grow"** is a Vietnamese-language website that teaches English to children (primary to grade-10 learners). Kids follow courses → units → lessons, practise vocabulary with multiple-choice and typing exercises, listen to pronunciations, earn points, and track progress — accompanied by friendly 3D-cartoon mascots.

## Sources
- `uploads/ex (2).png` (copied to `assets/reference/screen-review-quiz.png`) — a single 1536×1024 mockup of the **Ôn tập (lesson review)** quiz page. This is the **only** source: no codebase, Figma, font files, or vector logo were provided. All tokens were sampled from this image. Brand imagery (logo, mascots) has not been supplied yet; `assets/vocab/` holds client-supplied vocabulary illustrations.
- Brief: "thiết kế website học tiếng anh cho trẻ em" (design an English-learning website for kids).

## Products
- **Website** (desktop learning app) — recreated in `ui_kits/website/`.

---

## CONTENT FUNDAMENTALS
- **Language:** UI copy is **Vietnamese**; learning content (target words, speech-bubble exclamations) is **English**. SpeechBubble copy is short English ("Let's practice!").
- **Voice:** warm, encouraging older-sibling/teacher talking to a child. Addresses the learner as **"bạn"** (you); never "tôi". Sentence-final particles soften instructions: *"Chọn đáp án đúng cho mỗi câu hỏi nhé!"*, *"…giúp bạn nhớ lâu hơn đó!"*.
- **Casing:** Sentence case everywhere (Vietnamese convention): "Câu tiếp theo", "Tiến độ ôn tập", "Bài kiểm tra". No ALL CAPS.
- **Punctuation:** exclamation marks for encouragement; "…" in placeholders ("Tìm kiếm..."); counters as "Câu 1 / 5", "8/10"; scores "Điểm: 0".
- **Brevity:** labels are 1–3 words (Trang chủ, Khóa học, Từ vựng, Trò chơi). Tips are one short sentence.
- **Grammar labels** in answers use English part-of-speech in parentheses then Vietnamese meaning: "(adverb) một cách nhanh chóng", "(n) quả táo". Phonetics in IPA slashes: /ˈkwɪkli/.
- **Emoji:** not used in UI text. Expressiveness comes from colour, Lucide icons and vocabulary illustrations.
- Example strings: "Ôn tập mở đầu Hello", "Trả lời nghĩa", "Nghe và trả lời nghĩa", "Điền từ tiếng Anh", "Câu trước", "Câu tiếp theo", "Tiếp tục ôn tập", "Hoàn thành", "Số câu trả lời đúng".

## VISUAL FOUNDATIONS
- **Colour — 5 hues + ink, nothing else.** Each hue has 6 steps: **50** bg · **100** hover bg · **200** border · **500** brand/graphic · **600** action fill (white text ≥ 4.5:1) · **700** text & strong edge.
  - Blue → `--primary`, `--info` · Pink → `--secondary` · Yellow → `--accent`, `--warning` · Green → `--success` · Red → `--danger`.
  - Every role has the same 6 slots: `--{role}`, `-strong` (one step darker — button edge, link hover), `-bg`, `-bg-hover`, `-border`, `-text`. Filled roles use step 600 (yellow stays 500 with ink text).
  - Text: `--text-strong / -body / -muted / -placeholder / -disabled / -link / -on-{role}`. Surfaces: `--surface-page / -card / -header / -hover / -disabled / -track / -quiz / -selected`. Borders: `--border-subtle` (dividers) · `-default` (decorative) · `-strong` (form controls, ≥ 3:1) · `-hover` · `-selected`.
  - Components use **role tokens only**; raw scales only in specimens. No hex literals in components.
- **Contrast (WCAG AA):** all text pairs ≥ 4.5:1, control borders/icons ≥ 3:1. Never put text on a 500 fill; never use ink-400 for text. Status is never colour-only (icon + screen-reader text).
- **Type:** one family, `--font-sans` (Lexend). Scale `--fs-xs…5xl` = 12·14·16·18·20·24·30·36·44; line-heights `--lh-none/tight/snug/body` = 1·1.15·1.3·1.5. Use the composite `--type-*` styles (`word, h1–h3, answer, body-lg/body/body-sm, label-xl/lg/label/sm, caption`) via `font:`.
- **Spacing:** 4px base, `--space-1…10` = 4·8·12·16·20·24·32·40·48·64. No off-scale values.
- **Sizing:** `--control-h-sm/md/lg/xl` = 40·48·56·64 for every interactive control. Icons `--icon-sm/md/lg/xl` = 16·20·24·32 (`<Icon size="md">`).
- **Radii:** `--radius-xs/sm/md/lg/xl/pill` = 6·10·14·18·24·999. Controls sm→sm, md/lg→md; cards lg; bubbles xl; tabs, pills, circles pill.
- **Borders:** `--border-width-thin` 1px for containers (cards, alerts, pills), `--border-width` 2px for controls.
- **Shadows:** one ink tint. `--shadow-xs` header, `--shadow-card` cards/hover lift, `--shadow-raised` overlays. **Buttons use a hard bottom edge, never a blurred/grey shadow**: `0 var(--button-edge) 0 var(--{role}-hover)` (outline/soft use `--primary-border`). Hover darkens the fill (filled) or tints it `--primary-bg` (outline/ghost) — no movement; press sinks to `--button-edge-pressed`. `--shadow-halo` for the playing audio button.
- **States (every interactive component):** hover (filled: fill darkens ~8%; outline/ghost/soft: `-bg` tint; borders → `--border-hover`), focus-visible (one soft 3px `--focus-color` (blue-200) ring everywhere — global outline for buttons/links, `--focus-ring` box-shadow for inputs, borders never change; correct/wrong inputs match Alert (`--{role}-bg` + `--{role}-border`)), active/press (button sinks onto its edge), disabled (`--surface-disabled` + `--text-disabled`, not opacity), loading where relevant. Transitions use `--transition-interactive`.
- **Backgrounds:** flat `--surface-page`. No gradients, textures or decorative backdrops until brand illustrations arrive.
- **Motion:** `--dur-fast/base/slow` with `--ease-out` / `--ease-bounce`; reduced-motion respected globally.
- **Layout:** `--layout-header-h` 80 · `--layout-max-w` 1536 · `--layout-sidebar-w` 400 · `--layout-gutter`. Desktop-first.

## ICONOGRAPHY
- **UI glyphs:** rounded line icons from **Lucide** (substitute — no icon set supplied), loaded as the `lucide-static` icon font from CDN in `tokens/fonts.css`. Use `<Icon name="…"/>` or `<i class="icon-search">`. Colour them with role tokens (primary for interactive, success/danger for status).
- Nav uses Lucide: house, book-open, book-a, clipboard-check, gamepad-2. Status: circle-check, circle-x, triangle-alert, info.
- **Illustrations:** vocabulary art only (`assets/vocab/`). No logo, mascots or illustrated nav icons yet — to be supplied by the client.
- **Emoji:** not used. **Unicode** only for "/" in counters.

## Brand mark
No logo in the system yet (to be supplied). Render "FunEnglish" in the display font wherever a mark goes; NavBar does this automatically when `logoSrc` is omitted.

---

## Index
- `styles.css` — entry; @imports `tokens/*.css` (fonts, colors, typography, spacing, layout, effects, base).
- `guidelines/` — foundation specimen cards (Colors, Type, Spacing, Brand).
- `assets/reference/` — original reference screenshot.
- `assets/vocab/` — vocabulary illustrations (transparent PNG, named by English word).
- `components/` — React primitives (below), one `@dsCard` per folder.
- `ui_kits/website/` — interactive Ôn tập quiz screen.
- `thumbnail.html`, `SKILL.md`.

## Components
- **core:** Button, IconButton, Card, Pill, Badge, Icon
- **navigation:** NavBar, Breadcrumbs, StepTabs (lesson stepper)
- **forms:** SearchInput, TextInput, Checkbox
- **feedback:** Alert
- **learning:** AnswerCard, Flashcard, LessonCard, ProgressBar, ProgressRing, StatRow, AudioButton, WordPrompt, SpeechBubble, TipCard

No source component library existed; the set above is derived from elements visible in the reference screen. **Intentional additions:** `Icon` (wrapper for the Lucide glyph font); `TextInput` (for the "Điền từ tiếng Anh" step named in the tabs but not shown); `Alert` (shows the semantic success/warning/danger/info roles).
