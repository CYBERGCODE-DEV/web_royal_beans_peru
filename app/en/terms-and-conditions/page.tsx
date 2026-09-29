import TermsPage from "@/components/TermsPage";
export const metadata = { title: "Terms and conditions", description: "Terms governing use of the Royal Beans Perú website.", alternates: { canonical: "/en/terms-and-conditions/", languages: { "es-PE": "/terminos-y-condiciones/", en: "/en/terms-and-conditions/", "x-default": "/terminos-y-condiciones/" } } };
export default function Page() { return <TermsPage lang="en" />; }
