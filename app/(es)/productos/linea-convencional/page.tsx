import type { Metadata } from "next";
import HomePage from "@/components/HomePage";

export const metadata: Metadata = {
  title: { absolute: "Línea Convencional | Royal Beans Perú" },
  description: "Catálogo convencional de legumbres, granos, maíces, semillas y especias peruanas para operaciones comerciales.",
  alternates: { canonical: "/productos/linea-convencional/", languages: { "es-PE": "/productos/linea-convencional/", en: "/en/products/conventional-line/", "x-default": "/productos/linea-convencional/" } },
};

export default function ConventionalLinePage() {
  return <HomePage lang="es" page="productos" productLine="conventional" />;
}
