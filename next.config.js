/** @type {import('next').NextConfig} */
const nextConfig = {
  images: {
    remotePatterns: [
      {
        protocol: "https",
        hostname: "*.ovuday.com",
      },
      {
        protocol: "https",
        hostname: "moccasin-dugong-732789.hostingersite.com",
      },
      {
        protocol: "https",
        hostname: "secure.gravatar.com",
      },
    ],
  },
  async headers() {
    return [
      {
        source: "/(.*)",
        headers: [
          { key: "X-Content-Type-Options",        value: "nosniff" },
          { key: "X-Frame-Options",               value: "DENY" },
          { key: "X-XSS-Protection",              value: "1; mode=block" },
          { key: "Referrer-Policy",               value: "strict-origin-when-cross-origin" },
          { key: "Permissions-Policy",            value: "camera=(), microphone=(), geolocation=(), interest-cohort=()" },
          // HSTS — forces HTTPS for 2 years, includes subdomains, eligible for preload list
          { key: "Strict-Transport-Security",     value: "max-age=63072000; includeSubDomains; preload" },
          // Prevent browsers from guessing MIME types
          { key: "Cross-Origin-Opener-Policy",    value: "same-origin" },
          { key: "Cross-Origin-Resource-Policy",  value: "same-origin" },
          { key: "Cross-Origin-Embedder-Policy",  value: "credentialless" },
        ],
      },
      {
        // Static assets — aggressively cached (content-hashed filenames)
        source: "/_next/static/(.*)",
        headers: [
          { key: "Cache-Control", value: "public, max-age=31536000, immutable" },
        ],
      },
      {
        // Fonts — long-lived cache
        source: "/fonts/(.*)",
        headers: [
          { key: "Cache-Control", value: "public, max-age=31536000, immutable" },
        ],
      },
      {
        // Images served from /public — use path-to-regexp named-param syntax (no regex look-around)
        source: "/:path*.:ext(png|jpg|jpeg|gif|webp|avif|svg|ico)",
        headers: [
          { key: "Cache-Control", value: "public, max-age=86400, stale-while-revalidate=604800" },
        ],
      },
    ];
  },
};

module.exports = nextConfig;
