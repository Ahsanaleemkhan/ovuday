import type { Metadata, Viewport } from "next";
import { Inter, Plus_Jakarta_Sans } from "next/font/google";
import "./globals.css";
import Header from "@/components/Header";
import Footer from "@/components/Footer";
import JsonLd from "@/components/JsonLd";
import { getGlobalSeo } from "@/lib/graphql";

/* ─── Google Fonts ───────────────────────────────────────── */
const inter = Inter({
  subsets: ["latin"],
  variable: "--font-inter",
  display: "swap",
  weight: ["400", "500", "600"],
});

const jakarta = Plus_Jakarta_Sans({
  subsets: ["latin"],
  variable: "--font-jakarta",
  display: "swap",
  weight: ["600", "700", "800"],
});

/* ─── Root Metadata ──────────────────────────────────────── */
export const metadata: Metadata = {
  metadataBase: new URL(
    process.env.NEXT_PUBLIC_SITE_URL ?? "https://ovuday.com"
  ),
  title: {
    default: "OvuDay — Free Ovulation Calculator & Fertile Window Tracker",
    template: "%s | OvuDay",
  },
  description:
    "Calculate your ovulation date and fertile window instantly with OvuDay — the free, accurate ovulation calculator trusted by thousands of women trying to conceive.",
  keywords: [
    "ovulation calculator",
    "fertile window",
    "ovulation tracker",
    "when do I ovulate",
    "trying to conceive",
    "fertility calculator",
    "ovulation day",
    "cycle tracker",
    "period calculator",
    "TTC calculator",
  ],
  authors: [{ name: "OvuDay", url: "https://ovuday.com" }],
  creator: "OvuDay",
  publisher: "OvuDay",
  robots: {
    index: true,
    follow: true,
    googleBot: {
      index: true,
      follow: true,
      "max-image-preview": "large",
      "max-snippet": -1,
    },
  },
  openGraph: {
    type: "website",
    locale: "en_US",
    url: "https://ovuday.com",
    siteName: "OvuDay",
    title: "OvuDay — Free Ovulation Calculator & Fertile Window Tracker",
    description:
      "Calculate your ovulation date and fertile window instantly. Free, accurate and private.",
    images: [
      {
        url: "/opengraph-image",
        width: 1200,
        height: 630,
        alt: "OvuDay Ovulation Calculator",
      },
    ],
  },
  twitter: {
    card: "summary_large_image",
    title: "OvuDay — Free Ovulation Calculator",
    description:
      "Calculate your ovulation date and fertile window instantly. Free and private.",
    images: ["/twitter-image"],
  },
  alternates: {
    canonical: "https://ovuday.com",
  },
  verification: process.env.NEXT_PUBLIC_GOOGLE_SITE_VERIFICATION
    ? { google: process.env.NEXT_PUBLIC_GOOGLE_SITE_VERIFICATION }
    : undefined,
};

export const viewport: Viewport = {
  width: "device-width",
  initialScale: 1,
  themeColor: "#E8476E",
};

/* ─── Root Layout ────────────────────────────────────────── */
export default async function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  // Fetch social links from WordPress for Organization schema sameAs
  const globalSeo = await getGlobalSeo();

  const sameAs: string[] = [];
  if (globalSeo?.facebookUrl)  sameAs.push(globalSeo.facebookUrl);
  if (globalSeo?.instagramUrl) sameAs.push(globalSeo.instagramUrl);
  if (globalSeo?.twitterUsername) {
    const handle = globalSeo.twitterUsername.replace(/^@/, "");
    sameAs.push(`https://twitter.com/${handle}`);
  }

  const orgSchema = {
    "@context": "https://schema.org",
    "@type": "Organization",
    name: globalSeo?.organizationName || "OvuDay",
    url: "https://ovuday.com",
    logo: globalSeo?.organizationLogo || "https://ovuday.com/logo.svg",
    description:
      "Free ovulation calculator and fertile window tracker for women trying to conceive.",
    sameAs,
  };

  const websiteSchema = {
    "@context": "https://schema.org",
    "@type": "WebSite",
    name: "OvuDay",
    url: "https://ovuday.com",
    potentialAction: {
      "@type": "SearchAction",
      target: {
        "@type": "EntryPoint",
        urlTemplate: "https://ovuday.com/blog?q={search_term_string}",
      },
      "query-input": "required name=search_term_string",
    },
  };

  return (
    <html
      lang="en"
      className={`${inter.variable} ${jakarta.variable}`}
      suppressHydrationWarning
    >
      <head>
        <JsonLd data={orgSchema} />
        <JsonLd data={websiteSchema} />
      </head>
      <body className="flex min-h-screen flex-col">
        <Header />
        <main id="main-content" className="flex-1" role="main">
          {children}
        </main>
        <Footer />
      </body>
    </html>
  );
}
