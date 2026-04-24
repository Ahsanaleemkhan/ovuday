import type { Metadata } from "next";
import Link from "next/link";
import JsonLd from "@/components/JsonLd";
import { getSiteContent } from "@/lib/graphql";

export const metadata: Metadata = {
  title: "How Ovulation Calculator Works — The Science Behind OvuDay",
  description:
    "Learn how OvuDay's ovulation calculator works. Understand the menstrual cycle, luteal phase, fertile window, and how to use the calendar method to predict ovulation.",
  alternates: { canonical: "https://ovuday.com/how-it-works" },
};

const articleSchema = {
  "@context": "https://schema.org",
  "@type": "Article",
  headline: "How an Ovulation Calculator Works",
  description: "A guide to understanding the menstrual cycle, ovulation, and fertile window calculation.",
  url: "https://ovuday.com/how-it-works",
  publisher: { "@type": "Organization", name: "OvuDay", url: "https://ovuday.com" },
  mainEntityOfPage: { "@type": "WebPage", "@id": "https://ovuday.com/how-it-works" },
};

const fallbackPhases = [
  { name: "Menstrual Phase",  days: "Day 1–5",     description: "Your period starts. The uterine lining sheds. Hormone levels (estrogen and progesterone) are at their lowest.",                                           color: "#FEE2E2", textColor: "#991B1B" },
  { name: "Follicular Phase", days: "Day 1–13",     description: "The pituitary gland releases FSH (Follicle Stimulating Hormone), triggering several follicles to develop. Estrogen rises.",                                color: "#FEF9C3", textColor: "#854D0E" },
  { name: "Ovulation",        days: "Day 14 (avg)", description: "A surge of LH (Luteinizing Hormone) triggers the dominant follicle to release an egg. This egg survives 12–24 hours. This is your peak fertility.",        color: "#FCE4EC", textColor: "#9D174D" },
  { name: "Luteal Phase",     days: "Day 15–28",    description: "The follicle becomes the corpus luteum, producing progesterone to prepare the uterus. If no fertilization occurs, hormone levels drop, triggering your next period.", color: "#EDE9FE", textColor: "#5B21B6" },
];

const fallbackLimitations = [
  "Irregular cycles make predictions less precise — track your last 3–6 months to find your average.",
  "Stress, illness, and lifestyle changes can shift ovulation by several days.",
  "The calendar method is best paired with BBT (basal body temperature) tracking or LH surge test strips for highest accuracy.",
  "This tool is not a contraceptive method.",
];

