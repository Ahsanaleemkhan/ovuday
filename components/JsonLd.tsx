/**
 * Safe JSON-LD injector.
 * Escapes </script> sequences to prevent script-injection attacks
 * when WordPress content contains literal "</script>" strings.
 */
export default function JsonLd({ data }: { data: Record<string, unknown> }) {
  const safe = JSON.stringify(data)
    .replace(/</g, "\\u003c")   // escape < so </script> can never close the tag
    .replace(/>/g, "\\u003e")
    .replace(/&/g, "\\u0026");

  return (
    <script
      type="application/ld+json"
      dangerouslySetInnerHTML={{ __html: safe }}
    />
  );
}
