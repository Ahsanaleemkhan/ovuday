"use client";

import Script from "next/script";
import { useEffect } from "react";

declare global {
  interface Window {
    adsbygoogle?: Array<Record<string, unknown>>;
  }
}

interface AdSlotProps {
  clientId?: string;
  slot?: string;
  enabled?: boolean;
  label?: string;
  className?: string;
}

export default function AdSlot({
  clientId,
  slot,
  enabled = false,
  label = "Advertisement",
  className = "",
}: AdSlotProps) {
  const canRenderAd = enabled && Boolean(clientId) && Boolean(slot);

  useEffect(() => {
    if (!canRenderAd) return;

    try {
      (window.adsbygoogle = window.adsbygoogle || []).push({});
    } catch {
      // Ignore duplicate push errors from route transitions.
    }
  }, [canRenderAd, slot]);

  if (!enabled) return null;

  if (!canRenderAd) {
    return (
      <aside
        className={`rounded-xl border border-dashed p-4 text-center ${className}`.trim()}
        aria-label={label}
        style={{ borderColor: "var(--color-border)", background: "var(--color-surface)" }}
      >
        <p className="text-xs font-semibold uppercase tracking-wider" style={{ color: "var(--color-muted)" }}>
          {label}
        </p>
        <p className="mt-1 text-xs" style={{ color: "var(--color-muted)" }}>
          Configure AdSense client and slot in the plugin to display ads here.
        </p>
      </aside>
    );
  }

  return (
    <aside className={className} aria-label={label}>
      <Script
        id="adsbygoogle-script"
        async
        strategy="afterInteractive"
        src={`https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=${clientId}`}
        crossOrigin="anonymous"
      />

      <p
        className="mb-2 text-center text-[11px] font-semibold uppercase tracking-wider"
        style={{ color: "var(--color-muted)" }}
      >
        {label}
      </p>

      <ins
        className="adsbygoogle"
        style={{ display: "block" }}
        data-ad-client={clientId}
        data-ad-slot={slot}
        data-ad-format="auto"
        data-full-width-responsive="true"
      />
    </aside>
  );
}
