# FlipCore App Dashboard Design System

**Source of truth:** `public/assets/css/app-dashboard.css`  
**Codename:** Stitch Unified Light Mode  
**Product:** Enterprise payroll & HRMS admin (FlipCore)  
**Primary mode:** Light (dark mode supported via `data-theme="dark"`)

---

## Brand & aesthetic

A calm, enterprise-grade admin UI with a soft indigo-lavender canvas, crisp navy typography, and confident blue primary actions. The look is **professional, data-dense, and trustworthy** — suitable for payroll operators, HR admins, and finance reviewers.

**Keywords:** unified light, soft indigo surfaces, monospace labels, rounded cards, subtle elevation, Material Symbols icons, AG Grid data tables.

**Avoid:** harsh pure white backgrounds, neon gradients, playful consumer-app styling, heavy glassmorphism, cramped tables, low-contrast gray-on-gray text.

---

## Color tokens (light mode)

All pages wrap content in `.ui-page` and consume CSS custom properties.

| Token | Hex / value | Usage |
|-------|-------------|-------|
| `--ui-bg` | `#faf8ff` | Page background |
| `--ui-surface` | `#faf8ff` | Base surface |
| `--ui-surface-low` | `#f2f3ff` | Tab bar, side panels, column panels |
| `--ui-surface-muted` | `#ffffff` | Cards, modals, inputs |
| `--ui-surface-container` | `#eaedff` | Secondary buttons, hover chips |
| `--ui-surface-container-high` | `#e2e7ff` | Table headers, pinned columns |
| `--ui-border` | `#c2c6d6` | Default borders |
| `--ui-border-strong` | `#727785` | Emphasized borders |
| `--ui-text` | `#131b2e` | Primary text |
| `--ui-text-muted` | `#424754` | Secondary text, table headers |
| `--ui-text-faint` | `#727785` | Placeholders, meta |
| `--ui-accent` | `#0058be` | Primary brand / CTA |
| `--ui-accent-hover` | `#004395` | Primary hover |
| `--ui-accent-soft` | `rgba(0, 88, 190, 0.08)` | Selected rows, hover fills |
| `--ui-accent-container` | `#2170e4` | Accent stat cards |
| `--ui-on-accent-container` | `#fefcff` | Text on accent surfaces |
| `--ui-primary-fixed` | `#d8e2ff` | Info badges, processing states |
| `--ui-success` | `#059669` | Success accent |
| `--ui-success-soft` | `#ecfdf5` | Success backgrounds |
| `--ui-success-text` | `#047857` | Success text |
| `--ui-danger` | `#ba1a1a` | Errors, destructive |
| `--ui-danger-soft` | `#fff1f2` | Danger backgrounds |
| `--ui-warning-bg` | `#ffdcc6` | Warning banners |
| `--ui-warning-text` | `#311400` | Warning text |
| `--ui-warning-accent` | `#924700` | Warning icons/links |
| `--ui-info-bg` | `#d8e2ff` | Info banners |
| `--ui-info-border` | `rgba(0, 88, 190, 0.2)` | Info borders |

### Semantic pairings

- **Addition / credit:** green soft bg `#ecfdf5`, text `#047857`
- **Deduction / debit:** rose soft bg `#fff1f2`, text `#be123c`
- **Leave columns:** `rgba(0, 88, 190, 0.06)` header tint
- **Deduction columns:** `rgba(186, 26, 26, 0.06)` header tint

---

## Color tokens (dark mode)

Activated on `html[data-theme="dark"]` or `body.theme-dark`.

| Token | Value |
|-------|-------|
| `--ui-bg` | `#131314` |
| `--ui-surface` | `#28292a` |
| `--ui-surface-low` | `rgba(255,255,255,0.03)` |
| `--ui-surface-muted` | `rgba(255,255,255,0.04)` |
| `--ui-border` | `#3c4043` |
| `--ui-text` | `#e3e3e3` |
| `--ui-text-muted` | `#9aa0a6` |
| `--ui-accent` | `#3b82f6` |
| `--ui-accent-hover` | `#2563eb` |

---

## Typography

| Role | Font | Weight | Notes |
|------|------|--------|-------|
| Body | Inter, Open Sans | 400–500 | Default UI copy |
| Headlines | Hanken Grotesk, Inter | 700 | Page titles, card titles |
| Labels / meta / buttons / tabs / badges | JetBrains Mono | 500–700 | Uppercase, letter-spacing `0.06–0.08em` |
| Code / IDs in tables | JetBrains Mono | 500 | Employee codes, numeric meta |

### Type scale

