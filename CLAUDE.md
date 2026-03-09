# CLAUDE.md


# ⛔ КРИТИЧЕСКИЕ ПРАВИЛА

## 🔴 ПРАВИЛО #0: ПЛАН → ОДОБРЕНИЕ → ПРИМЕНЕНИЕ

**CRITICAL:**

ЗАПРЕЩЕНО вносить любые изменения в код без предварительного плана и одобрения пользователя.

**Workflow:**
1. План
2. СТОП — ждать одобрения
3. Одобрение получено → выполнять


This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Maribulka** - A fullstack photography portfolio and booking management system built with Vue 3 + TypeScript frontend and PHP + MySQL backend, deployed on Beget shared hosting.

- **Production URL:** http://марибулька.рф (http://xn--80aac1alfd7a3a5g.xn--p1ai)
- **Admin Credentials:** login: `admin` / password: `123`

## Commands

### Development
```bash
cd maribulka-vue
npm run dev  # Starts dev server on http://localhost:5173
```

### Build
```bash
cd maribulka-vue
npm run build  # Compiles TypeScript and builds to dist/
```

### Deployment (3-step process)
```bash
git add . && git commit -m "..."
git push
.\deploy.ps1  # Windows - automated deploy script
# OR
./deploy.sh   # Linux/Mac - automated deploy script
```

The deploy script:
1. Checks git status
2. Builds Vue project
3. Creates server backup
4. Uploads dist/ and api/ to Beget via rsync/scp
5. Creates media symlink
6. Verifies deployment

### SSH Access
```bash
ssh -i ~/.ssh/beget_maribulka sava7424@sava7424.beget.tech
```

## Architecture

### Frontend (Vue 3 + TypeScript)
- **Location:** `maribulka-vue/src/`
- **Entry:** `App.vue` → imports global CSS, renders TopBar and routed pages
- **State Management:** Pinia stores (`src/stores/`)
  - `auth.ts` - Authentication & admin check (syncs with backend session)
  - `navigation.ts` - Client-side page routing
  - `bookings.ts` - Booking/calendar data
  - `finance.ts` - Income/expenses data
  - `references.ts` - Reference tables (clients, shooting types, etc.)
  - `home.ts` - Home page content
- **Components:** Single File Components in `src/components/` and `src/views/`
- **Build Output:** `maribulka-vue/dist/` (NOT in git)

### Backend (PHP 8.4)
- **Location:** `api/`
- **Database:** MySQL 8.0 (`sava7424_mari`)
- **API Endpoints:** RESTful PHP scripts in `api/` directory
  - `auth.php` - Session management
  - `bookings.php` - Calendar bookings CRUD
  - `expenses.php` - Expenses tracking
  - `income.php` - Income tracking
  - `clients.php`, `shooting-types.php`, `promotions.php` - Reference data
- **Credentials:** Stored in `.env` on server (NOT in git)

### Development Proxy
**CRITICAL:** No local PHP server! All `/api` and `/media` requests proxy to production via Vite (see `vite.config.ts`):
```typescript
server: {
  proxy: {
    '/api': { target: 'https://xn--80aac1alfd7a3a5g.xn--p1ai', ... },
    '/media': { target: 'https://xn--80aac1alfd7a3a5g.xn--p1ai', ... }
  }
}
```

### Build Optimization
Vite splits bundles into chunks:
- `vue-vendor` - Vue 3 + Pinia
- `fullcalendar` - FullCalendar libraries
- `charts` - Chart.js + TanStack Table
- Chunk size warning limit: 700kb

### Media Files
Uploaded images/photos stored **outside** `dist/` in `/home/s/sava7424/maribulka.rf/media/`
Symlink created during deploy: `public_html/media → ../media`

## CSS Architecture (STRICT RULES)

### Law
1. **NO `<style>` blocks in `.vue` files** - All styles in separate CSS files
2. **NO new CSS without explicit permission** - Use existing classes only
3. **100% CSS variables** from `style.css` - No hardcoded colors/sizes