export default async function HowItWorksPage() {
  const siteContent = await getSiteContent();
  const hp = siteContent?.howPage;

  const badge              = hp?.badge              || "The Science";
  const title              = hp?.title              || "How the Ovulation Calculator Works";
  const intro              = hp?.intro              || "OvuDay uses the calendar method — the most widely understood approach for estimating ovulation. Here's the science behind every calculation.";
  const phasesTitle        = hp?.phasesTitle        || "The 4 Phases of the Menstrual Cycle";
  const phasesSubtitle     = hp?.phasesSubtitle     || "A typical cycle is 21–35 days. Understanding each phase helps you interpret your calculator results.";
  const phases             = hp?.phases?.length      ? hp.phases : fallbackPhases;
  const formulaTitle       = hp?.formulaTitle       || "The Calculation Formula";
  const formulaExample     = hp?.formulaExample     || "If your last period was March 1st, cycle is 28 days, and luteal phase is 14 days — ovulation = March 1 + (28 − 14) = March 15. Fertile window: March 10–16.";
  const fertileTitle       = hp?.fertileTitle       || "Why the Fertile Window is 6 Days";
  const fertileExplanation = hp?.fertileExplanation || "Sperm can survive in the reproductive tract for up to 5 days. The egg, however, only survives 12–24 hours after ovulation. So intercourse in the 5 days before ovulation — plus ovulation day itself — gives the best chance of conception.";
  const limitationsTitle   = hp?.limitationsTitle   || "Limitations to Know";
  const limitations        = hp?.limitations?.length ? hp.limitations : fallbackLimitations;
  const ctaText            = hp?.ctaText            || "Ready to calculate your ovulation date?";
  const ctaBtnText         = hp?.ctaBtnText         || "Use the Free Calculator →";
  const ctaBtnUrl          = hp?.ctaBtnUrl          || "/";

  return (
    <>
      <JsonLd data={articleSchema} />
      <div className="container-main max-w-3xl py-14">

        <nav aria-label="Breadcrumb" className="mb-8">
          <ol className="flex gap-2 text-sm" style={{ color: "var(--color-muted)" }}>
            <li><Link href="/" style={{ color: "var(--color-primary)" }}>Home</Link></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page">How It Works</li>
          </ol>
        </nav>

        <div className="badge mb-5 w-fit">{badge}</div>
        <h1>{title}</h1>
        <p className="mt-4 text-lg leading-relaxed" style={{ color: "#4B5563" }}>{intro}</p>

        <div className="divider" />

        {/* Cycle Phases */}
        <section aria-labelledby="phases-heading">
          <h2 id="phases-heading">{phasesTitle}</h2>
          <p className="mt-2 mb-8" style={{ color: "#4B5563" }}>{phasesSubtitle}</p>
          <div className="grid gap-4 sm:grid-cols-2">
            {phases.map(({ name, days, color, textColor, description }) => (
              <div key={name} className="rounded-xl p-5" style={{ background: color }}>
                <span className="text-xs font-bold uppercase tracking-wide" style={{ color: textColor }}>{days}</span>
                <h3 className="mt-1 text-base" style={{ color: textColor }}>{name}</h3>
                <p className="mt-2 text-sm leading-relaxed" style={{ color: textColor + "CC" }}>{description}</p>
              </div>
            ))}
          </div>
        </section>

        <div className="divider" />

        {/* The Formula */}
        <section aria-labelledby="formula-heading">
          <h2 id="formula-heading">{formulaTitle}</h2>
          <div className="mt-5 rounded-xl border p-6" style={{ borderColor: "var(--color-border)", background: "var(--color-surface)" }}>
            <div className="space-y-3 text-sm font-mono">
              <p><strong>Ovulation Day</strong> = Last Period Date + (Cycle Length − Luteal Phase)</p>
              <p><strong>Fertile Window Start</strong> = Ovulation Day − 5 days</p>
              <p><strong>Fertile Window End</strong> = Ovulation Day + 1 day</p>
              <p><strong>Next Period</strong> = Last Period Date + Cycle Length</p>
            </div>
          </div>
          <p className="mt-4 text-sm" style={{ color: "#4B5563" }}>
            <strong>Example:</strong> {formulaExample}
          </p>
        </section>

        <div className="divider" />

        {/* Fertile Window Explanation */}
        <section aria-labelledby="fertile-heading">
          <h2 id="fertile-heading">{fertileTitle}</h2>
          <p className="mt-3" style={{ color: "#4B5563" }}>{fertileExplanation}</p>
          <div
            className="mt-5 grid grid-cols-6 gap-1 rounded-xl overflow-hidden text-center text-xs"
            aria-label="Fertile window visualization"
          >
            {["Day -5", "Day -4", "Day -3", "Day -2", "Day -1", "Ovulation"].map((d, i) => (
              <div
                key={d}
                className="py-3 font-semibold"
                style={{
                  background: i === 5 ? "var(--color-primary)" : "#FFB3CD",
                  color:      i === 5 ? "#fff" : "#7A0A3D",
                }}
              >
                {d}
              </div>
            ))}
          </div>
        </section>

        <div className="divider" />

        {/* Limitations */}
        <section aria-labelledby="limits-heading">
          <h2 id="limits-heading">{limitationsTitle}</h2>
          <ul className="mt-4 space-y-2" style={{ color: "#4B5563" }}>
            {limitations.map((item) => (
              <li key={item} className="flex gap-2 text-sm">
                <span style={{ color: "var(--color-primary)" }} aria-hidden="true">→</span>
                {item}
              </li>
            ))}
          </ul>
        </section>

        <div className="divider" />

        {/* CTA */}
        <div className="text-center">
          <p className="mb-4 text-base font-semibold" style={{ color: "var(--color-text)" }}>{ctaText}</p>
          <Link href={ctaBtnUrl} className="btn-primary no-underline">{ctaBtnText}</Link>
        </div>
      </div>
    </>
  );
}
