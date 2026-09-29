import SiteDocument from "@/components/SiteDocument";
import PublicChrome from "@/components/PublicChrome";
export { metadata } from "@/components/SiteDocument";
export default function Layout({children}: {children: React.ReactNode}) {return <SiteDocument lang="en"><PublicChrome lang="en">{children}</PublicChrome></SiteDocument>;}
