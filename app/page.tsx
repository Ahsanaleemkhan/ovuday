import type { Metadata } from "next";
import OvulationCalculator from "@/components/OvulationCalculator";
import JsonLd from "@/components/JsonLd";
import ScrollToTopButton from "@/components/ScrollToTopButton";
import Link from "next/link";
import Image from "next/image";
import { getSiteContent, fetchGraphQL } from "@/lib/graphql";
import { GET_POSTS } from "@/lib/queries";
import { parseSiteCopy, t } from "@/lib/siteCopy";
import {
  Lock, Zap, Gift, CalendarDays,
  Check, ArrowRight, Plus, Flower, HeartPulse, Activity, Star,
} from "@/components/icons";

/* ─── SEO ────────────────────────────────────────────────── */
export const metadata: Metadata = {
  title: "Free Ovulation Calculator — Know Your Fertile Window | OvuDay",
  description:
    "Use OvuDay's free ovulation calculator to find your most fertile days. Enter your last period date and cycle length to instantly see your ovulation date, fertile window, and next 3 cycle predictions.",
  alternates: { canonical: "https://ovuday.com" },
  openGraph: {
    title: "Free Ovulation Calculator — Know Your Fertile Window",
    description: "Find your ovulation date and fertile window instantly. Free, accurate, and private.",
    url: "https://ovuday.com",
  },
};

/* ─── Schemas ────────────────────────────────────────────── */
const calculatorSchema = {
  "@context": "https://schema.org",
  "@type": "WebApplication",
  name: "OvuDay Ovulation Calculator",
  description: "Free ovulation calculator that predicts your ovulation date, fertile window, and next menstrual period.",
  url: "https://ovuday.com",
  applicationCategory: "HealthApplication",
  operatingSystem: "Any",
  offers: { "@type": "Offer", price: "0", priceCurrency: "USD" },
  featureList: ["Ovulation date prediction", "Fertile window calculation", "Next period prediction", "3-cycle forecast"],
};

/* ─── Static content ─────────────────────────────────────── */
const fallbackStats = [
  { value: "100%", label: "Free forever"      },
  { value: "0",    label: "Data stored"       },
  { value: "6",    label: "Fertile days shown"},
  { value: "3",    label: "Cycles forecast"   },
];

const fallbackTrustPills = ["No data stored", "Works offline", "100% free", "No sign-up"];

const fallbackFeatures = [
  { Icon: Lock,        title: "100% Private",     desc: "Everything runs in your browser. Nothing is sent to our servers."             },
  { Icon: Zap,         title: "Instant Results",  desc: "Get your ovulation date, fertile window and next period in one click."        },
  { Icon: CalendarDays,title: "3-Cycle Forecast", desc: "See your next 3 cycles planned out so you can prepare ahead."                },
  { Icon: Gift,        title: "Always Free",      desc: "No subscription, no sign-up — just clear, accurate results."                },
];

const fallbackSteps = [
  { Icon: CalendarDays, step: "1", title: "Enter Your Last Period Date",  desc: "Select the first day of your most recent period. This is Day 1 of your cycle." },
  { Icon: Activity,     step: "2", title: "Set Your Cycle Length",        desc: "Average is 28 days, but cycles vary 21–45 days. Use your personal 3-month average." },
  { Icon: Flower,       step: "3", title: "Get Your Full Forecast",       desc: "See your ovulation date, 6-day fertile window, next period, and 3 future cycles instantly." },
];

const fallbackFaqs = [
  { q: "How is my ovulation day calculated?",     a: "Ovulation typically occurs 14 days before your next period (the luteal phase). If your cycle is 28 days, ovulation falls around day 14. OvuDay calculates this automatically." },
  { q: "What is the fertile window?",             a: "The fertile window spans 6 days — the 5 days before ovulation and ovulation day itself. Sperm can survive up to 5 days, so intercourse during this window maximises conception chances." },
  { q: "My cycles are irregular. Can I use this?",a: "Yes. Use your average cycle length from the past 3–6 months. For highly irregular cycles, track your basal body temperature (BBT) or use LH test strips alongside this calculator." },
  { q: "What is the luteal phase?",               a: "The luteal phase is the time from ovulation to your next period — typically 12–16 days (average 14). A consistent luteal phase is a sign of healthy progesterone levels." },
];

