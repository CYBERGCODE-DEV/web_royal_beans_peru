import { Check, MapPin, ArrowUpRight, ArrowRight, Sprout, History, Handshake, Target, Telescope, Fingerprint, Warehouse, SlidersHorizontal, ShieldCheck, SearchCheck, Container, FileCheck2, Truck, PackageCheck, ScanLine, Ship, UsersRound, CalendarCheck, MessageCircle } from "lucide-react";
import Link from "next/link";
import { AboutVideo, AnimatedCounter, ContactChannels, ContactForm, ContentHydrator, EnglishAboutVideoSection, EventParticipationArchive, HomeAnnouncements, HomeProductCarousel, HorizontalRail, ImpactGallery, ProductCatalog, QualityStandards, UpcomingEvent, type CatalogProduct } from "./InteractiveShell";

import { pagePath, productLinePath, type Lang, type PageKey } from "./routes";
import { SITE_URL } from "./siteUrl";

const content = {
  es: {
    loading: "Preparando origen peruano",
    nav: [
      ["#inicio", "Inicio"], ["#nosotros", "Nosotros"], ["#productos", "Productos"],
      ["#participacion", "Participación"], ["#impacto", "Impacto"], ["#contacto", "Contáctanos"],
    ],
    heroEyebrow: "Origen peruano · Alcance global",
    heroTitle: "Granos, legumbres y especias del Perú para el mundo.",
    heroText: "Seleccionamos productos peruanos para mercados nacionales e internacionales.",
    heroCta: "Ver Línea a Granel",
    scroll: "Conocer Royal Beans",
    aboutKicker: "Nuestra esencia",
    aboutTitle: "Cerca del origen. Listos para el mundo.",
    aboutBody: "Royal Beans Perú nació en 2022 en Lambayeque. Acopiamos, procesamos y comercializamos productos agrícolas, trabajando junto a productores y acompañando cada etapa, desde el cultivo hasta la entrega.",
    aboutQuote: "Construimos relaciones comerciales claras, responsables y de largo plazo.",
    values: ["Trazabilidad de origen", "Selección cuidadosa", "Atención directa"],
    productsKicker: "Selección peruana",
    productsTitle: "Productos con identidad de origen",
    productsIntro: "Una oferta enfocada para distribuidores, mayoristas y compradores internacionales.",
    productCta: "Consultar disponibilidad",
    participationKicker: "Participación",
    participationTitle: "Donde nacen nuevas conexiones",
    participationText: "Presentamos nuestra oferta y fortalecemos vínculos con compradores, aliados y especialistas del sector alimentario.",
    eventLabel: "Encuentro comercial",
    origin: "Origen operativo",
    destinations: "Conexiones comerciales",
    destinationsList: ["Mercado nacional", "Comercio internacional", "Nuevas oportunidades"],
    impactKicker: "Impacto",
    impactTitle: "Crecer cuidando cada vínculo",
    impactText: "Acompañamos a los agricultores en campo, verificamos la materia prima y promovemos una productividad responsable. El resultado es una cadena más confiable para quienes producen y para quienes compran.",
    impactItems: [
      ["01", "Campo", "Seguimiento cercano desde la siembra y la cosecha."],
      ["02", "Calidad", "Control del producto para responder a requisitos comerciales."],
      ["03", "Relaciones", "Colaboración directa con productores y clientes."],
    ],
    contactKicker: "Hablemos",
    contactTitle: "Tu próxima oportunidad puede comenzar aquí.",
    contactText: "Cuéntanos qué producto, volumen y destino necesitas. Nuestro equipo te responderá de forma directa.",
    addressLabel: "Ubicación",
    address: "Urb. La Parada Mza. Ñ Lote 13, Chiclayo, Lambayeque, Perú",
    emailLabel: "Correo comercial",
    phoneLabel: "Gerencia",
    footerText: "Productos peruanos para mercados que valoran el origen.",
    privacy: "Política de privacidad",
  },
  en: {
    loading: "Preparing Peruvian origin",
    nav: [
      ["#inicio", "Home"], ["#nosotros", "About"], ["#productos", "Products"],
      ["#participacion", "Events"], ["#impacto", "Impact"], ["#contacto", "Contact"],
    ],
    heroEyebrow: "Peruvian origin · Global reach",
    heroTitle: "Peruvian grains, pulses and spices for the world.",
    heroText: "We select Peruvian products for domestic and international markets.",
    heroCta: "Explore Bulk Line",
    scroll: "Meet Royal Beans",
    aboutKicker: "Our essence",
    aboutTitle: "Close to the source. Ready for the world.",
    aboutBody: "Royal Beans Perú was founded in 2022 in Lambayeque. We source, process and market agricultural products while working alongside growers from cultivation through delivery.",
    aboutQuote: "We build clear, responsible and long-term business relationships.",
    values: ["Origin traceability", "Careful selection", "Direct service"],
    productsKicker: "Peruvian selection",
    productsTitle: "Products with a sense of place",
    productsIntro: "A focused portfolio for distributors, wholesalers and international buyers.",
    productCta: "Check availability",
    participationKicker: "Events",
    participationTitle: "Where new connections begin",
    participationText: "We present our portfolio and strengthen relationships with buyers, partners and food industry specialists.",
    eventLabel: "Trade meeting",
    origin: "Operating origin",
    destinations: "Business connections",
    destinationsList: ["Domestic market", "International trade", "New opportunities"],
    impactKicker: "Impact",
    impactTitle: "Growing by caring for every relationship",
    impactText: "We accompany growers in the field, inspect raw materials and encourage responsible productivity. The result is a more reliable chain for producers and buyers.",
    impactItems: [
      ["01", "Field", "Close follow-up from planting to harvest."],
      ["02", "Quality", "Product control aligned with commercial requirements."],
      ["03", "Relationships", "Direct collaboration with growers and clients."],
    ],
    contactKicker: "Let's talk",
    contactTitle: "Your next opportunity can start here.",
    contactText: "Tell us the product, volume and destination you need. Our team will reply directly.",
    addressLabel: "Location",
    address: "Urb. La Parada Mza. Ñ Lote 13, Chiclayo, Lambayeque, Peru",
    emailLabel: "Business email",
    phoneLabel: "Management",
    footerText: "Peruvian products for markets that value origin.",
    privacy: "Privacy policy",
  },
};

