import type { Metadata } from "next";
import Link from "next/link";
import JsonLd from "@/components/JsonLd";
import { getSiteContent } from "@/lib/graphql";
import { parseSiteCopy, t } from "@/lib/siteCopy";

export const metadata: Metadata = {
  title: "Ovulation & Fertility FAQ — Frequently Asked Questions | OvuDay",
  description:
    "Answers to the most common questions about ovulation, fertile window, menstrual cycles, and using OvuDay's calculator. Medically reviewed information.",
  alternates: { canonical: "https://ovuday.com/faq" },
};

const fallbackFaqItems = [
  {
    category: "The Calculator",
    items: [
      {
        q: "How accurate is OvuDay's ovulation calculator?",
        a: "OvuDay uses the calendar/rhythm method, which estimates ovulation based on your average cycle length. It's accurate for women with regular cycles. For irregular cycles, combine with BBT tracking or LH test strips for best results.",
      },
      {
        q: "What information do I need to use the calculator?",
        a: "You need: (1) the date your last period started, (2) your average cycle length (typically 21–35 days), and optionally your luteal phase length (typically 12–16 days).",
      },
      {
        q: "Is my data private?",
        a: "Yes. OvuDay performs all calculations directly in your browser. No dates or personal data are sent to or stored on our servers.",
      },
    ],
  },
  {
    category: "Ovulation Basics",
    items: [
      {
        q: "What is ovulation?",
        a: "Ovulation is when a mature egg is released from an ovary into the fallopian tube. It typically occurs once per cycle, about 14 days before your next period. The egg is viable for 12–24 hours after release.",
      },
      {
        q: "What is the fertile window?",
        a: "The fertile window is the 6-day period during which conception is possible — the 5 days before ovulation and ovulation day itself. Sperm can survive up to 5 days, so earlier intercourse can still result in pregnancy.",
      },
      {
        q: "Can I ovulate more than once in a cycle?",
        a: "It's very rare to ovulate twice in a single cycle. However, multiple eggs can sometimes be released within a 24-hour period (which can lead to fraternal twins). A second ovulation in a different week does not typically occur.",
      },
      {
        q: "What are signs of ovulation?",
        a: "Common signs include: changes in cervical mucus (becomes clear and stretchy, like egg whites), a slight rise in basal body temperature (BBT), mild pelvic pain or twinges (mittelschmerz), increased libido, and breast tenderness.",
      },
    ],
  },
  {
    category: "Menstrual Cycle",
    items: [
      {
        q: "What is a normal cycle length?",
        a: "A normal menstrual cycle ranges from 21 to 35 days, with 28 days being the average. Your cycle length is measured from the first day of one period to the first day of the next.",
      },
      {
        q: "What is the luteal phase?",
        a: "The luteal phase is the second half of your cycle, from ovulation to your next period. It's typically 12–16 days (average 14). A short luteal phase (under 10 days) may affect fertility.",
      },
      {
        q: "My cycles are irregular. How do I use the calculator?",
        a: "Calculate your average cycle length from the last 3–6 months. Add up the lengths and divide by the number of cycles. Use this average in the calculator. For highly irregular cycles, consider speaking with a healthcare provider.",
      },
    ],
  },
  {
    category: "Trying to Conceive",
    items: [
      {
        q: "When is the best time to have intercourse to get pregnant?",
        a: "The best time is the 1–2 days before ovulation and ovulation day itself. However, having intercourse every 1–2 days throughout your fertile window gives the highest chance of conception.",
      },
      {
        q: "How long does it typically take to conceive?",
        a: "For couples having regular intercourse without contraception: about 30% conceive in the first month, about 75% within 6 months, and about 90% within 12 months. See a fertility specialist if you haven't conceived after 12 months (or 6 months if over 35).",
      },
      {
        q: "Can stress affect ovulation?",
        a: "Yes. High levels of stress can disrupt the hormonal signals that trigger ovulation, potentially delaying or even suppressing it. Managing stress through exercise, sleep, and relaxation techniques can support regular cycles.",
      },
    ],
  },
];

export default async function FaqPage() {
  const siteContent = await getSiteContent();
  const copy = parseSiteCopy(siteContent?.siteCopyJson);

  const faqSections = siteContent?.faq?.length
    ? Object.entries(
        siteContent.faq.reduce((acc, item) => {
          const key = item.category || "General";
          if (!acc[key]) acc[key] = [];
          acc[key].push({ q: item.question, a: item.answer });
          return acc;
        }, {} as Record<string, { q: string; a: string }[]>)
      ).map(([category, items]) => ({
        category,
        items,
      }))
    : fallbackFaqItems;

  const jsonLdFaq = {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    mainEntity: faqSections.flatMap((cat) =>
      cat.items.map(({ q, a }) => ({
        "@type": "Question",
        name: q,
        acceptedAnswer: { "@type": "Answer", text: a.replace(/<[^>]+>/g, "") },
      }))
    ),
  };

  return (
    <>
      <JsonLd data={jsonLdFaq} />
      <div className="container-main max-w-3xl py-14">

        {/* Breadcrumb */}
        <nav aria-label="Breadcrumb" className="mb-8">
          <ol className="flex gap-2 text-sm" style={{ color: "var(--color-muted)" }}>
            <li><Link href="/" style={{ color: "var(--color-primary)" }}>Home</Link></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page">FAQ</li>
          </ol>
        </nav>

        <div className="badge mb-5 w-fit">FAQ</div>
        <h1>{siteContent?.faqSectionTitle || t(copy, "faq.title", "Frequently Asked Questions")}</h1>
        <p className="mt-4 text-lg" style={{ color: "#4B5563" }}>
          {siteContent?.faqSectionSubtitle || t(copy, "faq.subtitle", "Everything you need to know about ovulation, fertile windows, and using the OvuDay calculator.")}
        </p>

        <div className="divider" />

        {faqSections.map(({ category, items }) => (
          <section key={category} className="mb-12" aria-labelledby={`cat-${category}`}>
            <h2
              id={`cat-${category}`}
              className="mb-5 text-xl"
              style={{ color: "var(--color-primary)" }}
            >
              {category}
            </h2>
            <div className="space-y-4">
              {items.map(({ q, a }) => (
                <details key={q} className="card group cursor-pointer">
                  <summary className="flex items-center justify-between gap-4 text-base font-semibold list-none"
                           style={{ color: "var(--color-text)" }}>
                    {q}
                    <span
                      className="shrink-0 text-lg transition-transform group-open:rotate-45"
                      style={{ color: "var(--color-primary)" }}
                      aria-hidden="true"
                    >
                      +
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
          </section>
        ))}

        {/* Medical disclaimer */}
        <div
          className="rounded-xl border p-5 text-sm"
          style={{ borderColor: "var(--color-border)", background: "var(--color-surface)", color: "var(--color-muted)" }}
        >
          <strong>Medical Disclaimer:</strong> The information on OvuDay is for
          {" "}
          {t(
            copy,
            "faq.medicalDisclaimer",
            "educational purposes only and does not constitute medical advice. Always consult a qualified healthcare professional for fertility concerns."
          )}
        </div>

        <div className="mt-10 text-center">
          <Link href="/" className="btn-primary no-underline">
            Use the Ovulation Calculator →
          </Link>
        </div>
      </div>
    </>
  );
}