### Global CSS files (imported in `main.ts`)

| File | Purpose |
|------|---------|
| `style.css` | CSS variables `[data-theme]`, body, animated orbs, scrollbar — lives at `src/style.css` (root, not assets/) |
| `buttonGlass.css` | Buttons `.btnGlass` + modifiers + `.btn-theme` |
| `padGlass.css` | Panels `.padGlass` + modifiers |
| `modal.css` | Overlay, inputs, `.padGlass.modal-sm`, `.ButtonFooter` |
| `animations.css` | Shared keyframe animations |

**Old CSS** (pre-05.03.2026) moved to `src/assets/oldCss/` — do NOT import them.

**Theme** is set on `<html>`: `document.documentElement.setAttribute('data-theme', 'dark')` — NOT on `#app`!

### Button System (new — public UI)
**Base class:** `.btnGlass`

```html
<!-- Large icon button (TopBar, panels) -->
<button class="btnGlass bigIcon" @click="onRipple($event)">
  <span class="inner-glow"></span>
  <span class="top-shine"></span>
  <svg-icon type="mdi" :path="someIcon" class="btn-icon-big" />
</button>

<!-- Icon + text button (modals, forms) -->
<button class="btnGlass iconText" @click="...">
  <span class="inner-glow"></span>
  <span class="top-shine"></span>
  <svg-icon type="mdi" :path="someIcon" class="btn-icon" />
  <span>Label</span>
</button>
```

**EVERY button must have** `<span class="inner-glow">` and `<span class="top-shine">` inside.

**Button System (old — admin/accounting UI only)**
`.buttonGL` system — used only in `src/components/accounting/`. Do NOT migrate without explicit task.

### Panel System
- `.padGlass` — base panel
- `.padGlass-top` — fixed top bar
- `.padGlass-work` — work area panel
- `.padGlass.modal-sm` — small modal panel (`min-width: auto; gap: 12px; padding: 20px`)

### Modals
- **Custom modals ONLY** - NO browser `alert()`/`confirm()`
- Use: `AlertModal.vue`, `ConfirmModal.vue`, `LoginModal.vue`
- **Structure:**
  ```html
  <div class="modal-overlay">
    <div class="padGlass modal-sm">
      <div class="modal-glassTitle">Title</div>  <!-- NO <h2>! -->
      <!-- content -->
      <div class="ButtonFooter PosCenter">
        <button class="btnGlass iconText">...</button>
      </div>
    </div>
  </div>
  ```
- Use `<Teleport to="body">` wrapper

### Footer positioning
```html
<div class="ButtonFooter PosRight">...</div>   <!-- Right-aligned -->
<div class="ButtonFooter PosLeft">...</div>    <!-- Left-aligned -->
<div class="ButtonFooter PosSpace">...</div>   <!-- Space-between -->
<div class="ButtonFooter PosCenter">...</div>  <!-- Centered -->
```

### Icons
- **Library:** `@mdi/js` (Material Design Icons)
- **Component:** `@jamescoyle/vue-icon`
- **Usage:** `<svg-icon type="mdi" :path="mdiCheckCircleOutline" />` — **NO `:size` prop**

### Reference (Sandbox)
`src/sandbox/SandboxView.vue` is the design reference. Copy HTML **exactly as-is** — no modifications.

### Known CSS Traps

- **DO NOT** add `overflow: hidden` to `.padGlass` — it breaks CKEditor balloon/dropdown panels (creates clip container)

### Mobile Adaptation
- **Single breakpoint:** `@media (pointer: coarse)`

### Sticky Elements
Use `position: sticky`, NOT `fixed`

## Backend Principles

