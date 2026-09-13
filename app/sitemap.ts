import type { MetadataRoute } from "next";
import { pages, pagePath, type PageKey } from "@/components/routes";
export const dynamic = "force-static";
export default function sitemap(): MetadataRoute.Sitemap {
  return [
    ...(Object.keys(pages) as PageKey[]).flatMap(page => (["es", "en"] as const).map(lang => ({ url: `https://royalbeansperu.com${pagePath(page, lang)}`, changeFrequency: "monthly" as const, priority: page === "inicio" ? 1 : 0.8, alternates: { languages: { "es-PE": `https://royalbeansperu.com${pagePath(page, "es")}`, en: `https://royalbeansperu.com${pagePath(page, "en")}` } } }))),
    { url: "https://royalbeansperu.com/politica-de-privacidad/", changeFrequency: "yearly", priority: 0.2 },
    { url: "https://royalbeansperu.com/en/privacy-policy/", changeFrequency: "yearly", priority: 0.2 },
  ];
}
