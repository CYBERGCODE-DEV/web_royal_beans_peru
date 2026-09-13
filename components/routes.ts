import type { Metadata } from "next";
export type Lang = "es" | "en";
export const pages = {
  inicio: { es: "", en: "", label: { es: "Inicio", en: "Home" }, description: { es: "Royal Beans Perú: granos, legumbres y especias de origen peruano para el comercio nacional e internacional.", en: "Royal Beans Perú: Peruvian grains, pulses and spices for domestic and international trade." } },
  nosotros: { es: "nosotros", en: "about-us", label: { es: "Nosotros", en: "About us" }, description: { es: "Conoce nuestra historia, propósito y compromiso con los agricultores y compradores desde el norte del Perú.", en: "Discover our story, purpose and commitment to growers and buyers from northern Peru." } },
  productos: { es: "productos", en: "products", label: { es: "Productos", en: "Products" }, description: { es: "Explora nuestro catálogo de legumbres, granos, semillas, maíces y especias. Consulta disponibilidad y requerimientos comerciales.", en: "Explore our catalogue of pulses, grains, seeds, corn and spices. Enquire about availability and business requirements." } },
  participacion: { es: "participacion", en: "events", label: { es: "Participación", en: "Events" }, description: { es: "Nuestra participación en Expoalimentaria 2024 y 2025: productos peruanos y nuevas conexiones comerciales.", en: "Our participation in Expoalimentaria 2024 and 2025: Peruvian products and new business connections." } },
  presencia: { es: "presencia", en: "presence", label: { es: "Presencia", en: "Presence" }, description: { es: "Desde Chiclayo y Lambayeque, conectamos la oferta agrícola peruana con compradores nacionales e internacionales.", en: "From Chiclayo and Lambayeque, we connect Peruvian agricultural products with domestic and international buyers." } },
  impacto: { es: "impacto", en: "impact", label: { es: "Impacto", en: "Impact" }, description: { es: "Acompañamiento en campo, selección de materia prima y relaciones responsables con agricultores y clientes.", en: "Field support, raw material selection and responsible relationships with growers and customers." } },
  contacto: { es: "contacto", en: "contact", label: { es: "Contáctanos", en: "Contact us" }, description: { es: "Contacta con Royal Beans Perú. Cuéntanos el producto, volumen y destino de tu próxima operación comercial.", en: "Contact Royal Beans Perú. Tell us the product, volume and destination for your next business enquiry." } },
} as const;
export type PageKey = keyof typeof pages;
export function pagePath(page: PageKey, lang: Lang): string { return `${lang === "en" ? "/en/" : "/"}${pages[page][lang] ? `${pages[page][lang]}/` : ""}`; }
export function pageFromSlug(slug: string, lang: Lang): PageKey | undefined { return (Object.keys(pages) as PageKey[]).find(key => pages[key][lang] === slug); }
export function pageMetadata(page: PageKey, lang: Lang): Metadata {
  const title = `${pages[page].label[lang]} | Royal Beans Perú`;
  const description = pages[page].description[lang];
  return { title: { absolute: title }, description, alternates: { canonical: pagePath(page, lang), languages: { "es-PE": pagePath(page, "es"), en: pagePath(page, "en"), "x-default": pagePath(page, "es") } }, openGraph: { title, description, url: `https://royalbeansperu.com${pagePath(page, lang)}`, siteName: "Royal Beans Perú", type: "website", locale: lang === "es" ? "es_PE" : "en_US" } };
}
