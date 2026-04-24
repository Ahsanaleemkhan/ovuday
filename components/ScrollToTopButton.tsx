"use client";

import { Flower } from "@/components/icons";

export default function ScrollToTopButton() {
  return (
    <button
      onClick={() => window.scrollTo({ top: 0, behavior: "smooth" })}
      className="mt-8 inline-flex items-center gap-2 rounded-xl bg-white px-8 py-3.5 text-sm font-bold transition-all hover:scale-105 hover:shadow-lg border-0 cursor-pointer"
      style={{ color: "var(--color-primary)" }}
      aria-label="Scroll to top and use the calculator"
    >
      <Flower size={16} aria-hidden="true" />
      Calculate My Ovulation Now
    </button>
  );
}
