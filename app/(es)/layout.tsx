import SiteDocument from "@/components/SiteDocument";
import PublicChrome from "@/components/PublicChrome";
export { metadata } from "@/components/SiteDocument";
export default function Layout({children}: {children: React.ReactNode}) {return <SiteDocument lang="es"><PublicChrome lang="es">{children}</PublicChrome></SiteDocument>;}
