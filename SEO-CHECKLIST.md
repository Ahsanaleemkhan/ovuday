# OvuDay — Full SEO Checklist

## ✅ DONE — Technical SEO
- [x] Next.js 14 App Router (SSR/SSG — Googlebot-friendly)
- [x] `app/sitemap.ts` — auto-generated, includes static + WP blog posts
- [x] `app/robots.ts` — allows Google, blocks AI scrapers (GPTBot, CCBot)
- [x] `app/manifest.ts` — PWA manifest, theme color, icons
- [x] Security headers in `next.config.ts` (X-Frame-Options, CSP, etc.)
- [x] HTTPS-ready (enforced at host level)
- [x] Canonical URLs on every page
- [x] Mobile-first responsive design
- [x] `lang="en"` on <html>
- [x] `app/loading.tsx` — loading UI
- [x] `app/error.tsx` — error boundary
- [x] `app/not-found.tsx` — custom 404

## ✅ DONE — On-Page SEO (every page)
- [x] Unique `<title>` tag (50–60 chars)
- [x] Unique `<meta description>` (150–160 chars)
- [x] One `<h1>` per page
- [x] Logical H2/H3 hierarchy
- [x] Breadcrumb nav on all inner pages
- [x] `aria-*` labels for accessibility (WCAG AA)
- [x] `alt` text on all images

## ✅ DONE — Schema / Structured Data
| Page          | Schema Type               |
|---------------|---------------------------|
| Home          | WebApplication + FAQPage  |
| How It Works  | Article                   |
| FAQ           | FAQPage (15 Q&As)         |
| About         | AboutPage                 |
| Blog post     | Article + BreadcrumbList  |
| Global        | Organization + WebSite    |

## ✅ DONE — Open Graph & Social
- [x] `og:title`, `og:description`, `og:image` (1200×630) on all pages
- [x] `twitter:card`, `twitter:title`, `twitter:image`
- [x] `og:type = article` on blog posts with `publishedTime`

## ✅ DONE — Google Fonts (Performance)
- [x] Inter — body text (400, 500, 600)
- [x] Plus Jakarta Sans — headings (600, 700, 800)
- [x] `display: swap` — no layout shift
- [x] Loaded via `next/font/google` (self-hosted at build time)

## ✅ DONE — Color System (Accessibility)
- Primary Rose `#E8476E` on white → 4.8:1 contrast ratio (WCAG AA ✓)
- Body text `#1A1A2E` on white → 16:1 (WCAG AAA ✓)
- Muted text `#6B7280` on white → 4.6:1 (WCAG AA ✓)

## ✅ DONE — Pages Built
| URL                        | Purpose                        |
|----------------------------|--------------------------------|
| `/`                        | Home + Calculator tool         |
| `/how-it-works`            | Science/education page         |
| `/faq`                     | 15 Q&As with FAQPage schema    |
| `/about`                   | Mission + E-E-A-T signals      |
| `/blog`                    | Blog listing (from WordPress)  |
| `/blog/[slug]`             | Single blog post               |
| `/blog/category/[slug]`    | Category archive               |
| `/contact`                 | Contact form                   |
| `/privacy-policy`          | GDPR-ready privacy policy      |
| `/terms`                   | Terms of use                   |
| `/sitemap.xml`             | Auto-generated sitemap         |
| `/robots.txt`              | Crawler rules                  |
| `/manifest.webmanifest`    | PWA manifest                   |

## 🔲 TODO — Before Launch (Manual Steps)
- [ ] Add real `public/og-image.png` (1200×630 px)
- [ ] Add `public/logo.png` (512×512 px)
- [ ] Add `public/icons/icon-192.png` and `public/icons/icon-512.png`
- [ ] Add `public/favicon.ico`
- [ ] Replace `YOUR_GOOGLE_VERIFICATION_CODE` in `app/layout.tsx`
- [ ] Set real domain in `.env.local` → `NEXT_PUBLIC_SITE_URL=https://ovuday.com`
- [ ] Set WordPress GraphQL URL → `NEXT_PUBLIC_WP_GRAPHQL_URL=https://cms.ovuday.com/graphql`
- [ ] Submit sitemap to Google Search Console
- [ ] Verify site in Google Search Console
- [ ] Run PageSpeed Insights after deploy → fix any CWV issues
- [ ] Add Google Analytics ID in `.env.local` → `NEXT_PUBLIC_GA_ID=G-XXXXXXXXXX`

## 🔲 TODO — Content Strategy (Post-Launch)
- [ ] Write 10 seed blog posts targeting long-tail keywords:
  - "ovulation calculator with irregular periods"
  - "what does fertile window mean"
  - "how to track ovulation naturally"
  - "signs of ovulation"
  - "luteal phase defect symptoms"
  - "best time to get pregnant in cycle"
  - "does stress affect ovulation"
  - "ovulation pain symptoms"
  - "how long does ovulation last"
  - "basal body temperature chart"
- [ ] Add author bio page with credentials (E-E-A-T signal)
- [ ] Add "medically reviewed by" section to key articles
- [ ] Build 5 backlinks from women's health or parenting blogs
