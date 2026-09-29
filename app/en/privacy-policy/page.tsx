import PrivacyPage from "@/components/PrivacyPage";
export const metadata = { title: "Privacy and contact", description: "How contact and personal information work on the Royal Beans Perú website.", alternates: { canonical: "/en/privacy-policy/", languages: { "es-PE": "/politica-de-privacidad/", en: "/en/privacy-policy/", "x-default": "/politica-de-privacidad/" } } };
export default function Page() { return <PrivacyPage lang="en" />; }
