import Link from "next/link";
import { ContentHydrator } from "./InteractiveShell";

export default function PrivacyPage({ lang }: { lang: "es" | "en" }) {
  const es = lang === "es";
  const sections = [
    ["privacy-controller", es ? "Responsable" : "Controller", es ? "Royal Beans Perú S.A.C. es responsable del tratamiento de los datos enviados por este sitio. Puedes contactarnos en administracion@royalbeansperu.com." : "Royal Beans Perú S.A.C. is responsible for personal data submitted through this website. Contact us at administracion@royalbeansperu.com."],
    ["privacy-data", es ? "Datos que recopilamos" : "Data we collect", es ? "Registramos los datos que completas en el formulario: tipo de participante, nombre, empresa, país, correo, teléfono, línea y productos de interés, y mensaje. También conservamos un registro técnico de seguridad asociado al envío." : "We record the details entered in the form: participant type, name, company, country, email, phone number, product line and products of interest, and message. We also keep a technical security record associated with the submission."],
    ["privacy-purpose", es ? "Para qué los usamos" : "How we use it", es ? "Usamos los datos para responder consultas, preparar cotizaciones, dar seguimiento comercial y prevenir envíos abusivos. No los usamos para publicidad sin una autorización adicional." : "We use the data to answer enquiries, prepare quotations, provide commercial follow-up and prevent abusive submissions. We do not use it for advertising without additional permission."],
    ["privacy-sharing", es ? "Acceso y terceros" : "Access and third parties", es ? "El acceso se limita al personal autorizado y a proveedores de infraestructura necesarios para operar el sitio. No vendemos datos. Si eliges WhatsApp u otro enlace externo, ese proveedor aplicará su propia política." : "Access is limited to authorised staff and infrastructure providers required to operate the website. We do not sell personal data. If you choose WhatsApp or another external link, that provider's own policy applies."],
    ["privacy-retention", es ? "Conservación" : "Retention", es ? "Conservamos la información mientras sea necesaria para atender la consulta y cumplir obligaciones comerciales o legales. Después se elimina o anonimiza de forma segura." : "We retain information while it is needed to handle the enquiry and meet commercial or legal obligations. It is then securely deleted or anonymised."],
    ["privacy-rights", es ? "Tus derechos" : "Your rights", es ? "Puedes solicitar acceso, rectificación, cancelación u oposición al tratamiento de tus datos. Envía tu solicitud e identificación a administracion@royalbeansperu.com. La atenderemos dentro de los plazos legales." : "You may request access, rectification, cancellation or object to the processing of your personal data. Send your request and identification to administracion@royalbeansperu.com. We will respond within the legal time limits."],
    ["privacy-cookies", es ? "Cookies y servicios externos" : "Cookies and external services", es ? "La web pública no usa cookies publicitarias. Puede guardar preferencias técnicas en tu navegador. Mapas, videos, WhatsApp y redes sociales pueden aplicar sus propias cookies o políticas cuando los utilizas." : "The public website does not use advertising cookies. It may store technical preferences in your browser. Maps, videos, WhatsApp and social networks may apply their own cookies or policies when you use them."],
  ] as const;
  return <><ContentHydrator page="privacidad" lang={lang} /><main className="legal">
    <header className="legal-intro" data-cms-section="legal-intro">
      <Link data-cms="legal-intro.back" href={es ? "/" : "/en/"} prefetch={false}>← Royal Beans Perú</Link>
      <p className="legal-kicker" data-cms-managed>ROYAL BEANS PERÚ S.A.C.</p>
      <h1 data-cms="legal-intro.title">{es ? "Política de privacidad" : "Privacy policy"}</h1>
      <p data-cms="legal-intro.summary">{es ? "Explicamos qué datos recibimos, para qué los usamos y cómo puedes ejercer tus derechos." : "This policy explains what data we receive, why we use it and how you can exercise your rights."}</p>
    </header>
    <div className="legal-sections">
      {sections.map(([key, title, body], index) => <section key={key} className="legal-section" data-cms-section={key}>
        <span data-cms-managed>{String(index + 1).padStart(2, "0")}</span>
        <div><h2 data-cms={`${key}.title`}>{title}</h2><p data-cms={`${key}.body`}>{body}</p></div>
      </section>)}
    </div>
  </main></>;
}
