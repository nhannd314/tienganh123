# Handoff: Đăng nhập & Đăng ký (Login & Sign-up)

## Overview
Two authentication pages for FunEnglish, a Vietnamese website that teaches English to children. Both pages use the same centred single-card layout: the learner logs in or creates an account with **Google** or with **email + password**. UI copy is Vietnamese; the tone is warm and encouraging and addresses the learner as "bạn".

## About the Design Files
The files in this bundle are **design references created in HTML** — prototypes that show the intended look and behaviour, not production code to copy. Recreate them in the target codebase's environment (React, Vue, etc.) using its existing patterns, router and auth library. If no environment exists yet, pick the most suitable framework.

Each `.dc.html` opens directly in a browser (it needs `support.js` and `_ds/` next to it). The template markup is between `<x-dc>…</x-dc>` and the logic is the `class Component` inside `<script data-dc-script>`. `{{ name }}` holes are values returned by `renderVals()`. `<x-import component-from-global-scope="FunEnglishDesignSystem_41741d.X">` mounts design-system component **X** from `_ds/…/_ds_bundle.js`; its props are attributes (kebab-case → camelCase).

## Fidelity
**High-fidelity.** Final colours, type, spacing and interactions. Build with the FunEnglish design-system components (Button, TextInput, Checkbox, Icon) or the codebase's equivalents, matching the tokens below.

## Shared layout (both pages)
- Page: `min-height:100vh`, background `--surface-page` #F6F7FA. Content is centred both ways, with padding 40px top/bottom and 20px left/right.
- Column: width 100%, `max-width:440px`, vertical gap 24px:
  1. **Wordmark** "FunEnglish": a link to the home page (`Trang chu`), centred, Lexend 700 30px/1.15, colour #0B4DB3 (`--primary-text`). There is no logo image yet.
  2. **Card**: white background, radius 18px, shadow `0 6px 24px rgba(27,31,59,.08), 0 1px 3px rgba(27,31,59,.05)`, padding 32px top/bottom and 28px left/right, vertical gap 24px.
  3. **Footer line**: centred, 400 16px #33385A, with an inline link in weight 600.
- Card header (centred, gap 8px): H1 600 36px/1.15 #1B1F3B, then a subtitle in 400 16px #5A5F7D.
- **Google button**: design-system `Button` with variant `outline`, size `lg` (56px tall), full width, radius 14px, 2px border #B3D3FF, white background, text #0B4DB3 500 18px. It has a 4px hard bottom edge in #B3D3FF. On hover the background turns #EAF3FE; when pressed the button sinks onto a 1px edge. Left icon: the official multicolour Google "G" at 24×24 (an SVG data-URI in the code; in production use Google's official asset or the Google Identity button).
- **Divider**: a flex row with gap 12px and 1px lines in #EEF0F5 on both sides; label 400 14px #5A5F7D.
- **Form**: flex column, gap 20px, `noValidate`; validation runs in JS when the form is submitted.
- **Text field** (`TextInput`): label 500 16px #33385A, then a gap of 8px, then the input. Input: 56px tall, radius 14px, 1px border #E0E4EE (hover #B5B9CC), padding 0 16px, 400 20px/1.3 #1B1F3B, placeholder #5A5F7D. Focus adds a 3px #B3D3FF ring (box-shadow); the border does not change. Error state: background #FFEDED, border #FFB3B3, a red `circle-x` icon at the right, and a hint underneath in 400 14px #A81B1C.
- **Password field**: the design-system `TextInput` has no `type` prop, so the password input is a native `<input>` styled exactly like `TextInput`, with right padding 48px. An eye button sits inside it on the right: 48×48, radius 10px, transparent background, icon colour #5A5F7D; on hover the background is #EAF3FE and the icon #0B4DB3. The Lucide icon switches between `eye` and `eye-off`, and the aria-label between "Hiện mật khẩu" and "Ẩn mật khẩu". In production, add a `type` prop to TextInput instead of keeping a separate input.
- **Checkbox** (design-system `Checkbox`, 24px) followed by a text label, gap 12px. Clicking the label text also toggles the checkbox.
- **Primary submit**: `Button` with variant `primary`, size `lg`, full width; fill #0F62E0, edge #0B4DB3, white text. While submitting it shows the `loading` state (`aria-busy`).

## Screen 1 — Đăng nhập (`Dang nhap.dc.html`)
Order inside the card:
1. H1 "Đăng nhập" · subtitle "Chào mừng bạn quay lại FunEnglish!"
2. Google button "Đăng nhập với Google"
3. Divider "hoặc đăng nhập bằng email"
4. Email field: label "Email", placeholder "ten@email.com"
5. Password field: a label row with "Mật khẩu" on the left and a "Quên mật khẩu?" link (14px) on the right; placeholder "Nhập mật khẩu", `autocomplete="current-password"`
6. Checkbox "Ghi nhớ đăng nhập" (checked by default)
7. Submit "Đăng nhập"

Footer: "Chưa có tài khoản? **Đăng ký ngay**" → sign-up page.