/* ─── Page ───────────────────────────────────────────────── */
export default async function HomePage() {
  const siteContent = await getSiteContent();
  const copy = parseSiteCopy(siteContent?.siteCopyJson);
  const calculator = siteContent?.calculator;

  // Fetch latest blog posts for the blog section
  let blogPosts: Array<{
    slug: string; title: string; excerpt: string; date: string;
    featuredImage?: { node: { sourceUrl: string; altText: string } } | null;
    categories?: { nodes: { name: string; slug: string }[] };
  }> = [];
  try {
    const postsData = await fetchGraphQL<{ posts: { nodes: typeof blogPosts } }>(GET_POSTS, { first: 3 }, 600);
    blogPosts = postsData.posts.nodes;
  } catch { /* gracefully show no blog section if posts fail */ }

  const iconMap = {
    Lock,
    Zap,
    Gift,
    CalendarDays,
    Activity,
    Flower,
    Star,
  } as const;

  const heroBadge = siteContent?.hero?.badgeText || t(copy, "home.hero.badge", "Free Ovulation Calculator");
  const heroTitleLead = siteContent?.hero?.headline || t(copy, "home.hero.titleLead", "Know Your");
  const heroTitleAccent = siteContent?.hero?.headlineAccent || t(copy, "home.hero.titleAccent", "Fertile Window");
  const heroSubtitle =
    siteContent?.hero?.subheadline ||
    t(copy, "home.hero.subtitle", "Enter your last period date and cycle length — get your ovulation day, fertile window, and 3-cycle forecast in seconds.");

  const trustPills = siteContent?.trust?.length
    ? siteContent.trust.map((item) => item.text)
    : fallbackTrustPills;

  const stats = siteContent?.stats?.length
    ? siteContent.stats.map((item) => ({
        value: `${item.value}${item.suffix || ""}`,
        label: item.label,
      }))
    : fallbackStats;

  const features = siteContent?.features?.length
    ? siteContent.features.map((item) => ({
        Icon: iconMap[item.icon as keyof typeof iconMap] || Star,
        title: item.title,
        desc: item.description,
      }))
    : fallbackFeatures;

  const steps = siteContent?.steps?.length
    ? siteContent.steps.map((item, index) => ({
        Icon: iconMap[item.icon as keyof typeof iconMap] || CalendarDays,
        step: item.number || String(index + 1),
        title: item.title,
        desc: item.description,
      }))
    : fallbackSteps;

  const faqs = siteContent?.faq?.length
    ? siteContent.faq.map((item) => ({
        q: item.question,
        a: item.answer,
      }))
    : fallbackFaqs;

  const featuresTitle = siteContent?.featuresSectionTitle || t(copy, "home.features.title", "Built for Women, Not for Data");
  const featuresSubtitle =
    siteContent?.featuresSectionSubtitle ||
    t(copy, "home.features.subtitle", "Simple, private, and accurate — everything you need to understand your cycle.");

  const stepsTitle = siteContent?.stepsSectionTitle || t(copy, "home.steps.title", "3 Steps to Your Fertile Window");
  const stepsSubtitle =
    siteContent?.stepsSectionSubtitle ||
    t(copy, "home.steps.subtitle", "Based on the standard calendar method, personalised to your cycle.");

  const faqTitle = siteContent?.faqSectionTitle || t(copy, "home.faq.title", "Common Questions");
  const faqSubtitle =
    siteContent?.faqSectionSubtitle ||
    t(copy, "home.faq.subtitle", "Quick answers to what women ask most about ovulation tracking.");

  const ctaTitle = siteContent?.cta?.title || t(copy, "home.cta.title", "Ready to Know Your Fertile Window?");
  const ctaSubtitle =
    siteContent?.cta?.subtitle ||
    t(copy, "home.cta.subtitle", "Join thousands of women who use OvuDay to understand their cycle and plan their journey to parenthood — completely free.");

  const faqSchemaData = {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    mainEntity: faqs.map((item) => ({
      "@type": "Question",
      name: item.q,
      acceptedAnswer: {
        "@type": "Answer",
        text: item.a.replace(/<[^>]+>/g, ""),
      },
    })),
  };

  return (
    <>
      <JsonLd data={calculatorSchema} />
      <JsonLd data={faqSchemaData} />

      {/* ════════════════════════════════
          HERO
         ════════════════════════════════ */}
      <section
        aria-labelledby="hero-heading"
        style={{
          background: "linear-gradient(135deg,#FFF0F5 0%,#FFF8FB 55%,#F5F0FF 100%)",
          position: "relative", overflow: "hidden",
        }}
      >
        {/* Decorative blobs */}
        <span aria-hidden="true" style={{ position:"absolute", top:"-80px", left:"-80px", width:"400px", height:"400px", borderRadius:"50%", background:"radial-gradient(circle,#FFB3CD44 0%,transparent 70%)", pointerEvents:"none" }} />
        <span aria-hidden="true" style={{ position:"absolute", bottom:"-100px", right:"-60px", width:"360px", height:"360px", borderRadius:"50%", background:"radial-gradient(circle,#C4B5FD33 0%,transparent 70%)", pointerEvents:"none" }} />

        <div className="container-main py-12 sm:py-16 lg:py-20">
          <div className="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">

            {/* LEFT — Copy */}
            <div className="text-center lg:text-left">
              <div className="badge mb-5 lg:mx-0 mx-auto w-fit flex items-center gap-1.5">
                <Flower size={13} aria-hidden="true" />
                {heroBadge}
              </div>

              <h1
                id="hero-heading"
                className="text-[2.2rem] sm:text-[2.8rem] lg:text-[3.2rem] leading-[1.15]"
              >
                {heroTitleLead}{" "}
                <span style={{
                  backgroundImage: "linear-gradient(135deg, var(--color-primary) 0%, #7C5CBF 100%)",
                  WebkitBackgroundClip: "text", WebkitTextFillColor: "transparent", backgroundClip: "text",
                }}>
                  {heroTitleAccent}
                </span>
                <br />Instantly
              </h1>

              <p className="mx-auto lg:mx-0 mt-5 max-w-md text-lg leading-relaxed" style={{ color: "#4B5563" }}>
                {heroSubtitle}
              </p>

              {/* Trust pills */}
              <div className="mt-7 flex flex-wrap justify-center lg:justify-start gap-2">
                {trustPills.map((pill) => (
                  <span
                    key={pill}
                    className="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold"
                    style={{ borderColor: "var(--color-border)", color: "var(--color-text)", background: "white" }}
                  >
                    <Check size={11} style={{ color: "var(--color-primary)" }} aria-hidden="true" />
                    {pill}
                  </span>
                ))}
              </div>

              {/* Stats */}
              <div className="mt-10 grid grid-cols-4 gap-4">
                {stats.map(({ value, label }) => (
                  <div key={label} className="text-center lg:text-left">
                    <p className="font-heading text-2xl font-bold" style={{ color: "var(--color-primary)" }}>
                      {value}
                    </p>
                    <p className="mt-0.5 text-xs leading-tight" style={{ color: "var(--color-muted)" }}>
                      {label}
                    </p>
                  </div>
                ))}
              </div>

              <p className="mt-8 hidden lg:flex items-center gap-2 text-sm" style={{ color: "var(--color-muted)" }}>
                <ArrowRight size={14} style={{ color: "var(--color-primary)" }} aria-hidden="true" />
                Use the calculator on the right to get started
              </p>
            </div>

            {/* RIGHT — Calculator */}
            <div className="relative">
              <div aria-hidden="true" style={{ position:"absolute", inset:"-12px", borderRadius:"2rem", background:"linear-gradient(135deg,#FFB3CD33 0%,#C4B5FD22 100%)", filter:"blur(16px)", zIndex:0 }} />
              <div style={{ position:"relative", zIndex:1 }}>
                <OvulationCalculator calculator={calculator} />
              </div>
            </div>

          </div>
        </div>
      </section>

      {/* ════════════════════════════════
          FEATURES
         ════════════════════════════════ */}
      <section className="py-20" aria-labelledby="features-heading">
        <div className="container-main">
          <div className="section-title mb-12">
            <div className="badge mx-auto mb-4 w-fit">Why OvuDay</div>
            <h2 id="features-heading">{featuresTitle}</h2>
            <p>{featuresSubtitle}</p>
          </div>
          <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {features.map(({ Icon, title, desc }) => (
              <div key={title} className="card text-center transition-shadow hover:shadow-panel">
                <span
                  className="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-2xl"
                  style={{ background: "var(--color-primary-bg)", color: "var(--color-primary)" }}
                  aria-hidden="true"
                >
                  <Icon size={26} />
                </span>
                <h3 className="mt-4 text-base">{title}</h3>
                <p className="mt-1.5 text-sm leading-relaxed" style={{ color: "var(--color-muted)" }}>{desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ════════════════════════════════
          HOW IT WORKS
         ════════════════════════════════ */}
      <section className="py-20" style={{ background: "var(--color-surface)" }} aria-labelledby="how-heading">
        <div className="container-main max-w-3xl">
          <div className="section-title mb-12">
            <div className="badge mx-auto mb-4 w-fit">How It Works</div>
            <h2 id="how-heading">{stepsTitle}</h2>
            <p>{stepsSubtitle}</p>
          </div>
          <ol className="space-y-0" role="list">
            {steps.map(({ Icon, step, title, desc }, i, arr) => (
              <li key={step} className="flex gap-5">
                <div className="flex flex-col items-center">
                  <span
                    className="flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-white shadow-md"
                    style={{ background: "var(--color-primary)" }}
                    aria-hidden="true"
                  >
                    <Icon size={22} />
                  </span>
                  {i < arr.length - 1 && (
                    <span className="mt-1 w-px flex-1 rounded-full" style={{ minHeight:"40px", background:"var(--color-border)" }} aria-hidden="true" />
                  )}
                </div>
                <div className="pb-8 pt-2">
                  <p className="mb-1 text-xs font-bold uppercase tracking-widest" style={{ color: "var(--color-primary)" }}>
                    Step {step}
                  </p>
                  <h3 className="mb-1.5 text-base">{title}</h3>
                  <p className="text-sm leading-relaxed" style={{ color: "var(--color-muted)" }}>{desc}</p>
                </div>
              </li>
            ))}
          </ol>
          <div className="mt-4 text-center">
            <Link href="/how-it-works" className="btn-secondary inline-flex items-center gap-2 no-underline">
              Learn the science
              <ArrowRight size={15} aria-hidden="true" />
            </Link>
          </div>
        </div>
      </section>

      {/* ════════════════════════════════
          FAQ
         ════════════════════════════════ */}
      <section className="py-20" aria-labelledby="faq-heading">
        <div className="container-main max-w-3xl">
          <div className="section-title mb-12">
            <div className="badge mx-auto mb-4 w-fit">FAQ</div>
            <h2 id="faq-heading">{faqTitle}</h2>
            <p>{faqSubtitle}</p>
          </div>
          <div className="space-y-3">
            {faqs.map(({ q, a }) => (
              <details key={q} className="card group cursor-pointer">
                <summary className="flex items-center justify-between gap-4 text-base font-semibold list-none" style={{ color: "var(--color-text)" }}>
                  {q}
                  <span
                    className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full transition-transform duration-200 group-open:rotate-45"
                    style={{ background: "var(--color-primary-bg)", color: "var(--color-primary)" }}
                    aria-hidden="true"
                  >
                    <Plus size={14} />
                  </span>
                </summary>
                <p
                  className="mt-3 text-sm leading-relaxed"
                  style={{ color: "#4B5563" }}
                  dangerouslySetInnerHTML={{ __html: a }}
                />
              </details>
            ))}
          </div>
          <div className="mt-8 text-center">
            <Link href="/faq" className="inline-flex items-center gap-1.5 text-sm font-semibold no-underline hover:underline" style={{ color: "var(--color-primary)" }}>
              See all FAQs <ArrowRight size={14} aria-hidden="true" />
            </Link>
          </div>
        </div>
      </section>

      {/* ════════════════════════════════
          BLOG
         ════════════════════════════════ */}
      {blogPosts.length > 0 && (
        <section
          className="py-20"
          style={{ background: "var(--color-surface)" }}
          aria-labelledby="blog-heading"
        >
          <div className="container-main">
            <div className="section-title mb-12">
              <div className="badge mx-auto mb-4 w-fit">Blog</div>
              <h2 id="blog-heading">Latest from the Blog</h2>
              <p>Expert-written articles to help you understand your cycle and fertility.</p>
            </div>
            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
              {blogPosts.map((post) => {
                const cat = post.categories?.nodes?.[0];
                const d = new Date(post.date);
                const months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
                const dateStr = `${months[d.getUTCMonth()]} ${d.getUTCDate()}, ${d.getUTCFullYear()}`;
                return (
                  <article
                    key={post.slug}
                    className="card group flex flex-col overflow-hidden p-0"
                    style={{ transition: "transform .2s, box-shadow .2s" }}
                  >
                    <Link href={`/blog/${post.slug}`} tabIndex={-1} aria-hidden="true">
                      <div className="relative h-48 overflow-hidden" style={{ background: "linear-gradient(135deg, #FFF0F5, #F5F0FF)" }}>
                        {post.featuredImage ? (
                          <Image
                            src={post.featuredImage.node.sourceUrl}
                            alt={post.featuredImage.node.altText || post.title}
                            fill
                            sizes="(max-width:640px) 100vw, 33vw"
                            className="object-cover transition-transform duration-300 group-hover:scale-105"
                          />
                        ) : (
                          <div className="flex h-full items-center justify-center">
                            <Flower size={48} style={{ color: "var(--color-primary)", opacity: 0.3 }} />
                          </div>
                        )}
                      </div>
                    </Link>
                    <div className="flex flex-1 flex-col p-5">
                      {cat && (
                        <span
                          className="mb-2 w-fit rounded-full px-2.5 py-0.5 text-xs font-semibold"
                          style={{ background: "var(--color-primary-bg)", color: "var(--color-primary)" }}
                        >
                          {cat.name}
                        </span>
                      )}
                      <h3 className="text-base leading-snug">
                        <Link
                          href={`/blog/${post.slug}`}
                          className="no-underline hover:underline"
                          style={{ color: "var(--color-text)" }}
                        >
                          {post.title}
                        </Link>
                      </h3>
                      <div
                        className="mt-2 text-sm leading-relaxed line-clamp-2"
                        style={{ color: "var(--color-muted)" }}
                        dangerouslySetInnerHTML={{ __html: post.excerpt }}
                      />
                      <div
                        className="mt-auto flex items-center justify-between border-t pt-3 text-xs"
                        style={{ borderColor: "var(--color-border)", color: "var(--color-muted)", marginTop: "12px" }}
                      >
                        <span>{dateStr}</span>
                        <Link
                          href={`/blog/${post.slug}`}
                          className="inline-flex items-center gap-1 font-semibold no-underline hover:underline"
                          style={{ color: "var(--color-primary)" }}
                        >
                          Read more <ArrowRight size={12} />
                        </Link>
                      </div>
                    </div>
                  </article>
                );
              })}
            </div>
            <div className="mt-10 text-center">
              <Link href="/blog" className="btn-secondary inline-flex items-center gap-2 no-underline">
                View all articles
                <ArrowRight size={15} aria-hidden="true" />
              </Link>
            </div>
          </div>
        </section>
      )}

      {/* ════════════════════════════════
          CTA
         ════════════════════════════════ */}
      <section
        className="py-16 text-white"
        style={{ background: "linear-gradient(135deg, var(--color-primary) 0%, #7C5CBF 100%)" }}
        aria-labelledby="cta-heading"
      >
        <div className="container-main max-w-2xl text-center">
          <HeartPulse size={44} className="mx-auto mb-4 opacity-90" aria-hidden="true" />
          <h2 id="cta-heading" className="text-white text-2xl sm:text-3xl">
            {ctaTitle}
          </h2>
          <p className="mx-auto mt-3 max-w-md text-base opacity-90">
            {ctaSubtitle}
          </p>
          <ScrollToTopButton />
          <p className="mt-4 text-xs opacity-70">No sign-up · No data stored · 100% free</p>
        </div>
      </section>
    </>
  );
}
