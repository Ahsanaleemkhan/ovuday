"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useState } from "react";
import { Menu, X, Flower, ArrowRight } from "@/components/icons";
import { t } from "@/lib/siteCopy";

interface NavLink {
  label: string;
  url: string;
}

interface HeaderClientProps {
  navLinks: NavLink[];
  copy: any;
}

export default function HeaderClient({ navLinks, copy }: HeaderClientProps) {
  const pathname = usePathname();
  const [open, setOpen] = useState(false);

  return (
    <header
      className="sticky top-0 z-50 border-b bg-white/95 backdrop-blur-sm"
      style={{ borderColor: "var(--color-border)" }}
    >
      <div className="container-main flex h-16 items-center justify-between">

        {/* Logo */}
        <Link
          href="/"
          className="flex items-center gap-2 font-heading text-xl font-bold no-underline"
          style={{ color: "var(--color-text)" }}
          aria-label="OvuDay home"
        >
          <span
            className="flex h-8 w-8 items-center justify-center rounded-lg text-white"
            style={{ background: "var(--color-primary)" }}
            aria-hidden="true"
          >
            <Flower size={16} />
          </span>
          Ovu<span style={{ color: "var(--color-primary)" }}>Day</span>
        </Link>

        {/* Desktop nav */}
        <nav aria-label="Main navigation" className="hidden md:flex">
          <ul className="flex items-center gap-1" role="list">
            {navLinks.map(({ url, label }) => {
              const active = pathname === url;
              return (
                <li key={url}>
                  <Link
                    href={url}
                    className={`rounded-lg px-4 py-2 text-sm font-medium no-underline transition-colors ${
                      active ? "text-white" : "text-gray-600 hover:bg-pink-50 hover:text-pink-600"
                    }`}
                    style={active ? { background: "var(--color-primary)" } : {}}
                    aria-current={active ? "page" : undefined}
                  >
                    {label}
                  </Link>
                </li>
              );
            })}
          </ul>
        </nav>

        {/* CTA */}
        <div className="hidden md:flex items-center gap-3">
          <Link
            href="/"
            className="btn-primary flex items-center gap-1.5 text-sm px-5 py-2.5 no-underline"
          >
            {t(copy, "header.cta", "Calculate Now")}
            <ArrowRight size={14} aria-hidden="true" />
          </Link>
        </div>

        {/* Mobile hamburger */}
        <button
          className="md:hidden rounded-lg p-2 transition-colors hover:bg-pink-50"
          aria-label={open ? "Close menu" : "Open menu"}
          aria-expanded={open}
          aria-controls="mobile-menu"
          onClick={() => setOpen((v) => !v)}
        >
          {open
            ? <X size={22} style={{ color: "var(--color-text)" }} />
            : <Menu size={22} style={{ color: "var(--color-text)" }} />
          }
        </button>
      </div>

      {/* Mobile menu */}
      {open && (
        <nav
          id="mobile-menu"
          aria-label="Mobile navigation"
          className="border-t md:hidden"
          style={{ borderColor: "var(--color-border)", background: "var(--color-surface)" }}
        >
          <ul className="flex flex-col py-4" role="list">
            {navLinks.map(({ url, label }) => {
              const active = pathname === url;
              return (
                <li key={url}>
                  <Link
                    href={url}
                    className={`block px-6 py-3 text-sm font-medium no-underline transition-colors ${
                      active ? "text-white" : "text-gray-600 hover:bg-pink-50 hover:text-pink-600"
                    }`}
                    style={active ? { background: "var(--color-primary)" } : {}}
                    aria-current={active ? "page" : undefined}
                    onClick={() => setOpen(false)}
                  >
                    {label}
                  </Link>
                </li>
              );
            })}
            <li className="border-t mt-2 pt-2" style={{ borderColor: "var(--color-border)" }}>
              <Link
                href="/"
                className="btn-primary flex items-center justify-center gap-1.5 text-sm px-6 py-3 mx-6 no-underline"
                onClick={() => setOpen(false)}
              >
                {t(copy, "header.cta", "Calculate Now")}
                <ArrowRight size={14} aria-hidden="true" />
              </Link>
            </li>
          </ul>
        </nav>
      )}
    </header>
  );
}