import type { Metadata } from "next";
import Link from "next/link";
import { getSiteContent } from "@/lib/graphql";

export const metadata: Metadata = {
  title: "Contact OvuDay — Get in Touch",
  description: "Have a question, suggestion, or feedback? Contact the OvuDay team.",
  alternates: { canonical: "https://ovuday.com/contact" },
};

const fallbackSubjects = ["Calculator feedback", "Bug report", "Content suggestion", "Partnership inquiry", "Other"];

export default async function ContactPage({ searchParams }: { searchParams?: { sent?: string } }) {
  const sent = searchParams?.sent === "1";
  const siteContent = await getSiteContent();
  const cp = siteContent?.contactPage;

  const badge          = cp?.badge          || "Contact";
  const title          = cp?.title          || "Get in Touch";
  const intro          = cp?.intro          || "Have a question, suggestion, or spotted an issue? We'd love to hear from you.";
  const successMessage = cp?.successMessage || "Thanks for contacting us. Your message has been sent successfully.";
  const responseTime   = cp?.responseTime   || "We typically respond within 2 business days. For urgent fertility questions, please consult a healthcare professional.";
  const formEmail      = cp?.formEmail      || "ahsanaleemofficial@gmail.com";
  const formSubject    = cp?.formSubject    || "OvuDay Contact Form";
  const subjects       = cp?.subjects?.length ? cp.subjects : fallbackSubjects;

  return (
    <div className="container-main max-w-2xl py-14">

      <nav aria-label="Breadcrumb" className="mb-8">
        <ol className="flex gap-2 text-sm" style={{ color: "var(--color-muted)" }}>
          <li><Link href="/" style={{ color: "var(--color-primary)" }}>Home</Link></li>
          <li aria-hidden="true">/</li>
          <li aria-current="page">Contact</li>
        </ol>
      </nav>

        <div className="badge mb-5 w-fit">{badge}</div>
        <h1>{title}</h1>
        <p className="mt-4 text-base" style={{ color: "#4B5563" }}>{intro}</p>

        {sent && (
          <div
            className="mt-5 rounded-xl border p-4 text-sm"
            style={{ borderColor: "#86EFAC", background: "#F0FDF4", color: "#166534" }}
            role="status"
          >
            {successMessage}
          </div>
        )}

        <div className="divider" />

      <form
        action={`https://formsubmit.co/${formEmail}`}
        method="POST"
        className="space-y-5"
        aria-label="Contact form"
      >
        {/* Honeypot */}
        <input type="text" name="_honey" className="hidden" aria-hidden="true" />
        <input type="hidden" name="_captcha" value="false" />
        <input type="hidden" name="_subject" value={formSubject} />
        <input type="hidden" name="_next" value="https://ovuday.com/contact?sent=1" />

        <div>
          <label htmlFor="name" className="form-label">Your Name</label>
          <input id="name" name="name" type="text" className="form-input" required autoComplete="name" />
        </div>

        <div>
          <label htmlFor="email" className="form-label">Email Address</label>
          <input id="email" name="email" type="email" className="form-input" required autoComplete="email" />
        </div>

        <div>
          <label htmlFor="subject" className="form-label">Subject</label>
          <select id="subject" name="subject" className="form-input" required>
            <option value="">Select a subject…</option>
            {subjects.map((s) => (
              <option key={s}>{s}</option>
            ))}
          </select>
        </div>

        <div>
          <label htmlFor="message" className="form-label">Message</label>
          <textarea id="message" name="message" rows={5} className="form-input resize-none" required />
        </div>

        <button type="submit" className="btn-primary w-full sm:w-auto">Send Message</button>
      </form>

      <div className="divider" />

        <p className="text-sm" style={{ color: "var(--color-muted)" }}>{responseTime}</p>
    </div>
  );
}
