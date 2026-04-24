import type { Metadata } from "next";
import Link from "next/link";
import JsonLd from "@/components/JsonLd";
import { getSiteContent } from "@/lib/graphql";

export const metadata: Metadata = {
  title: "About OvuDay — Our Mission to Support Fertility Journeys",
  description:
    "Learn about OvuDay, our mission to provide free, accurate, and private ovulation tracking tools for women on their fertility journey.",
  alternates: { canonical: "https://ovuday.com/about" },
};

const aboutSchema = {
  "@context": "https://schema.org",
  "@type": "AboutPage",
  name: "About OvuDay",
  description: "OvuDay's mission to support women with free, private fertility tools.",
  url: "https://ovuday.com/about",
  publisher: { "@type": "Organization", name: "OvuDay", url: "https://ovuday.com" },
};

const fallbackValues = [
  { icon: "🔒", title: "Privacy First",     description: "All calculations happen in your browser. We never store personal health data." },
  { icon: "✅", title: "Science-Based",     description: "Our calculator uses medically recognised methods reviewed against clinical guidelines." },
  { icon: "🌍", title: "Accessible to All", description: "OvuDay is free, works on any device, and requires no account or sign-up." },
  { icon: "💬", title: "Honest & Clear",    description: "We explain our calculations transparently and acknowledge limitations openly." },
];

export default async function AboutPage() {
  const siteContent = await getSiteContent();
  const ap = siteContent?.aboutPage;

  const badge            = ap?.badge            || "About Us";
  const title            = ap?.title            || "Our Mission";
  const intro            = ap?.intro            || "OvuDay was built with one goal: to give every woman a simple, private, and accurate way to understand her cycle — completely free, no strings attached.";
  const storyTitle       = ap?.storyTitle       || "Why We Built OvuDay";
  const storyP1          = ap?.storyP1          || "Fertility tracking should not require expensive apps, subscriptions, or giving away your most personal health data.";
  const storyP2          = ap?.storyP2          || "OvuDay exists to fix that. We built a fast, clean, browser-based calculator that respects your privacy completely — no accounts, no data collection, and transparent policies.";
  const valuesTitle      = ap?.valuesTitle      || "Our Values";
  const values           = ap?.values?.length   ? ap.values : fallbackValues;
  const disclaimer       = ap?.disclaimer       || "Our calculator is not a substitute for professional medical advice, diagnosis, or treatment. Consult your healthcare provider for fertility concerns.";
  const ctaPrimaryText   = ap?.ctaPrimaryText   || "Try the Calculator";
  const ctaPrimaryUrl    = ap?.ctaPrimaryUrl    || "/";
  const ctaSecondaryText = ap?.ctaSecondaryText || "Contact Us";
  const ctaSecondaryUrl  = ap?.ctaSecondaryUrl  || "/contact";

  return (
    <>
      <JsonLd data={aboutSchema} />
      <div className="container-main max-w-3xl py-14">

        <nav aria-label="Breadcrumb" className="mb-8">
          <ol className="flex gap-2 text-sm" style={{ color: "var(--color-muted)" }}>
            <li><Link href="/" style={{ color: "var(--color-primary)" }}>Home</Link></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page">About</li>
          </ol>
        </nav>

        <div className="badge mb-5 w-fit">{badge}</div>
        <h1>{title}</h1>
        <p className="mt-4 text-lg leading-relaxed" style={{ color: "#4B5563" }}>{intro}</p>

        <div className="divider" />

        <section aria-labelledby="story-heading">
          <h2 id="story-heading">{storyTitle}</h2>
          <div className="mt-4 space-y-4 text-base leading-relaxed" style={{ color: "#374151" }}>
            <p>{storyP1}</p>
            <p>{storyP2}</p>
          </div>
        </section>

        <div className="divider" />

        <section aria-labelledby="values-heading">
          <h2 id="values-heading">{valuesTitle}</h2>
          <div className="mt-6 grid gap-5 sm:grid-cols-2">
            {values.map(({ icon, title: vTitle, description }) => (
              <div key={vTitle} className="card flex gap-4">
                <span className="text-2xl shrink-0" aria-hidden="true">{icon}</span>
                <div>
                  <h3 className="text-base">{vTitle}</h3>
                  <p className="mt-1 text-sm" style={{ color: "var(--color-muted)" }}>{description}</p>
                </div>
              </div>
            ))}
          </div>
        </section>

        <div className="divider" />

        <div
          className="rounded-xl border p-5 text-sm"
          style={{ borderColor: "var(--color-border)", background: "var(--color-surface)", color: "var(--color-muted)" }}
        >
          <strong>Medical Disclaimer:</strong> OvuDay provides educational tools only.{" "}
          {disclaimer}
        </div>

        <div className="mt-10 flex flex-wrap gap-4">
          <Link href={ctaPrimaryUrl} className="btn-primary no-underline">{ctaPrimaryText}</Link>
          <Link href={ctaSecondaryUrl} className="btn-secondary no-underline">{ctaSecondaryText}</Link>
        </div>
      </div>
    </>
  );
}
