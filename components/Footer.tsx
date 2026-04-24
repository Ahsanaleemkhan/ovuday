import Link from "next/link";
import { Flower, HeartPulse, ArrowRight } from "@/components/icons";
import { getSiteContent } from "@/lib/graphql";
import { parseSiteCopy, t } from "@/lib/siteCopy";

const fallbackFooterLinks = {
  Tools: [
    { href: "/",             label: "Ovulation Calculator" },
    { href: "/how-it-works", label: "How It Works"         },
    { href: "/faq",          label: "FAQ"                  },
  ],
  Learn: [
    { href: "/blog",  label: "Blog"         },
    { href: "/about", label: "About OvuDay" },
  ],
  Legal: [
    { href: "/privacy-policy", label: "Privacy Policy" },
    { href: "/terms",          label: "Terms of Use"   },
    { href: "/contact",        label: "Contact"        },
  ],
};

export default async function Footer() {
  const siteContent = await getSiteContent();
  const copy = parseSiteCopy(siteContent?.siteCopyJson);
  const footer = siteContent?.footer;

  const footerLinks = footer?.links?.length
    ? Object.fromEntries(
        footer.links.map((group) => [
          group.title || "Links",
          group.items.map((item) => ({ href: item.url, label: item.label })),
        ])
      )
    : fallbackFooterLinks;

  const year = new Date().getFullYear();
  const logoText = footer?.logoText || "OvuDay";
  const tagline =
    footer?.tagline ||
    t(copy, "footer.tagline", "Free, accurate ovulation calculator to help you understand your fertile window and plan your journey to parenthood.");
  const disclaimer =
    footer?.disclaimer ||
    t(copy, "footer.disclaimer", "Not medical advice. Consult your doctor.");
  const copyright = footer?.copyright || `© ${year} ${logoText}. All rights reserved.`;
  const madeWithCare = t(copy, "footer.madeWithCare", "Made with care for women on their fertility journey.");

  return (
    <footer
      className="mt-20 border-t"
      style={{ borderColor: "var(--color-border)", background: "var(--color-surface)" }}
      role="contentinfo"
    >
      <div className="container-main py-12">
        <div className="grid grid-cols-2 gap-8 sm:grid-cols-4">

          {/* Brand */}
          <div className="col-span-2 sm:col-span-1">
            <Link
              href="/"
              className="inline-flex items-center gap-2 font-heading text-lg font-bold no-underline"
              style={{ color: "var(--color-text)" }}
            >
              <span
                className="flex h-7 w-7 items-center justify-center rounded-md text-white"
                style={{ background: "var(--color-primary)" }}
                aria-hidden="true"
              >
                <Flower size={14} />
              </span>
              <span style={{ color: "var(--color-text)" }}>{logoText}</span>
            </Link>
            <p className="mt-3 text-sm leading-relaxed" style={{ color: "var(--color-muted)" }}>
              {tagline}
            </p>
            <p className="mt-4 flex items-center gap-1.5 text-xs" style={{ color: "var(--color-muted)" }}>
              <HeartPulse size={13} aria-hidden="true" />
              {disclaimer}
            </p>
          </div>

          {/* Link columns */}
          {Object.entries(footerLinks).map(([group, links]) => (
            <div key={group}>
              <h3 className="mb-4 text-sm font-semibold" style={{ color: "var(--color-text)" }}>
                {group}
              </h3>
              <ul className="space-y-2" role="list">
                {links.map(({ href, label }) => (
                  <li key={href}>
                    <Link
                      href={href}
                      className="flex items-center gap-1 text-sm no-underline transition-colors hover:underline"
                      style={{ color: "var(--color-muted)" }}
                    >
                      <ArrowRight size={11} aria-hidden="true" />
                      {label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>

        {/* Bottom bar */}
        <div
          className="mt-10 flex flex-col items-center justify-between gap-3 border-t pt-6 text-xs sm:flex-row"
          style={{ borderColor: "var(--color-border)", color: "var(--color-muted)" }}
        >
          <p>{copyright}</p>
          <p>{madeWithCare}</p>
        </div>
      </div>
    </footer>
  );
}