type ProductLine = "conventional" | "retail";

export default function HomePage({ lang, page = "inicio", productLine, initialProducts = [] }: { lang: Lang; page?: PageKey; productLine?: ProductLine; initialProducts?: CatalogProduct[] }) {
  const t = content[lang];
  const organizationSchema = {
    "@context": "https://schema.org",
    "@type": "Organization",
    name: "Royal Beans Perú S.A.C.",
    url: lang === "es" ? `${SITE_URL}/` : `${SITE_URL}/en/`,
    logo: `${SITE_URL}/images/logo.webp`,
    foundingDate: "2022",
    address: { "@type": "PostalAddress", addressLocality: "Chiclayo", addressRegion: "Lambayeque", addressCountry: "PE" },
    email: "administracion@royalbeansperu.com",
    telephone: "+51 961 804 500",
    sameAs: ["https://www.instagram.com/royalbeans_peru/", "https://www.facebook.com/profile.php?id=61573866174999"],
  };
  const secondary = lang === "es" ? {
    products: { kicker: "Catálogo", title: "Productos peruanos para mercados exigentes.", text: "Legumbres, granos, maíces y especias seleccionados con atención directa para cada requerimiento comercial.", section: "Encuentra el producto para tu próxima operación.", note: "Consulta disponibilidad, presentación y volumen con nuestro equipo.", traits: [["Selección", "Materia prima revisada"], ["Trazabilidad", "Origen y lote identificados"], ["Despacho", "Atención según destino"]] },
    events: { kicker: "Participación", title: "Conexiones que abren nuevos mercados.", text: "Presentamos nuestra oferta peruana y construimos relaciones directas con compradores y aliados del sector alimentario.", section: "Royal Beans Perú en Expoalimentaria.", note: "Un espacio para mostrar productos, escuchar al mercado y crear oportunidades comerciales.", traits: [["Presentación", "Portafolio peruano"], ["Conexión", "Diálogo con compradores"], ["Seguimiento", "Relaciones de largo plazo"]] },
    impact: { kicker: "Impacto", title: "Crecemos cuidando cada vínculo.", text: "Acompañamos el trabajo del campo, revisamos la materia prima y promovemos relaciones responsables en toda la cadena.", section: "Valor compartido desde el origen.", note: "Una cadena confiable comienza con presencia, criterios claros y comunicación directa.", traits: [["Campo", "Acompañamiento cercano"], ["Calidad", "Materia prima revisada"], ["Relaciones", "Compromiso continuo"]] },
    contact: { kicker: "Contáctanos", title: "Conversemos sobre tu próxima operación.", text: "Indícanos producto, volumen y destino. Nuestro equipo atenderá tu consulta de forma directa.", section: "Estamos listos para escucharte.", note: "Elige el canal que prefieras o completa el formulario para preparar tu consulta." },
    ctaKicker: "Hablemos de negocios", ctaTitle: "Construyamos una oportunidad juntos.", ctaButton: "Contáctanos",
  } : {
    products: { kicker: "Catalogue", title: "Peruvian products for demanding markets.", text: "Pulses, grains, corn and spices selected with direct service for each commercial requirement.", section: "Find the product for your next operation.", note: "Ask our team about availability, presentation and volume.", traits: [["Selection", "Inspected raw material"], ["Traceability", "Identified origin and lot"], ["Dispatch", "Service for each destination"]] },
    events: { kicker: "Events", title: "Connections that open new markets.", text: "We present our Peruvian portfolio and build direct relationships with buyers and food industry partners.", section: "Royal Beans Perú at Expoalimentaria.", note: "A space to showcase products, understand the market and create business opportunities.", traits: [["Presentation", "Peruvian portfolio"], ["Connection", "Dialogue with buyers"], ["Follow-up", "Long-term relationships"]] },
    impact: { kicker: "Impact", title: "Growing by caring for every relationship.", text: "We support field work, inspect raw materials and encourage responsible relationships throughout the chain.", section: "Shared value from the source.", note: "A reliable chain begins with presence, clear criteria and direct communication.", traits: [["Field", "Close support"], ["Quality", "Inspected raw material"], ["Relationships", "Ongoing commitment"]] },
    contact: { kicker: "Contact us", title: "Let’s discuss your next operation.", text: "Tell us the product, volume and destination. Our team will handle your enquiry directly.", section: "We are ready to listen.", note: "Choose your preferred channel or complete the form to prepare your enquiry." },
    ctaKicker: "Let's talk business", ctaTitle: "Let’s build an opportunity together.", ctaButton: "Contact us",
  };
  const productLines = lang === "es" ? {
    conventional: { kicker: "Línea a Granel", title: "Origen peruano para operaciones de volumen.", text: "Nuestro portafolio de exportación reúne legumbres, granos, maíces, semillas y especias con atención comercial directa.", section: "Encuentra el producto para tu próxima operación.", note: "Consulta disponibilidad, presentación y volumen con nuestro equipo." },
    retail: { kicker: "Línea Retail", title: "Productos listos para acercarse al consumidor.", text: "Desarrollamos propuestas para canales minoristas según producto, presentación y requerimiento comercial.", section: "Una línea flexible para cada punto de venta.", note: "Nuestro equipo coordina formatos y alternativas según las necesidades de tu marca o canal.", features: [["Presentación", "Formatos definidos según el canal"], ["Producto", "Selección de origen peruano"], ["Atención", "Desarrollo comercial directo"]], button: "Solicitar catálogo retail" },
  } : {
    conventional: { kicker: "Bulk Line", title: "Peruvian origin for volume operations.", text: "Our export portfolio includes pulses, grains, corn, seeds and spices with direct commercial service.", section: "Find the product for your next operation.", note: "Ask our team about availability, presentation and volume." },
    retail: { kicker: "Retail Line", title: "Products prepared to reach consumers.", text: "We develop proposals for retail channels according to product, presentation and commercial requirements.", section: "A flexible line for every point of sale.", note: "Our team coordinates formats and alternatives based on the needs of your brand or channel.", features: [["Presentation", "Formats defined for each channel"], ["Product", "Selection of Peruvian origin"], ["Service", "Direct commercial development"]], button: "Request retail catalogue" },
  };
  const conventionalPath = productLinePath("conventional", lang);
  const retailPath = productLinePath("retail", lang);
  const cmsPage = productLine ? `productos-${productLine}` : page;
  const schema = organizationSchema;
  const aboutPage = lang === "es" ? {
    heroTitle: "Del campo peruano al mundo.",
    heroText: "Seleccionamos productos agrícolas con trazabilidad, atención directa y una clara vocación exportadora.",
    introTitle: "Origen que inspira confianza.",
    introText: "Royal Beans Perú conecta agricultores, productos y compradores mediante una operación cercana y responsable.",
    pillars: [
      ["Nuestra Esencia", "Origen, cercanía y compromiso guían cada decisión."],
      ["Nuestra Historia", "Nacimos en Lambayeque en 2022 para llevar productos peruanos a nuevos mercados."],
      ["Nuestro Propósito", "Crear relaciones duraderas entre agricultores y compradores."],
    ],
    videoKicker: "Conócenos",
    videoTitle: "Así trabajamos. Así crecemos.",
    principlesTitle: "Principios que guían cada operación.",
    mission: "Comercializar productos agrícolas confiables, atendiendo cada requerimiento con calidad y responsabilidad.",
    vision: "Ser un aliado peruano reconocido por su origen, cumplimiento y capacidad exportadora.",
    values: ["Cercanía", "Trazabilidad", "Cumplimiento"],
    traceTitle: "Trazabilidad de productos.",
    traceText: "Siete etapas conectan el origen del producto con una entrega comercial ordenada.",
    trace: [
      ["Acopio", "Recepción de origen"], ["Proceso", "Limpieza y selección"], ["Control de Calidad", "Parámetros verificados"],
      ["Inspección", "Revisión del lote"], ["Carga", "Acondicionamiento seguro"], ["Documentación", "Expediente completo"], ["Despacho", "Salida coordinada"],
    ],
    standardsTitle: "Nuestros estándares de calidad.",
    standardsText: "Referencias y controles aplicados a nuestra operación comercial.",
    standards: [
      ["FDA", "Registro internacional"], ["SENASA", "Control sanitario"], ["HACCP INTERNO", "Control preventivo"],
    ],
    ctaKicker: "Hablemos de negocios",
    ctaTitle: "Conversemos sobre su próxima operación.",
    ctaButton: "Contáctanos",
  } : {
    heroTitle: "From Peruvian fields to the world.",
    heroText: "We select agricultural products with traceability, direct service and a clear export focus.",
    introTitle: "Origin that inspires trust.",
    introText: "Royal Beans Perú connects growers, products and buyers through a close and responsible operation.",
    pillars: [
      ["Our Essence", "Origin, proximity and commitment guide every decision."],
      ["Our Story", "We began in Lambayeque in 2022 to take Peruvian products into new markets."],
      ["Our Purpose", "To create lasting relationships between growers and buyers."],
    ],
    videoKicker: "Meet us",
    videoTitle: "How we work. How we grow.",
    principlesTitle: "Principles guiding every operation.",
    mission: "To market reliable agricultural products while meeting every requirement with quality and responsibility.",
    vision: "To be a Peruvian partner recognized for origin, reliability and export capability.",
    values: ["Proximity", "Traceability", "Reliability"],
    traceTitle: "Product traceability.",
    traceText: "Seven stages connect product origin with an orderly commercial delivery.",
    trace: [
      ["Sourcing", "Origin reception"], ["Processing", "Cleaning and selection"], ["Quality Control", "Verified parameters"],
      ["Inspection", "Lot review"], ["Loading", "Safe preparation"], ["Documentation", "Complete records"], ["Dispatch", "Coordinated departure"],
    ],
    standardsTitle: "Our quality standards.",
    standardsText: "References and controls applied to our commercial operation.",
    standards: [
      ["FDA", "International registration"], ["SENASA", "Sanitary control"], ["Internal HACCP", "Preventive control"],
    ],
    ctaKicker: "Let's talk business",
    ctaTitle: "Let’s discuss your next operation.",
    ctaButton: "Contact us",
  };

  return (
    <>
      <a className="skip-link" href="#contenido">{lang === "es" ? "Ir al contenido" : "Skip to content"}</a>
      <ContentHydrator page={cmsPage} lang={lang} />
      {page === "inicio" && <HomeAnnouncements lang={lang} />}
      <main id="contenido" className={page === "inicio" ? "home-page" : page === "nosotros" ? "inner-page about-page" : `inner-page content-page ${page}-page`}>
        {page === "inicio" && (<section id="inicio" className="hero" data-hero aria-labelledby="hero-title">
          <picture className="hero-picture" aria-hidden="true">
            <source media="(max-width: 599px)" type="image/avif" srcSet="/images/hero-mobile.avif" />
            <source media="(max-width: 599px)" type="image/webp" srcSet="/images/hero-mobile.webp" />
            <source media="(max-width: 1279px)" type="image/avif" srcSet="/images/hero-tablet.avif" />
            <source media="(max-width: 1279px)" type="image/webp" srcSet="/images/hero-tablet.webp" />
            <source type="image/avif" srcSet="/images/hero-desktop.avif" />
            <img className="hero-image" data-cms="hero.image" src="/images/hero-desktop.webp" alt="" fetchPriority="high" width="1920" height="1080" />
          </picture>
          <div className="hero-noise" aria-hidden="true" />
          <div className="hero-content container" data-hero-content>
            <p className="eyebrow eyebrow-light"><span />{t.heroEyebrow}</p>
            <h1 id="hero-title" className="hero-brand-claim" data-cms="hero.title">{t.heroTitle}</h1>
            <p className="hero-copy" data-cms="hero.description">{t.heroText}</p>
            <div className="hero-buttons"><a className="button button-lime" href={conventionalPath}>{t.heroCta}<ArrowUpRight size={18} /></a><Link className="button button-outline" href={pagePath("contacto", lang)} prefetch={false}>{lang === "es" ? "Hablemos de negocios" : "Let’s talk business"}<ArrowUpRight size={18} /></Link></div>
          </div>
          <div className="origin-strip"><div className="container">
            <article><span className="feature-icon"><img src="/icons/peru-map.svg" alt="" width="22" height="22" /></span><strong>{lang === "es" ? "Origen peruano" : "Peruvian origin"}</strong></article>
            <article><span className="feature-icon"><img src="/icons/selection.svg" alt="" width="22" height="22" /></span><strong>{lang === "es" ? "Selección cuidadosa" : "Carefully selected"}</strong></article>
            <article><span className="feature-icon"><img src="/icons/sailboat.svg" alt="" width="22" height="22" /></span><strong>{lang === "es" ? "Vocación exportadora" : "Export focused"}</strong></article>
            <article><span className="feature-icon"><img src="/icons/user-avatar.svg" alt="" width="22" height="22" /></span><strong>{lang === "es" ? "Cerca del agricultor" : "Close to growers"}</strong></article>
          </div></div>
        </section>)}

        {page === "inicio" && <>
          <section className="home-about section-pad" aria-labelledby="home-about-title">
            <div className="container home-about-grid">
              <figure className="home-about-media">
                <img data-cms="about.image" src="/images/about-field.webp" alt={lang === "es" ? "Campo agrícola peruano" : "Peruvian agricultural field"} loading="lazy" width="1400" height="800" />
              </figure>
              <div className="home-about-copy">
                <h2 id="home-about-title" data-cms="about.title">{lang === "es" ? "Calidad que nace en el campo" : "Quality rooted in the field"}</h2>
                <p data-cms="about.description">{lang === "es" ? "Seleccionamos, procesamos y comercializamos productos peruanos con altos estándares, conectando el esfuerzo de nuestros productores con el mundo." : "We select, process and market Peruvian products to high standards, connecting the work of our growers with the world."}</p>
              </div>
            </div>
          </section>
          <HomeProductCarousel lang={lang} initialProducts={initialProducts} />
          <section className="home-origin home-origin-compact" aria-labelledby="home-origin-title">
            <div className="container home-origin-content">
              <p className="eyebrow eyebrow-light"><span />{lang === "es" ? "Del origen al destino" : "From origin to destination"}</p>
              <h2 id="home-origin-title" data-cms="origin.title">{lang === "es" ? "Una cadena responsable, preparada para nuevos mercados." : "A responsible chain, ready for new markets."}</h2>
              <p data-cms="origin.description">{lang === "es" ? "Cuidamos cada etapa para conectar el campo peruano con el mundo." : "We care for every stage to connect Peruvian growers with the world."}</p>
            </div>
          </section>
        </>}

        {page === "nosotros" && <>
          <section id="nosotros" className="about-hero" data-hero aria-labelledby="about-hero-title">
            <img className="about-hero-background" data-cms="hero.image" src="/images/hero-desktop.webp" alt="" width="1920" height="1080" fetchPriority="high" />
            <div className="container about-hero-editorial">
              <div className="about-hero-content" data-hero-content>
                <h1 id="about-hero-title" data-cms="hero.title">{lang === "es" ? "Nuestra historia" : "Our story"}</h1>
                <span className="about-hero-line" aria-hidden="true" />
                <p data-cms="hero.description">{lang === "es" ? "Somos una agroexportadora peruana especializada en la producción y comercialización de productos agrícolas de calidad." : "We are a Peruvian agricultural exporter specializing in the production and marketing of quality agricultural products."}</p>
              </div>
            </div>
          </section>

          <section className="about-story section-pad" aria-labelledby="about-story-title">
            <div className="container">
              <h2 id="about-story-title" className="sr-only">{lang === "es" ? "Misión, visión y valores" : "Mission, vision and values"}</h2>
              <div className="about-direction-grid">
                <article><span className="about-direction-icon"><img src="/icons/mission-target.svg" alt="" width="48" height="48" /></span><div><h3>{lang === "es" ? "Misión" : "Mission"}</h3><p>{aboutPage.mission}</p></div></article>
                <article><span className="about-direction-icon"><img src="/icons/vision-eye.svg" alt="" width="48" height="48" /></span><div><h3>{lang === "es" ? "Visión" : "Vision"}</h3><p>{aboutPage.vision}</p></div></article>
              </div>
              <div className="about-values">
                <h2>{lang === "es" ? "Nuestros valores" : "Our values"}</h2>
                <span className="about-values-line" aria-hidden="true" />
                <div className="about-values-grid">
                  {(lang === "es" ? ["Calidad", "Transparencia", "Responsabilidad", "Compromiso"] : ["Quality", "Transparency", "Responsibility", "Commitment"]).map((value, index) => <article key={value}>
                    <span><img src={["/icons/quality-sprout.svg", "/icons/traceability-gear.svg", "/icons/responsibility-person.svg", "/icons/commitment-handshake.svg"][index]} alt="" width="48" height="48" /></span>
                    <h3>{value}</h3>
                  </article>)}
                </div>
              </div>
              {lang === "es" && <div className="about-video-grid">
                <div className="about-video-copy"><p className="eyebrow"><span />{aboutPage.videoKicker}</p><h3>{aboutPage.videoTitle}</h3><p>{lang === "es" ? "Conoce el trabajo, el equipo y el origen que forman parte de Royal Beans Perú." : "Discover the work, team and origin behind Royal Beans Perú."}</p></div>
                <div className="about-video-frame"><AboutVideo lang={lang} /></div>
              </div>}
              {lang === "en" && <EnglishAboutVideoSection kicker={aboutPage.videoKicker} title={aboutPage.videoTitle} description="Discover the work, team and origin behind Royal Beans Perú." />}
            </div>
          </section>

          <section className="about-trace section-pad" aria-labelledby="about-trace-title">
            <div className="container">
              <div className="about-section-heading"><div><p className="eyebrow"><span />{lang === "es" ? "Del campo al contenedor" : "From field to container"}</p><h2 id="about-trace-title">{aboutPage.traceTitle}</h2></div><p>{aboutPage.traceText}</p></div>
              <HorizontalRail label={lang === "es" ? "Carrusel de etapas de trazabilidad" : "Traceability stages carousel"} previousLabel={lang === "es" ? "Ver etapas anteriores" : "View previous stages"} nextLabel={lang === "es" ? "Ver etapas siguientes" : "View next stages"}>
                <ol className="trace-steps">{aboutPage.trace.map(([title, text], index) => {
                  const Icon = [Warehouse, SlidersHorizontal, ShieldCheck, SearchCheck, Container, FileCheck2, Truck][index];
                  return <li key={title}><span className="trace-icon"><Icon size={23} strokeWidth={1.6} /></span><div><h3>{title}</h3><p>{text}</p></div>{index < aboutPage.trace.length - 1 && <span className="trace-arrow" aria-hidden="true"><ArrowRight size={17} /></span>}</li>;
                })}</ol>
              </HorizontalRail>
            </div>
          </section>

          <section className="about-standards section-pad" aria-labelledby="about-standards-title">
            <div className="container">
              <header className="standards-heading">
                <h2 id="about-standards-title">{aboutPage.standardsTitle}</h2>
                <span aria-hidden="true" />
              </header>
              <ul className="standards-logos" aria-label={lang === "es" ? "Referencias y estándares de calidad" : "Quality references and standards"}>
                <QualityStandards lang={lang} titles={aboutPage.standards.map(item => item[0])} descriptions={aboutPage.standards.map(item => item[1])} />
              </ul>
              <p className="standards-description">{aboutPage.standardsText}</p>
            </div>
          </section>

          <section className="about-cta" aria-labelledby="about-cta-title"><div className="container"><div><p className="eyebrow eyebrow-light"><span />{aboutPage.ctaKicker}</p><h2 id="about-cta-title">{aboutPage.ctaTitle}</h2></div><Link className="button button-lime" href={pagePath("contacto", lang)} prefetch={false}>{aboutPage.ctaButton}<ArrowUpRight size={18} /></Link></div></section>
        </>}

        {page === "productos" && productLine === "conventional" && <>
          <section id="productos" className="product-line-detail-conventional" data-hero aria-labelledby="conventional-hero-title">
            <img className="conventional-hero-image" data-cms="hero.image" src="https://pub-9f6a575b45bb44898756ec5620ef77a6.r2.dev/royalbeans/by-hash/dd34642831d182d25ba8903d23f4cce7d5eb432142531d8e324c50c4c6be8aa9.webp" alt="" fetchPriority="high" />
            <div className="container conventional-hero-copy" data-hero-content>
              <p>{lang === "es" ? "Granos que conectan el mundo" : "Grains that connect the world"}</p>
              <h1 id="conventional-hero-title" data-cms="hero.title">{lang === "es" ? "Línea a Granel" : "Bulk Line"}</h1>
              <span data-cms="hero.description">{lang === "es" ? "Granos seleccionados para comercialización a mayor escala." : "Selected grains for large-scale commercialization."}</span>
            </div>
          </section>
          <section className="products products-catalog conventional-catalog" aria-label={lang === "es" ? "Catálogo de línea a granel" : "Bulk line catalogue"}><div className="container"><ProductCatalog lang={lang} initialProducts={initialProducts} /></div></section>
        </>}

        {page === "productos" && productLine === "retail" && <>
          <section id="productos" className="product-line-detail-conventional product-line-detail-retail-matched" data-hero aria-labelledby="retail-hero-title">
            <img className="conventional-hero-image" data-cms="hero.image" src="/images/expo-connection.webp" alt="" fetchPriority="high" />
            <div className="container conventional-hero-copy" data-hero-content>
              <p>{lang === "es" ? "Granos que conectan el mundo" : "Grains that connect the world"}</p>
              <h1 id="retail-hero-title" data-cms="hero.title">{lang === "es" ? "Línea retail" : "Retail line"}</h1>
              <span data-cms="hero.description">{lang === "es" ? "Presentaciones listas para acercarse al consumidor final." : "Presentations ready to reach the final consumer."}</span>
            </div>
          </section>
          <section className="products products-catalog conventional-catalog retail-conventional-catalog" aria-label={lang === "es" ? "Catálogo de línea retail" : "Retail line catalogue"}><div className="container"><ProductCatalog lang={lang} line="retail" initialProducts={initialProducts} /></div></section>
        </>}

        {page === "participacion" && <>
          <section id="participacion" className="events-hero" data-hero aria-labelledby="events-hero-title"><img data-cms="hero.image" src="https://pub-9f6a575b45bb44898756ec5620ef77a6.r2.dev/royalbeans/by-hash/47b54e82da3200f3ec96ea8b4d0c4c6f612f39004d9017a0bf73f0eefe4c7ef6.png" alt="" fetchPriority="high" /><div className="container"><div className="events-hero-copy" data-hero-content><p className="eyebrow eyebrow-light"><span />{secondary.events.kicker}</p><h1 id="events-hero-title" data-cms="hero.title">{lang === "es" ? "Nos vemos en los grandes encuentros." : "Meet us at the leading events."}</h1><p data-cms="hero.description">{lang === "es" ? "Compartimos lo mejor del frejol peruano con el mundo." : "We share the best of Peruvian beans with the world."}</p><span className="events-hero-signal">{lang === "es" ? "Conectando cultivos con oportunidades" : "Connecting crops with opportunities"}</span></div></div></section>
          <UpcomingEvent lang={lang} />
          <EventParticipationArchive lang={lang} />
        </>}

        {page === "impacto" && <>
          <section id="impacto" className="impact-opportunity-hero" data-hero aria-labelledby="impact-hero-title">
            <img data-cms="hero.image" src="/images/hero-tablet.webp" alt={lang === "es" ? "Trabajo y producción agrícola en el campo peruano" : "Agricultural work and production in Peruvian fields"} width="1280" height="853" fetchPriority="high" />
            <div className="container impact-opportunity-layout" data-hero-content><div className="impact-opportunity-copy"><h1 id="impact-hero-title" data-cms="hero.title">{lang === "es" ? <>Impacto que impulsa <em>oportunidades</em></> : <>Impact that creates <em>opportunities</em></>}</h1><p data-cms="hero.description">{lang === "es" ? "Generamos oportunidades para mujeres trabajadoras, madres de familia y mujeres que buscan crecer con dignidad y compromiso." : "We create opportunities for working women, mothers and women who seek to grow with dignity and commitment."}</p><strong>{lang === "es" ? "Inclusión que transforma." : "Inclusion that transforms."}</strong></div><p className="impact-hero-note">{lang === "es" ? <>Mujeres que<br />cultivan un<br />mejor mañana.</> : <>Women growing<br />a better<br />tomorrow.</>}</p></div>
          </section>
          <section className="impact-commitment section-pad" aria-labelledby="impact-commitment-title"><div className="container"><h2 id="impact-commitment-title">{lang === "es" ? "Nuestro compromiso" : "Our commitment"}</h2><div className="impact-commitment-grid">{(lang === "es" ? [["Inclusión laboral","Abrimos más oportunidades de trabajo para mujeres."],["Impulso a madres de familia","Acompañamos el desarrollo de mujeres que sostienen a sus hogares."],["Oportunidades para todas","Creemos en el talento, el esfuerzo y el crecimiento con equidad."]] : [["Workplace inclusion","We open more employment opportunities for women."],["Support for mothers","We support the growth of women who sustain their households."],["Opportunities for all","We believe in talent, effort and equitable growth."]]).map(([title,text],index) => { const Icon=[UsersRound,Handshake,Sprout][index]; return <article key={title}><span><Icon size={34} strokeWidth={1.55} /></span><h3>{title}</h3><p>{text}</p></article>; })}</div></div></section>
          <section className="impact-values" aria-labelledby="impact-values-title"><div className="container"><h2 id="impact-values-title">{lang === "es" ? "Lo que queremos representar" : "What we want to represent"}</h2><div className="impact-values-grid">{(lang === "es" ? [["Trabajo con propósito","Un empleo que genera bienestar."],["Desarrollo personal","Herramientas para crecer."],["Participación femenina","Mujeres que construyen un mejor mañana."]] : [["Purposeful work","Employment that creates wellbeing."],["Personal development","Tools for growth."],["Women’s participation","Women building a better tomorrow."]]).map(([title,text],index) => { const Icon=[Sprout,Target,UsersRound][index]; return <article key={title}><span><Icon size={29} strokeWidth={1.6} /></span><div><h3>{title}</h3><p>{text}</p></div></article>; })}</div></div></section>
          <section className="impact-testimonial section-pad" aria-labelledby="impact-testimonial-title"><div className="container"><h2 id="impact-testimonial-title">{lang === "es" ? "Historias que inspiran" : "Stories that inspire"}</h2><article><img src="/images/expo-connection.webp" alt={lang === "es" ? "Equipo de Royal Beans Perú creando nuevas oportunidades" : "Royal Beans Perú team creating new opportunities"} loading="lazy" /><blockquote><p>{lang === "es" ? "“Aquí encontramos una oportunidad para salir adelante y crecer con nuestro trabajo.”" : "“Here we found an opportunity to move forward and grow through our work.”"}</p><footer>{lang === "es" ? "Colaboradora" : "Team member"}</footer></blockquote></article></div></section>
          <ImpactGallery lang={lang} />
          <section className="impact-closing" aria-label={lang === "es" ? "Compromiso de impacto" : "Impact commitment"}><div><p>{lang === "es" ? <>Más oportunidades, más inclusión,<br />un mejor futuro.</> : <>More opportunities, more inclusion,<br />a better future.</>}</p><span aria-hidden="true"><i /><Sprout size={20} /><i /></span></div></section>
        </>}

        {page === "contacto" && <>
          <section id="contacto" className="subpage-hero subpage-hero-contact" data-hero aria-labelledby="contact-hero-title"><img className="contact-hero-background" data-cms="hero.image" src="/images/expo-connection.webp" alt="" width="1600" height="1067" fetchPriority="high" /><div className="container contact-hero-layout"><div className="subpage-hero-content" data-hero-content><p className="eyebrow eyebrow-light"><span />{secondary.contact.kicker}</p><h1 id="contact-hero-title" data-cms="hero.title">{secondary.contact.title}</h1><span className="contact-hero-line" aria-hidden="true" /><p data-cms="hero.description">{secondary.contact.text}</p></div></div></section>
          <section className="contact contact-page-body section-pad" aria-labelledby="contact-section-title"><div className="container"><div className="contact-grid"><div className="contact-copy"><div className="contact-column-heading"><p className="eyebrow eyebrow-light"><span />{lang === "es" ? "Información comercial" : "Business information"}</p><h2 id="contact-section-title">{lang === "es" ? "Canales directos" : "Direct channels"}</h2></div><ContactChannels lang={lang} /></div><ContactForm lang={lang} /></div></div></section>
        </>}
      </main>

      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }} />
    </>
  );
}