Tweak (prototype only): `googlePosition` = `top` | `bottom`. With `bottom`, the Google button moves below the form after a divider labelled "hoặc". The default is `top`.

Validation (shown only after the first submit):
- Email empty → "Bạn chưa nhập email."; bad format → "Email chưa đúng định dạng, bạn kiểm tra lại nhé."
- Password empty → "Bạn chưa nhập mật khẩu."

## Screen 2 — Đăng ký (`Dang ky.dc.html`)
Order inside the card:
1. H1 "Đăng ký" · subtitle "Tạo tài khoản để bắt đầu học tiếng Anh cùng FunEnglish nhé!"
2. Google button "Đăng ký với Google"
3. Divider "hoặc đăng ký bằng email"
4. "Họ và tên": placeholder "Nguyễn Văn An"
5. "Email": placeholder "ten@email.com"
6. "Mật khẩu" (with the eye toggle): placeholder "Ít nhất 6 ký tự", `autocomplete="new-password"`. A hint is always shown underneath: "Dùng ít nhất 6 ký tự." in muted grey, which turns red on error.
7. "Nhập lại mật khẩu": no eye button of its own; it follows the same show/hide toggle as the first password field.
8. Checkbox with "Mình đồng ý với **Điều khoản sử dụng** và **Chính sách bảo mật**" (two links, align-items flex-start, unchecked by default)
9. Submit "Tạo tài khoản"

Footer: "Đã có tài khoản? **Đăng nhập**" → login page.

Validation (after the first submit):
- Name empty → "Bạn chưa nhập họ và tên."
- Email: same messages as on the login page.
- Password shorter than 6 characters → "Mật khẩu cần có ít nhất 6 ký tự."
- Repeat password: empty → "Bạn chưa nhập lại mật khẩu."; does not match → "Mật khẩu nhập lại chưa khớp."
- Terms checkbox not ticked → "Bạn cần đồng ý với điều khoản để tiếp tục."

## Interactions & Behavior
- Errors appear only after the first submit attempt (`tried` flag). After that they update live as the user types.
- A valid submit puts the submit button into its loading state. In the prototype it then redirects to `Trang chu.dc.html` after 900ms; in production, call the auth API and redirect on success.
- Clicking the Google button puts it into its loading state and, in the prototype, redirects after 900ms. In production, run the Google OAuth / Google Identity Services flow; on sign-up, create the account from the Google profile.
- "Quên mật khẩu?", "Điều khoản sử dụng" and "Chính sách bảo mật" are placeholder `#` links — those pages are not designed yet.
- Server errors (wrong password, email already registered) are not designed yet. Suggestion: the design-system `Alert` with tone danger, placed above the form.
- Responsive: the column stays at most 440px wide and shrinks with the 20px side padding on small screens.
- Transitions: `--transition-interactive` (background, border, colour and box-shadow at the fast duration with ease-out). Reduced motion is respected globally.

## State
Login: `email, password, pwShown, remember (true), tried, loading, googleLoading`.
Sign-up: `name, email, password, password2, pwShown, agree (false), tried, loading, googleLoading`.
Email rule: `/^\S+@\S+\.\S+$/`. Password rule: at least 6 characters.

## Design Tokens
**Colors:** primary #0F62E0 (strong/text #0B4DB3, bg #EAF3FE, bg-hover #D6E7FF, border #B3D3FF) · danger #DC2627 (strong/text #A81B1C, bg #FFEDED, border #FFB3B3) · success #14903F (text #11793A, bg #E8F9EE, border #A3E6BA).
Text: strong #1B1F3B, body #33385A, muted/placeholder #5A5F7D, disabled #878BA2. Links: #0F62E0, hover #0B4DB3. Surfaces: page #F6F7FA, card #FFFFFF, disabled #EEF0F5. Borders: subtle #EEF0F5, default #E0E4EE, strong #878BA2, hover #B5B9CC. Focus ring: 3px #B3D3FF.
**Type:** Lexend only. Sizes 12·14·16·18·20·24·30·36·44; weights 400/500/600/700; line-heights 1 / 1.15 / 1.3 / 1.5.
**Spacing:** 4·8·12·16·20·24·32·40·48·64 (`--space-1…10`).
**Controls:** heights 40·48·56·64. Icons 16·20·24·32.
**Radii:** 6·10·14·18·24·999 (controls 14, cards 18).
**Shadows:** card as above; buttons use a hard bottom edge of 4px (1px when pressed), never a blurred shadow.

## Assets
- Icons: Lucide (`lucide-static` icon font, loaded in `tokens/fonts.css`): eye, eye-off, circle-x.
- Google "G" mark: an inline SVG in the logic class. Replace it with Google's official branding asset.
- Font: Lexend (Google Fonts). No logo yet; "FunEnglish" is rendered as a text wordmark.

## Files
- `Dang nhap.dc.html` — login page (template + logic).
- `Dang ky.dc.html` — sign-up page (template + logic).
- `_ds/funenglish-design-system-…/` — design-system tokens (`tokens/*.css`), `styles.css`, and the compiled components `_ds_bundle.js`.
- `support.js` — the runtime needed to open the `.dc.html` files in a browser.
