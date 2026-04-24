export default function Loading() {
  return (
    <div
      className="flex min-h-[60vh] items-center justify-center"
      role="status"
      aria-label="Loading"
    >
      <div className="flex flex-col items-center gap-4">
        {/* Spinner */}
        <div
          className="h-10 w-10 animate-spin rounded-full border-4 border-t-transparent"
          style={{ borderColor: "var(--color-border)", borderTopColor: "var(--color-primary)" }}
          aria-hidden="true"
        />
        <p className="text-sm font-medium" style={{ color: "var(--color-muted)" }}>
          Loading…
        </p>
      </div>
    </div>
  );
}
