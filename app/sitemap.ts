import type { MetadataRoute } from "next";
import { pages, pagePath, type PageKey } from "@/components/routes";
import { SITE_URL } from "@/components/siteUrl";
export const dynamic = "force-static";
export default function sitemap(): MetadataRoute.Sitemap {
  return [
    ...(Object.keys(pages) as PageKey[]).filter(page => page !== "productos").flatMap(page => (["es", "en"] as const).map(lang => ({ url: `${SITE_URL}${pagePath(page, lang)}`, changeFrequency: "monthly" as const, priority: page === "inicio" ? 1 : 0.8, alternates: { languages: { "es-PE": `${SITE_URL}${pagePath(page, "es")}`, en: `${SITE_URL}${pagePath(page, "en")}` } } }))),
    ...[
      ["/productos/linea-a-granel/", "/en/products/bulk-line/"],
      ["/productos/linea-retail/", "/en/products/retail/"],
    ].flatMap(([es, en]) => [
      { url: `${SITE_URL}${es}`, changeFrequency: "monthly" as const, priority: 0.8, alternates: { languages: { "es-PE": `${SITE_URL}${es}`, en: `${SITE_URL}${en}` } } },
      { url: `${SITE_URL}${en}`, changeFrequency: "monthly" as const, priority: 0.8, alternates: { languages: { "es-PE": `${SITE_URL}${es}`, en: `${SITE_URL}${en}` } } },
    ]),
    { url: `${SITE_URL}/politica-de-privacidad/`, changeFrequency: "yearly", priority: 0.2 },
    { url: `${SITE_URL}/en/privacy-policy/`, changeFrequency: "yearly", priority: 0.2 },
    { url: `${SITE_URL}/terminos-y-condiciones/`, changeFrequency: "yearly", priority: 0.2, alternates: { languages: { "es-PE": `${SITE_URL}/terminos-y-condiciones/`, en: `${SITE_URL}/en/terms-and-conditions/` } } },
    { url: `${SITE_URL}/en/terms-and-conditions/`, changeFrequency: "yearly", priority: 0.2, alternates: { languages: { "es-PE": `${SITE_URL}/terminos-y-condiciones/`, en: `${SITE_URL}/en/terms-and-conditions/` } } },
  ];
}
