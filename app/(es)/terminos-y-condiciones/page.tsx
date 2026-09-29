import TermsPage from "@/components/TermsPage";
export const metadata = { title: "Términos y condiciones", description: "Condiciones de uso del sitio web de Royal Beans Perú.", alternates: { canonical: "/terminos-y-condiciones/", languages: { "es-PE": "/terminos-y-condiciones/", en: "/en/terms-and-conditions/", "x-default": "/terminos-y-condiciones/" } } };
export default function Page() { return <TermsPage lang="es" />; }
