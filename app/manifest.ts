import type { MetadataRoute } from "next";

export default function manifest(): MetadataRoute.Manifest {
  return {
    name:             "OvuDay — Ovulation Calculator",
    short_name:       "OvuDay",
    description:      "Free ovulation calculator and fertile window tracker.",
    start_url:        "/",
    display:          "standalone",
    background_color: "#ffffff",
    theme_color:      "#E8476E",
    orientation:      "portrait",
    icons: [
      { src: "/logo.svg", sizes: "any", type: "image/svg+xml", purpose: "any" },
      { src: "/logo.svg", sizes: "any", type: "image/svg+xml", purpose: "maskable" },
      { src: "/apple-icon", sizes: "180x180", type: "image/png", purpose: "any" },
    ],
    categories: ["health", "medical", "lifestyle"],
    lang:       "en",
  };
}