| Style | Size | Line height | Weight | Letter spacing |
|-------|------|-------------|--------|----------------|
| Page title | 30px (`1.875rem`) | 1.2 | 700 | -0.02em |
| Card title | 20px (`1.25rem`) | 1.2 | 700 | — |
| Section / insight title | 18px (`1.125rem`) | 1.35 | 700 | — |
| Body | 14px (`0.875rem`) | 1.5 | 400–500 | — |
| Body small | 13px (`0.8125rem`) | 1.45–1.55 | 400–600 | — |
| Label / badge / tab | 11px (`0.6875rem`) | 1 | 700 | 0.08em, uppercase |
| Micro label | 10px (`0.625rem`) | 1 | 700 | 0.08em, uppercase |

### Display (AI welcome)

- Size: `clamp(1.75rem, 4vw, 2.75rem)`
- Gradient text: `linear-gradient(135deg, #0058be 0%, #059669 100%)`

---

## Spacing & layout

| Token | Value |
|-------|-------|
| `--ui-page-gutter` | `clamp(1rem, 2.5vw, 2rem)` |
| `--ui-section-gap` | `clamp(1rem, 2.5vw, 2rem)` |
| `--ui-card-padding` | `clamp(1rem, 2vw, 1.5rem)` |

**Page structure:**

1. `.ui-page` wrapper on `<main>`
2. `.container-fluid.py-4`
3. `.ui-page-header` — title + subtitle + optional actions
4. `.ui-tab-bar` or `.ui-filter-pills` for navigation
5. Content cards / grids / tables

**Grids:**

- Stat grid: 6 → 3 → 2 columns (responsive)
- Two-column layout: `280px | 2fr`, stacks below 991px
- Report featured grid: 3 columns → 1 on mobile

---

## Shape & elevation

| Token | Value |
|-------|-------|
| `--ui-radius-lg` | `1rem` (16px) |
| `--ui-radius-xl` | `1.25rem` (20px) |
| `--ui-radius-2xl` | `1.5rem` (24px) |
| Pills / badges / tabs (active) | `999px` full round |

| Shadow | Value |
|--------|-------|
| `--ui-shadow-sm` | `0 1px 2px rgba(19,27,46,0.05)` |
| `--ui-shadow-md` | `0 10px 24px rgba(19,27,46,0.08)` |
| `--ui-shadow-lg` | `0 20px 40px rgba(19,27,46,0.1)` |
| `--ui-shadow-primary` | `0 10px 25px rgba(0,88,190,0.18)` |

**Roundness guidance for Stitch:** `ROUND_TWELVE` with full-round pills for chips and badges.

---

## Icons

Use **Material Symbols Outlined** (weight 400, fill 0). Common icons:

- Navigation: `dashboard`, `payments`, `group`, `calendar_month`, `analytics`
- Actions: `add`, `search`, `download`, `tune`, `arrow_back`, `close`
- AI: `auto_awesome`, `attach_file`, `mic`, `arrow_upward`, `history`
- Status: `warning`, `lock`, `description`

Icon button size: `2.75rem` square, radius `--ui-radius-xl`.

---

## Core components

### Page header

- Title: Hanken Grotesk 30px bold navy
- Subtitle: 14px muted, pipe separator `|`
- Optional right-aligned `.ui-page-actions`

### Primary button (`.ui-btn-primary`)

- Background: `#0058be`, white text
- Padding: `0.75rem 1.5rem`
- Radius: `--ui-radius-xl`
- Uppercase JetBrains Mono 12px, letter-spacing 0.06em
- Shadow: `--ui-shadow-primary`
- Hover: `#004395`; active scale 0.98

### Secondary button (`.ui-btn-secondary`)

- Background: `--ui-surface-container`
- Border: 1px `--ui-border`
- Same typography as primary but sentence case styling via mono font

### Tab bar (`.ui-tab-bar`)

- Container: `--ui-surface-low`, inset shadow, `--ui-radius-2xl`
- Tabs: uppercase mono labels; active tab filled `--ui-accent` with white text + primary shadow

### Filter pills (`.ui-filter-pill`)

- Pill shape, mono uppercase 11px
- Default: `--ui-surface-container-high` bg
- Active: `--ui-accent` fill, white text

### Card (`.ui-card`)

- White surface, `--ui-border`, `--ui-radius-2xl`, `--ui-shadow-lg`
- Header: title + description + toolbar actions, bottom border
- Body padding: 1.5rem

### Badges (`.ui-badge`)

- Pill, mono 11px uppercase
- Variants: `draft`, `processing`, `completed`, `locked`, `success`, `danger`, `info`, `neutral`

