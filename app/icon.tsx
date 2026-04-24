import { ImageResponse } from "next/og";

export const runtime = "edge";
export const size = {
  width: 192,
  height: 192,
};
export const contentType = "image/png";

export default function Icon() {
  return new ImageResponse(
    (
      <div
        style={{
          width: "100%",
          height: "100%",
          display: "flex",
          alignItems: "center",
          justifyContent: "center",
          borderRadius: "28px",
          background: "linear-gradient(135deg, #E8476E 0%, #7C5CBF 100%)",
          color: "white",
          fontSize: 84,
          fontWeight: 800,
        }}
      >
        O
      </div>
    ),
    size
  );
}
