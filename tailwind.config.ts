import type { Config } from "tailwindcss";

const config: Config = {
  content: [
    "./pages/**/*.{js,ts,jsx,tsx,mdx}",
    "./components/**/*.{js,ts,jsx,tsx,mdx}",
    "./app/**/*.{js,ts,jsx,tsx,mdx}",
  ],
  theme: {
    extend: {
      colors: {
        // Primary — Rose Pink (feminine, fertility, trust)
        primary: {
          50:  "#FFF0F5",
          100: "#FFD6E7",
          200: "#FFB3CD",
          300: "#FF8FB3",
          400: "#FF6B9A",
          500: "#E8476E", // main brand
          600: "#D63165",
          700: "#C2185B",
          800: "#A01050",
          900: "#7A0A3D",
        },
        // Secondary — Soft Purple (fertility, wellness)
        secondary: {
          50:  "#F5F0FF",
          100: "#EDE0FF",
          200: "#D4BFFF",
          300: "#B89AFF",
          400: "#9B77FF",
          500: "#7C5CBF",
          600: "#6A47AD",
          700: "#59349A",
          800: "#482481",
          900: "#35165E",
        },
        // Neutrals
        surface: "#FFF8FB",
        border:  "#F0D6E4",
        muted:   "#6B7280",
      },
      fontFamily: {
        // Google Fonts — loaded via next/font
        sans:    ["var(--font-inter)", "system-ui", "sans-serif"],
        heading: ["var(--font-jakarta)", "system-ui", "sans-serif"],
      },
      boxShadow: {
        card:  "0 1px 3px 0 rgb(0 0 0 / 0.06), 0 1px 2px -1px rgb(0 0 0 / 0.06)",
        panel: "0 4px 24px 0 rgb(232 71 110 / 0.08)",
        glow:  "0 0 32px 0 rgb(232 71 110 / 0.18)",
      },
      borderRadius: {
        xl2: "1.25rem",
        xl3: "1.5rem",
      },
      animation: {
        "fade-in":   "fadeIn 0.5s ease-out",
        "slide-up":  "slideUp 0.6s ease-out",
        "pulse-dot": "pulseDot 2s ease-in-out infinite",
      },
      keyframes: {
        fadeIn: {
          "0%":   { opacity: "0" },
          "100%": { opacity: "1" },
        },
        slideUp: {
          "0%":   { opacity: "0", transform: "translateY(20px)" },
          "100%": { opacity: "1", transform: "translateY(0)" },
        },
        pulseDot: {
          "0%, 100%": { transform: "scale(1)", opacity: "1" },
          "50%":      { transform: "scale(1.4)", opacity: "0.7" },
        },
      },
    },
  },
  plugins: [],
};

export default config;
