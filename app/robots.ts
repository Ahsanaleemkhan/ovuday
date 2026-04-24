import type { MetadataRoute } from "next";

export default function robots(): MetadataRoute.Robots {
  return {
    rules: [
      {
        userAgent: "*",
        allow: "/",
        disallow: [
          "/api/",
          "/contact/success",
          "/_next/",
          "/wp-admin/",
          "/wp-login.php",
        ],
      },
      {
        // Block AI training scrapers — protect original health content
        userAgent: [
          "GPTBot",
          "ChatGPT-User",
          "CCBot",
          "anthropic-ai",
          "Claude-Web",
          "claudebot",
          "Google-Extended",   // Gemini training
          "PerplexityBot",
          "Bytespider",
          "meta-externalagent",
          "Amazonbot",
          "omgili",
          "omgilibot",
          "ImagesiftBot",
          "cohere-ai",
        ],
        disallow: "/",
      },
    ],
    sitemap: "https://ovuday.com/sitemap.xml",
    host:    "https://ovuday.com",
  };
}
