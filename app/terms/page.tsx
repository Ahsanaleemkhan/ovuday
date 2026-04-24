import type { Metadata } from "next";
import Link from "next/link";
import { getSiteContent } from "@/lib/graphql";

export const metadata: Metadata = {
  title: "Terms of Use | OvuDay",
  description: "Terms and conditions for using OvuDay's free ovulation calculator.",
  alternates: { canonical: "https://ovuday.com/terms" },
  robots: { index: true, follow: false },
};

const fallbackSections = [
  { heading: "1. Acceptance of Terms", body: 'By accessing and using OvuDay ("the Service"), you agree to be bound by these Terms of Use. If you do not agree, please discontinue use of the Service.' },
  { heading: "2. Not Medical Advice", body: "OvuDay provides educational information and estimation tools only. The ovulation calculator and all content on this website are not medical advice and should not be used as a substitute for professional medical guidance. Always consult a qualified healthcare professional for fertility, reproductive health, or contraception decisions." },
  { heading: "3. No Contraceptive Guarantee", body: "OvuDay is not a contraceptive method. The fertile window predictions are estimates only. We make no guarantee of the accuracy of ovulation predictions for the purpose of avoiding pregnancy." },
  { heading: "4. Use of the Service", body: "You agree not to:\n• Use the Service for any unlawful purpose\n• Attempt to reverse engineer or copy the Service\n• Use automated tools to scrape or crawl the Service without permission" },
  { heading: "5. Intellectual Property", body: "All content, design, and code on OvuDay is owned by OvuDay and protected by applicable copyright laws. You may not reproduce or distribute content without written permission." },
  { heading: "6. Disclaimer of Warranties", body: 'The Service is provided "as is" without warranties of any kind. OvuDay does not warrant that the Service will be uninterrupted, error-free, or completely accurate.' },
  { heading: "7. Limitation of Liability", body: "To the maximum extent permitted by law, OvuDay shall not be liable for any indirect, incidental, or consequential damages arising from use of the Service." },
  { heading: "8. Changes to Terms", body: "We reserve the right to modify these Terms at any time. Continued use of the Service after changes constitutes acceptance of the new Terms." },
  { heading: "9. Contact", body: "For questions about these Terms, contact us via our contact page." },
];

export default async function TermsPage() {
  const siteContent = await getSiteContent();
  const tp = siteContent?.termsPage;

  const title    = tp?.title || "Terms of Use";
  const sections = tp?.sections?.length ? tp.sections : fallbackSections;

  return (
    <div className="container-main max-w-3xl py-14">
      <nav aria-label="Breadcrumb" className="mb-8">
        <ol className="flex gap-2 text-sm" style={{ color: "var(--color-muted)" }}>
          <li><Link href="/" style={{ color: "var(--color-primary)" }}>Home</Link></li>
          <li aria-hidden="true">/</li>
          <li aria-current="page">Terms of Use</li>
        </ol>
      </nav>

      <h1>{title}</h1>
      <p className="mt-2 text-sm" style={{ color: "var(--color-muted)" }}>
        Last updated: {new Date().toLocaleDateString("en-US", { year: "numeric", month: "long", day: "numeric" })}
      </p>

      <div className="divider" />

      <div className="space-y-8 text-sm leading-relaxed" style={{ color: "#374151" }}>
        {sections.map(({ heading, body }) => (
          <section key={heading}>
            <h2>{heading}</h2>
            <div className="mt-2 whitespace-pre-line">{body}</div>
          </section>
        ))}
      </div>
    </div>
  );
}