### Data Aggregation
**Backend computes, Frontend displays:**
```typescript
// ❌ WRONG - Don't fetch all records to sum on client
const expenses = await fetch('/api/expenses.php')
const total = expenses.reduce((sum, e) => sum + e.amount, 0)

// ✅ CORRECT - Backend returns aggregated result
const { balance } = await fetch('/api/expenses.php?balance=true').then(r => r.json())
```

Backend uses SQL `SUM()`, `COUNT()`, `AVG()` and returns final numbers.

### Authentication Flow
1. Frontend calls `/api/auth.php?action=check` on mount
2. Backend validates PHP session
3. If valid admin session → `isAdmin = true`, sync to `localStorage`
4. Frontend shows/hides admin features based on `useAuthStore().isAdmin`

## Key Libraries

- **FullCalendar** 6.1.20 - Booking calendar with day/time grid
- **CKEditor 5** (licenseKey: `'GPL'`) - Rich text editor for Home page blocks
  - Component: `RichTextEditor.vue`, upload via `SimpleUploadAdapter`
  - On image upload: auto-set 30% width via `resizedWidth` attribute (NOT `width`!)
  - Upload endpoint: `POST /api/upload-image.php` (admin session required)
- **TipTap** 3.20.0 - Rich text editor (other places, replaced Quill Feb 26, 2026)
  - Extensions: Image (with resize), Link (custom modal), Color, TextAlign, Underline
  - `allowBase64: true` for embedded images
- **Pinia** - State management
- **TanStack Table** - Data tables
- **Chart.js** - Analytics charts
- **flatpickr** - Date/period pickers with presets

## Project Structure Details

```
maribulka/
├── maribulka-vue/          # Frontend app
│   ├── src/
│   │   ├── components/     # Vue SFCs
│   │   │   ├── accounting/ # Admin booking/finance components
│   │   │   ├── home/       # Public home page: Home.vue, EditBlockModal.vue
│   │   │   ├── TopBar.vue, SideBar.vue
│   │   │   └── *Modal.vue  # Reusable modals
│   │   ├── views/          # Page-level components
│   │   ├── stores/         # Pinia state
│   │   ├── assets/         # CSS files (NO images here!)
│   │   ├── utils/          # icons.ts (if exists)
│   │   ├── App.vue         # Root component
│   │   └── main.ts         # Entry point
│   ├── public/             # Static assets
│   ├── dist/               # Build output (gitignored)
│   ├── package.json
│   └── vite.config.ts
├── api/                    # PHP backend
│   ├── database.php        # DB connection
│   ├── *.php               # API endpoints
│   └── migrations/         # SQL migrations
├── deploy.ps1, deploy.sh   # Deployment scripts
└── README.md
```

## Important Notes

1. **NO style blocks in Vue files** - Always use external CSS
2. **Always use CSS variables** - Check `style.css` for available vars (`theme.css` was deleted 05.03.2026)
3. **Backend aggregates data** - Frontend should NOT sum/count large arrays
4. **Custom modals only** - No browser dialogs
5. **Icons via @mdi/js** - No other icon libraries
6. **Media files outside dist/** - Server symlink handles access
7. **Proxy to production** - Dev environment has no local PHP
8. **Admin-only features** - Check `authStore.isAdmin` before rendering
9. **Mobile breakpoint:** `@media (pointer: coarse)` — by device type, NOT pixel width
10. **Deploy is 3 commands:** commit, push, `.\deploy.ps1`

## Memory Files

The project has extensive documentation in `C:\Users\sava\.claude\projects\d--GitHub-maribulka\memory\`:
- `MEMORY.md` - Quick reference (always loaded)
- `home-stack.md` - Home page: sticky panels, CKEditor, smart scroll logic
- `buttons-refactoring.md` - Button system (refactoring completed Mar 2, 2026)
- `styles.md` - CSS organization details
- `patterns.md` - Code patterns and examples
- `architecture.md` - Technical architecture deep-dive
- `deployment.md` - Server/deploy details
- Other topic-specific files

**Check memory files for detailed context before making architectural changes.**
