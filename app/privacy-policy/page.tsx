import type { Metadata } from "next";
import Link from "next/link";
import { getSiteContent } from "@/lib/graphql";

export const metadata: Metadata = {
  title: "Privacy Policy | OvuDay",
  description: "OvuDay's privacy policy. We take your privacy seriously — no personal health data is collected or stored.",
  alternates: { canonical: "https://ovuday.com/privacy-policy" },
  robots: { index: true, follow: false },
};

const fallbackSections = [
  { heading: "1. Overview", body: 'OvuDay ("we", "our", "us") is committed to protecting your privacy. This policy explains what information we collect, how we use it, and your rights regarding your data.' },
  { heading: "2. Information We Do NOT Collect", body: "OvuDay's ovulation calculator runs entirely in your browser. We do not collect, store, or transmit:\n• Last period dates or cycle data entered into the calculator\n• Health or medical information of any kind\n• Personal identifying information (name, email) unless voluntarily submitted via our contact form" },
  { heading: "3. Information We Collect Automatically", body: "Like most websites, we may collect basic analytics data:\n• Pages visited and time spent\n• Approximate geographic location (country/region level)\n• Device type and browser\n• Referring URL\n\nThis data is anonymised and used only to improve the website." },
  { heading: "4. Cookies", body: "We use essential cookies required for basic website functionality. We may also use analytics cookies and advertising cookies (for example, Google AdSense) to measure performance and fund the service." },
  { heading: "5. Contact Form", body: "If you contact us via our contact form, we receive your name, email, and message. This information is used solely to respond to your inquiry. Form submissions are processed through a third-party form delivery provider (FormSubmit)." },
  { heading: "6. Third-Party Services", body: "Our website may use third-party services such as analytics, ad serving, contact form processing, and font delivery providers." },
  { heading: "7. Children's Privacy", body: "OvuDay is not directed at children under 13. We do not knowingly collect information from children." },
  { heading: "8. Your Rights", body: "You have the right to request access to, correction of, or deletion of any personal data we hold about you. Contact us at our contact page to exercise these rights." },
  { heading: "9. Changes to This Policy", body: "We may update this Privacy Policy periodically. Changes are effective when posted on this page. Continued use of OvuDay after changes constitutes acceptance." },
  { heading: "10. Contact", body: "For privacy-related questions, contact us via our contact page." },
];

export default async function PrivacyPolicyPage() {
  const siteContent = await getSiteContent();
  const pp = siteContent?.privacyPage;

  const title    = pp?.title || "Privacy Policy";
  const sections = pp?.sections?.length ? pp.sections : fallbackSections;

  return (
    <div className="container-main max-w-3xl py-14">
      <nav aria-label="Breadcrumb" className="mb-8">
        <ol className="flex gap-2 text-sm" style={{ color: "var(--color-muted)" }}>
          <li><Link href="/" style={{ color: "var(--color-primary)" }}>Home</Link></li>
          <li aria-hidden="true">/</li>
          <li aria-current="page">Privacy Policy</li>
        </ol>
      </nav>

      <h1>{title}</h1>
      <p className="mt-2 text-sm" style={{ color: "var(--color-muted)" }}>
        Last updated: {new Date().toLocaleDateString("en-US", { year: "numeric", month: "long", day: "numeric" })}
      </p>

      <div className="divider" />

      <div className="prose-sm space-y-8" style={{ color: "#374151" }}>
        {sections.map(({ heading, body }) => (
          <section key={heading}>
            <h2>{heading}</h2>
            <div className="mt-2 text-sm leading-relaxed whitespace-pre-line">{body}</div>
          </section>
        ))}
      </div>
    </div>
  );
}
