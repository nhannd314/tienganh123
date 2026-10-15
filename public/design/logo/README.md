# Handoff: Logo hoctienganh.pro.vn — phương án 1a "Bong bóng hội thoại"

## Overview
Brand logo for the kids' English-learning site **hoctienganh.pro.vn**. Replaces the placeholder "FunEnglish" text mark in the NavBar, footer, favicon and app icon on every page.

## About the Design Files
The files here are **design references made in HTML/SVG** — not production code. Recreate the logo in the target codebase (e.g. as an inline SVG React component and static SVG/PNG assets) using its existing patterns. `Logo.dc.html` shows all four explored options; **only option 1a is approved**.

## Fidelity
**High-fidelity.** Colours, type, proportions are final. Before shipping, a designer should **convert the text in the SVGs to outlines** (paths) so the logo doesn't depend on the Lexend font being installed — SVGs loaded via `<img>` cannot load web fonts.

## Logo anatomy (reference size: mark 88 × 88)
**Mark — speech bubble**
- Bubble: rounded rectangle **88 × 72**, radius **24**, fill **#0F62E0** (blue-600).
- Tail: right triangle under the bubble's bottom-left, **24 × 24**, starting **18px** from the bubble's left edge, overlapping the bubble by 8px (top at y=64, tip at y=88), points: (18,64) (42,64) (18,88). Same fill.
- Glyphs: **"Aa"**, Lexend **700**, **36px**, letter-spacing **-1px**, **#FFFFFF**, centred in the 88 × 72 bubble.
- Accent dot: circle **Ø24**, **#FFD23F** (yellow-500) with a **4px #FFFFFF** ring (Ø32 total), overlapping the top-right corner by 6px (right:-6, top:-6). The dot is dropped on dark backgrounds and at icon sizes ≤ 64px.

**Wordmark**
- Line 1: **"Học"** #1B1F3B (ink-900) + **"TiếngAnh"** #0F62E0 (blue-600), no space, Lexend **700**, **40px**, line-height 1, letter-spacing **-1px**, nowrap.
- Line 2: **"hoctienganh.pro.vn"**, Lexend **500**, **16px**, letter-spacing **0.5px**, #5A5F7D (ink-500 / --text-muted).
- Gap between lines **4px**; gap between mark and wordmark **16px** (--space-4). Wordmark block is vertically centred against the mark.

## Variants
1. **Primary (light bg)** — as above. `assets/logo-1a-primary.svg`
2. **On dark** (#1B1F3B bg) — mark 52 × 42 bubble, radius 14, fill **#1675FE** (blue-500), "Aa" 21px **#1B1F3B**; no dot. Wordmark 26px: "Học" #FFFFFF + "TiếngAnh" **#B3D3FF** (blue-200); gap 12px. Domain line optional, #B5B9CC. `assets/logo-1a-on-dark.svg`
3. **Mark only** — `assets/logo-1a-mark.svg`
4. **App icon / favicon** — square, radius = 25% of side (64px → 16, 32px → 9), fill #0F62E0, "Aa" white Lexend 700 at ~40% of side (64 → 26px, 32 → 14px). No tail, no dot. `assets/app-icon-1a.svg` (512px master; export PNG 512/192/180/32/16 + favicon.ico).

## Usage
- **NavBar** (header height 80px): mark height **40px** (scale whole lockup proportionally, ≈ 0.45×), drop the domain line → wordmark ~20px. Logo links to the home page (`Trang chu`), aria-label "Học Tiếng Anh — Trang chủ".
- **Minimum size**: full lockup 140px wide; below that use mark only.
- **Clear space**: at least the height of the "A" glyph on every side.
- Don't: recolour outside the palette, add shadows/gradients, put the primary version on photos or mid-tone backgrounds (use the on-dark version or a white plate), stretch, or rearrange the two colours in the wordmark.

## Design Tokens
- blue-600 #0F62E0 · blue-500 #1675FE · blue-700 #0B4DB3 · blue-200 #B3D3FF
- yellow-500 #FFD23F · white #FFFFFF
- ink-900 #1B1F3B · ink-500 #5A5F7D · ink-300 #B5B9CC
- Font: **Lexend** (Google Fonts), weights 500 and 700.

## Interactions & Behavior
Static logo. In the NavBar it's a link: no hover colour change; focus-visible uses the global 3px #B3D3FF focus ring.

## Assets
`assets/` — SVG exports of 1a (text still live, outline before production).

## Files
- `Logo.dc.html` — all four explored options (section 1a is the approved one); open in a browser (requires `support.js` and the `_ds` design-system folder from the project for fonts/tokens).
- `assets/*.svg` — production-oriented exports.
