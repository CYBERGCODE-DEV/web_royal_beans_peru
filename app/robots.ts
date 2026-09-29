import type { MetadataRoute } from "next";
import { SITE_INDEXABLE, SITE_URL } from "@/components/siteUrl";
export const dynamic = "force-static";

export default function robots(): MetadataRoute.Robots {
  if (!SITE_INDEXABLE) {
    return { rules: { userAgent: "*", disallow: "/" } };
  }
  return {
    rules: { userAgent: "*", allow: "/", disallow: ["/admin/", "/api/", "/cms-config/"] },
    sitemap: `${SITE_URL}/sitemap.xml`,
    host: SITE_URL,
  };
}
