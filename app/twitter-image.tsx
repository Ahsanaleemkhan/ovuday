import { ImageResponse } from "next/og";

export const runtime = "edge";
export const size = {
  width: 1200,
  height: 630,
};
export const contentType = "image/png";

export default function TwitterImage() {
  return new ImageResponse(
    (
      <div
        style={{
          width: "100%",
          height: "100%",
          display: "flex",
          flexDirection: "column",
          justifyContent: "center",
          padding: "56px",
          background:
            "linear-gradient(135deg, #fff0f5 0%, #fff8fb 55%, #f5f0ff 100%)",
          color: "#1A1A2E",
        }}
      >
        <div style={{ fontSize: 34, color: "#E8476E", fontWeight: 700 }}>OvuDay</div>
        <div style={{ marginTop: 16, fontSize: 66, lineHeight: 1.1, fontWeight: 800, maxWidth: 920 }}>
          Ovulation and Fertility Blog
        </div>
        <div style={{ marginTop: 18, fontSize: 32, color: "#4B5563", maxWidth: 980 }}>
          Practical guides, expert insights, and cycle tracking education.
        </div>
      </div>
    ),
    size
  );
}
