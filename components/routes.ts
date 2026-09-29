import type { Metadata } from "next";
import { SITE_URL } from "./siteUrl";
export type Lang = "es" | "en";
export type ProductLine = "conventional" | "retail";
export function productLinePath(line: ProductLine, lang: Lang): string {
  if (lang === "en") return line === "retail" ? "/en/products/retail/" : "/en/products/bulk-line/";
  return line === "retail" ? "/productos/linea-retail/" : "/productos/linea-a-granel/";
}
export function productMenuLinks(lang: Lang) {
  return (["conventional", "retail"] as const).map(line => ({
    href: productLinePath(line, lang),
    label: line === "conventional" ? (lang === "es" ? "Línea a Granel" : "Bulk Line") : (lang === "es" ? "Línea Retail" : "Retail Line"),
  }));
}
export const pages = {
  inicio: { es: "", en: "", label: { es: "Inicio", en: "Home" }, description: { es: "Royal Beans Perú: granos, legumbres y especias de origen peruano para el comercio nacional e internacional.", en: "Royal Beans Perú: Peruvian grains, pulses and spices for domestic and international trade." } },
  nosotros: { es: "nosotros", en: "about-us", label: { es: "Nosotros", en: "About us" }, description: { es: "Conoce nuestra historia, propósito y compromiso con los agricultores y compradores desde el norte del Perú.", en: "Discover our story, purpose and commitment to growers and buyers from northern Peru." } },
  productos: { es: "productos", en: "products", label: { es: "Productos", en: "Products" }, description: { es: "Explora nuestro catálogo de legumbres, granos, semillas, maíces y especias. Consulta disponibilidad y requerimientos comerciales.", en: "Explore our catalogue of pulses, grains, seeds, corn and spices. Enquire about availability and business requirements." } },
  participacion: { es: "participacion", en: "events", label: { es: "Participación", en: "Events" }, description: { es: "Nuestra participación en Expoalimentaria 2024 y 2025: productos peruanos y nuevas conexiones comerciales.", en: "Our participation in Expoalimentaria 2024 and 2025: Peruvian products and new business connections." } },
  impacto: { es: "impacto", en: "impact", label: { es: "Impacto", en: "Impact" }, description: { es: "Oportunidades, inclusión laboral y desarrollo para las mujeres que forman parte de la cadena de Royal Beans Perú.", en: "Opportunities, workplace inclusion and development for women across the Royal Beans Perú value chain." } },
  contacto: { es: "contacto", en: "contact", label: { es: "Contáctanos", en: "Contact us" }, description: { es: "Contacta con Royal Beans Perú. Cuéntanos el producto, volumen y destino de tu próxima operación comercial.", en: "Contact Royal Beans Perú. Tell us the product, volume and destination for your next business enquiry." } },
} as const;
export type PageKey = keyof typeof pages;
export function pagePath(page: PageKey, lang: Lang): string { return `${lang === "en" ? "/en/" : "/"}${pages[page][lang] ? `${pages[page][lang]}/` : ""}`; }
export function pageFromSlug(slug: string, lang: Lang): PageKey | undefined { return (Object.keys(pages) as PageKey[]).find(key => pages[key][lang] === slug); }
export function pageMetadata(page: PageKey, lang: Lang): Metadata {
  const title = page === "inicio"
    ? (lang === "es" ? "Royal Beans Perú | Agroexportadora Peruana" : "Royal Beans Perú | Peruvian Agricultural Exporter")
    : `${pages[page].label[lang]} | Royal Beans Perú`;
  const description = page === "inicio"
    ? (lang === "es" ? "Royal Beans Perú comercializa y exporta legumbres, granos andinos y especias seleccionadas con trazabilidad y atención para mercados internacionales." : "Royal Beans Perú supplies selected Peruvian pulses, Andean grains and spices to international buyers with traceability and direct commercial service.")
    : pages[page].description[lang];
  return { title: { absolute: title }, description, alternates: { canonical: pagePath(page, lang), languages: { "es-PE": pagePath(page, "es"), en: pagePath(page, "en"), "x-default": pagePath(page, "es") } }, openGraph: { title, description, url: `${SITE_URL}${pagePath(page, lang)}`, siteName: "Royal Beans Perú", type: "website", locale: lang === "es" ? "es_PE" : "en_US" }, twitter: { card: "summary_large_image", title, description } };
}
