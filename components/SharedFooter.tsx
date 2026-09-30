"use client";
import { Brand, FacebookIcon, InstagramIcon, YoutubeIcon, useContactChannels, type ContactChannel } from "./InteractiveShell";
import { ExternalLink } from "lucide-react";
import Link from "next/link";
import type { Lang } from "./routes";

export default function SharedFooter({ lang }: { lang: Lang }) {
  const defaultSocialChannels: ContactChannel[] = [
    { id: -1, channel_type: "facebook", label: "Facebook", value_text: "Royal Beans Perú", link_url: "https://www.facebook.com/profile.php?id=61573866174999" },
    { id: -2, channel_type: "instagram", label: "Instagram", value_text: "@royalbeans_peru", link_url: "https://www.instagram.com/royalbeans_peru/" },
    { id: -3, channel_type: "youtube", label: "YouTube", value_text: "Royal Beans Perú", link_url: "https://www.youtube.com/@Royalbeansperu" },
  ];
  const configuredSocialChannels = (useContactChannels(lang) ?? []).filter(channel => ["facebook", "instagram", "youtube", "linkedin", "tiktok", "other"].includes(channel.channel_type) && channel.link_url);
  const socialChannels = configuredSocialChannels.length ? configuredSocialChannels : defaultSocialChannels;
  return <footer className="site-footer shared-site-footer compact-footer footer-refined" data-cms-managed>
    <div className="container compact-footer-inner footer-layout">
      <div className="footer-main">
        <Link className="brand footer-brand" href={lang === "es" ? "/" : "/en/"} prefetch={false} aria-label="Royal Beans Perú"><Brand /></Link>
        <nav className="footer-social-links" aria-label={lang === "es" ? "Redes sociales" : "Social media"}>
          {socialChannels.map(channel => <a href={channel.link_url} target="_blank" rel="noreferrer" aria-label={channel.label} title={channel.label} key={channel.channel_type}>{channel.channel_type === "facebook" ? <FacebookIcon size={23} /> : channel.channel_type === "instagram" ? <InstagramIcon size={23} /> : channel.channel_type === "youtube" ? <YoutubeIcon size={25} /> : <ExternalLink size={22} />}</a>)}
        </nav>
      </div>
      <div className="footer-bottom-row">
        <span>© 2026 Royal Beans Perú | {lang === "es" ? "Todos los derechos reservados." : "All rights reserved."}</span>
        <span className="footer-credit">{lang === "es" ? "Diseñado y desarrollado por" : "Designed and developed by"} <a href="https://www.cybergcode.com" target="_blank" rel="noreferrer">CyberGCode</a></span>
        <nav className="footer-legal" aria-label={lang === "es" ? "Enlaces legales" : "Legal links"}><Link href={lang === "es" ? "/terminos-y-condiciones/" : "/en/terms-and-conditions/"} prefetch={false}>{lang === "es" ? "Términos y Condiciones" : "Terms and Conditions"}</Link><Link href={lang === "es" ? "/politica-de-privacidad/" : "/en/privacy-policy/"} prefetch={false}>{lang === "es" ? "Política de Privacidad" : "Privacy Policy"}</Link></nav>
      </div>
    </div>
  </footer>;
}
