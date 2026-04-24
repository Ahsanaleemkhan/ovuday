"use client";

import { useEffect } from "react";
import Link from "next/link";

export default function Error({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  useEffect(() => {
    // Log to error reporting service in production
    console.error(error);
  }, [error]);

  return (
    <div className="container-main max-w-lg py-24 text-center">
      <p className="text-5xl" aria-hidden="true">⚠️</p>
      <h1 className="mt-4 text-2xl">Something went wrong</h1>
      <p className="mt-3 text-base" style={{ color: "var(--color-muted)" }}>
        An unexpected error occurred. This has been logged and we'll look into it.
      </p>
      <div className="mt-8 flex flex-wrap items-center justify-center gap-4">
        <button onClick={reset} className="btn-primary">
          Try Again
        </button>
        <Link href="/" className="btn-secondary no-underline">
          Go to Calculator
        </Link>
      </div>
    </div>
  );
}