### Data table (`.ui-data-table`)

- Header: `--ui-surface-container-high`, mono 10px uppercase muted labels
- Rows: 13px body; hover `rgba(0,88,190,0.05)`
- Name cells: semibold; code cells: JetBrains Mono 12px muted

### Search field (`.ui-search-field`)

- Left-aligned search icon (Material Symbols)
- Input radius `--ui-radius-xl`
- Focus ring: `0 0 0 3px rgba(0,88,190,0.15)`

### Alert banners

- **Warning:** peach bg `#ffdcc6`, dark brown text
- **Info:** `--ui-info-bg`, accent border

### Modal (`.ui-modal-*`)

- Backdrop: `rgba(19,27,46,0.45)`
- Dialog: white card, 2xl radius, header/body/footer sections

### Stat cards (`.ui-stat-card`)

- White card with label (mono uppercase) + large value (24px bold)
- Accent variant: blue container with white text + soft glow orb

### Report featured card

- 3-column grid of selectable cards with icon tile, title, description, footer meta

### AI assistant shell (`.ui-ai-*`)

- Split layout: main chat + right history sidebar (256px)
- Welcome gradient headline + 3 suggestion cards in bento grid
- User bubbles: right-aligned, `--ui-surface-container-high`
- Assistant: left avatar (`auto_awesome`) + markdown body
- Composer: rounded shell, attach/mic/send, disclaimer microcopy below

---

## Data grid (AG Grid) theme

When designing spreadsheet-like views, match these Alpine overrides:

| Property | Light value |
|----------|-------------|
| Active color | `#0058be` |
| Background | `#ffffff` |
| Header bg | `#e2e7ff` |
| Header text | `#424754` mono uppercase |
| Row hover | `rgba(0,88,190,0.05)` |
| Odd row | `rgba(250,248,255,0.5)` |
| Border | `#c2c6d6` |
| Font | Inter 13px |

Pinned left columns use sticky shadow `4px 0 8px rgba(19,27,46,0.04)`.

---

## Interaction & motion

- Transitions: `0.15s ease` on color, border, background, box-shadow
- Primary button active: `scale(0.98)`
- Mobile sidebars: `translateX` slide `0.25s ease`
- Loading: small blue pulse dot for AI thinking state
- Hover reveals delete actions on history list items (opacity 0 → 1)

---

## Accessibility & content

- Maintain **4.5:1** contrast for body text on surfaces
- Financial figures: use tabular/monospace styling for alignment
- Status must not rely on color alone — pair badge text with label
- Destructive actions use `--ui-danger` and require confirmation in modals
- AI disclaimer: *"Payroll AI can make mistakes. Verify important financial data before final approval."*

---

## Screen patterns (Stitch)

When generating new screens for this product, follow these layouts:

1. **Dashboard hub** — page header, 6-stat grid, tab bar, primary card with table or grid
2. **Reports hub** — filter pills, 3 featured cards, list rows with icon + meta + actions
3. **Attendance hub** — tab bar (Policies, Monthly, Daily…), filter grid, data table or AG grid
4. **Payroll run detail** — back link, readiness banner, split adjustment grid
5. **AI assistant** — full-height shell, right history sidebar, centered chat column max ~52rem
6. **Compensation** — two-column master/detail, structure cards, component editor panels

---

## Stitch theme configuration (recommended)

| Setting | Value |
|---------|-------|
| `colorMode` | `LIGHT` |
| `customColor` | `#0058be` |
| `headlineFont` | `HANKEN_GROTESK` |
| `bodyFont` | `INTER` |
| `labelFont` | `JETBRAINS_MONO` |
| `roundness` | `ROUND_TWELVE` |
| `colorVariant` | `TONAL_SPOT` |

**Secondary accent (success):** `#059669`  
**Tertiary accent (warning):** `#924700` on `#ffdcc6`

---

## CSS class reference (implementation)

Prefix all layout/components with `ui-`. Page root: `main.main-content.ui-page`.

Key classes: `ui-page-header`, `ui-page-title`, `ui-page-subtitle`, `ui-tab-bar`, `ui-tab-btn`, `ui-card`, `ui-card-header`, `ui-card-body`, `ui-btn-primary`, `ui-btn-secondary`, `ui-badge`, `ui-data-table`, `ui-search-field`, `ui-filter-pill`, `ui-stat-grid`, `ui-stat-card`, `ui-modal-backdrop`, `ui-ai-shell`, `ui-ai-sidebar`, `ui-ai-suggestion-card`.

**Implementation file:** `public/assets/css/app-dashboard.css`
