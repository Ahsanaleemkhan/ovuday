import Link from "next/link";
import { Menu, X, Flower, ArrowRight } from "@/components/icons";
import { getSiteContent } from "@/lib/graphql";
import { parseSiteCopy, t } from "@/lib/siteCopy";
import HeaderClient from "./HeaderClient";

const fallbackNavLinks = [
  { label: "Calculator", url: "/" },
  { label: "How It Works", url: "/how-it-works" },
  { label: "Blog", url: "/blog" },
  { label: "FAQ", url: "/faq" },
  { label: "About", url: "/about" },
];

export default async function Header() {
  const siteContent = await getSiteContent();
  const copy = parseSiteCopy(siteContent?.siteCopyJson);
  const navLinks = siteContent?.navigation?.length
    ? siteContent.navigation
    : fallbackNavLinks;

  return <HeaderClient navLinks={navLinks} copy={copy} />;
}
