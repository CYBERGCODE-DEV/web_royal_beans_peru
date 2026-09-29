import Link from "next/link";
import { ContentHydrator } from "./InteractiveShell";

export default function TermsPage({ lang }: { lang: "es" | "en" }) {
  const es = lang === "es";
  const sections = [
    ["terms-scope", es ? "Alcance" : "Scope", es ? "Estas condiciones regulan el uso del sitio público de Royal Beans Perú S.A.C. Al navegar o enviar una consulta, aceptas estas reglas." : "These terms govern use of the Royal Beans Perú S.A.C. public website. By browsing or submitting an enquiry, you accept these rules."],
    ["terms-information", es ? "Información y cotizaciones" : "Information and quotations", es ? "El contenido es informativo. No constituye una oferta ni confirma una venta. Solo una cotización o contrato emitido por Royal Beans Perú establece condiciones comerciales vinculantes." : "Website content is informational. It is not an offer and does not confirm a sale. Only a quotation or contract issued by Royal Beans Perú establishes binding commercial terms."],
    ["terms-products", es ? "Productos y operaciones" : "Products and transactions", es ? "Disponibilidad, calidad, especificaciones, certificaciones, precios, cantidades, entrega y pago se confirman para cada operación en su documentación comercial." : "Availability, quality, specifications, certifications, prices, quantities, delivery and payment are confirmed for each transaction in its commercial documents."],
    ["terms-use", es ? "Uso permitido" : "Permitted use", es ? "Puedes consultar el contenido y contactar a la empresa. Está prohibido interferir con el sitio, acceder a áreas restringidas, introducir código malicioso, suplantar identidades o usar la información con fines ilícitos." : "You may consult the content and contact the company. You must not disrupt the website, access restricted areas, introduce malicious code, impersonate others or use information for unlawful purposes."],
    ["terms-intellectual", es ? "Propiedad intelectual" : "Intellectual property", es ? "Marcas, fotografías, diseños, textos y archivos pertenecen a Royal Beans Perú o se usan con autorización. No está permitida su reproducción o explotación comercial sin autorización escrita." : "Trademarks, photographs, designs, text and files belong to Royal Beans Perú or are used with permission. Reproduction or commercial exploitation without written authorisation is not permitted."],
    ["terms-external", es ? "Servicios externos" : "External services", es ? "Los enlaces a WhatsApp, mapas, videos o redes sociales llevan a servicios de terceros. Cada proveedor es responsable de su servicio, disponibilidad y políticas." : "Links to WhatsApp, maps, videos or social networks lead to third-party services. Each provider is responsible for its service, availability and policies."],
    ["terms-liability", es ? "Disponibilidad y responsabilidad" : "Availability and liability", es ? "Procuramos mantener el sitio actualizado y disponible, pero puede contener errores o interrupciones. Royal Beans Perú no responde por el uso indebido del sitio ni por fallas de servicios externos, dentro de los límites legales." : "We seek to keep the website accurate and available, but it may contain errors or interruptions. Within legal limits, Royal Beans Perú is not responsible for misuse of the website or failures of external services."],
    ["terms-changes", es ? "Cambios y ley aplicable" : "Changes and applicable law", es ? "Podemos actualizar estas condiciones cuando cambie el sitio o la normativa. Se aplica la legislación peruana. Para consultas, escribe a administracion@royalbeansperu.com." : "We may update these terms when the website or applicable rules change. Peruvian law applies. For enquiries, contact administracion@royalbeansperu.com."],
  ] as const;
  return <><ContentHydrator page="terminos" lang={lang} /><main className="legal">
    <header className="legal-intro" data-cms-section="legal-intro">
      <Link data-cms="legal-intro.back" href={es ? "/" : "/en/"} prefetch={false}>← Royal Beans Perú</Link>
      <p className="legal-kicker" data-cms-managed>ROYAL BEANS PERÚ S.A.C.</p>
      <h1 data-cms="legal-intro.title">{es ? "Términos y condiciones" : "Terms and conditions"}</h1>
      <p data-cms="legal-intro.summary">{es ? "Reglas claras para usar el sitio y solicitar información comercial." : "Clear rules for using the website and requesting commercial information."}</p>
    </header>
    <div className="legal-sections">
      {sections.map(([key, title, body], index) => <section key={key} className="legal-section" data-cms-section={key}>
        <span data-cms-managed>{String(index + 1).padStart(2, "0")}</span>
        <div><h2 data-cms={`${key}.title`}>{title}</h2><p data-cms={`${key}.body`}>{body}</p></div>
      </section>)}
    </div>
  </main></>;
}
