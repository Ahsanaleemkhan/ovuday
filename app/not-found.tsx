import type { Metadata } from "next";
import Link from "next/link";

export const metadata: Metadata = {
  title: "Page Not Found | OvuDay",
  robots: { index: false },
};

export default function NotFound() {
  return (
    <div className="container-main max-w-lg py-24 text-center">
      <p className="text-7xl font-bold font-heading" style={{ color: "var(--color-primary)" }}>
        404
      </p>
      <h1 className="mt-4 text-2xl">Page Not Found</h1>
      <p className="mt-3 text-base" style={{ color: "var(--color-muted)" }}>
        The page you're looking for doesn't exist or has been moved.
      </p>
      <div className="mt-8 flex flex-wrap items-center justify-center gap-4">
        <Link href="/" className="btn-primary no-underline">Go to Calculator</Link>
        <Link href="/blog" className="btn-secondary no-underline">Read the Blog</Link>
      </div>
    </div>
  );
}
