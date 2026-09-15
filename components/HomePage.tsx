import { BadgeCheck, Check, Globe2, Leaf, MapPin, ArrowUpRight, ArrowRight, Mail, Phone, Sprout, History, Handshake, Target, Telescope, Fingerprint, Warehouse, SlidersHorizontal, ShieldCheck, SearchCheck, Container, FileCheck2, Truck, PackageCheck, ScanLine, Ship, UsersRound, CalendarCheck, Network, Route, Radar, MessageCircle, Clock3 } from "lucide-react";
import { AboutVideo, AnimatedCounter, Brand, ContactForm, ContentHydrator, ConversionTracker, FacebookIcon, Header, HomeProductCarousel, HorizontalRail, InstagramIcon, ProductCatalog, WhatsappIcon } from "./InteractiveShell";
import products from "./products.json";

import { pages, pagePath, type Lang, type PageKey } from "./routes";

const content = {
  es: {
    loading: "Preparando origen peruano",
    nav: [
      ["#inicio", "Inicio"], ["#nosotros", "Nosotros"], ["#productos", "Productos"],
      ["#participacion", "Participación"], ["#presencia", "Presencia"], ["#impacto", "Impacto"], ["#contacto", "Contáctanos"],
    ],
    heroEyebrow: "Origen peruano · Alcance global",
    heroTitle: <>Calidad que nace en el campo y <em>cruza fronteras.</em></>,
    heroText: "Granos andinos, legumbres y especias seleccionados desde el norte del Perú para mercados que valoran trazabilidad, constancia y origen.",
    heroCta: "Descubrir productos",
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
    presenceKicker: "Presencia",
    presenceTitle: "Del norte peruano a nuevos destinos",
    presenceText: "Nuestra operación parte de Lambayeque y se proyecta hacia mercados internacionales con soluciones flexibles para cada requerimiento.",
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
      ["#participacion", "Events"], ["#presencia", "Presence"], ["#impacto", "Impact"], ["#contacto", "Contact"],
    ],
    heroEyebrow: "Peruvian origin · Global reach",
    heroTitle: <>Quality rooted in the field and <em>ready for the world.</em></>,
    heroText: "Andean grains, pulses and spices selected in northern Peru for markets that value traceability, consistency and provenance.",
    heroCta: "Explore products",
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
    presenceKicker: "Presence",
    presenceTitle: "From northern Peru to new destinations",
    presenceText: "Our operation begins in Lambayeque and reaches international markets with flexible solutions for each requirement.",
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

export default function HomePage({ lang, page = "inicio", productLine }: { lang: Lang; page?: PageKey; productLine?: ProductLine }) {
  const t = content[lang];
  const nav = (Object.keys(pages) as PageKey[]).map(key => ({ href: pagePath(key, lang), label: pages[key].label[lang] }));
  const organizationSchema = {
    "@context": "https://schema.org",
    "@type": "Organization",
    name: "Royal Beans Perú S.A.C.",
    url: lang === "es" ? "https://royalbeansperu.com/" : "https://royalbeansperu.com/en/",
    logo: "https://royalbeansperu.com/images/logo.webp",
    foundingDate: "2022",
    address: { "@type": "PostalAddress", addressLocality: "Chiclayo", addressRegion: "Lambayeque", addressCountry: "PE" },
    email: "administracion@royalbeansperu.com",
    telephone: "+51 961 804 500",
    sameAs: ["https://www.instagram.com/royalbeans_peru/", "https://www.facebook.com/profile.php?id=61573866174999"],
  };
  const secondary = lang === "es" ? {
    products: { kicker: "Catálogo", title: "Productos peruanos para mercados exigentes.", text: "Legumbres, granos, maíces y especias seleccionados con atención directa para cada requerimiento comercial.", section: "Encuentra el producto para tu próxima operación.", note: "Consulta disponibilidad, presentación y volumen con nuestro equipo.", traits: [["Selección", "Materia prima revisada"], ["Trazabilidad", "Origen y lote identificados"], ["Despacho", "Atención según destino"]] },
    events: { kicker: "Participación", title: "Conexiones que abren nuevos mercados.", text: "Presentamos nuestra oferta peruana y construimos relaciones directas con compradores y aliados del sector alimentario.", section: "Royal Beans Perú en Expoalimentaria.", note: "Un espacio para mostrar productos, escuchar al mercado y crear oportunidades comerciales.", traits: [["Presentación", "Portafolio peruano"], ["Conexión", "Diálogo con compradores"], ["Seguimiento", "Relaciones de largo plazo"]] },
    presence: { kicker: "Presencia", title: "Desde Lambayeque hacia nuevos destinos.", text: "Conectamos el origen agrícola peruano con oportunidades nacionales e internacionales desde nuestra operación en Chiclayo.", section: "Una operación cercana con alcance comercial.", note: "Coordinamos cada consulta desde el origen hasta el destino requerido.", traits: [["Origen", "Chiclayo · Lambayeque"], ["Cobertura", "Mercado nacional"], ["Proyección", "Comercio internacional"]] },
    impact: { kicker: "Impacto", title: "Crecemos cuidando cada vínculo.", text: "Acompañamos el trabajo del campo, revisamos la materia prima y promovemos relaciones responsables en toda la cadena.", section: "Valor compartido desde el origen.", note: "Una cadena confiable comienza con presencia, criterios claros y comunicación directa.", traits: [["Campo", "Acompañamiento cercano"], ["Calidad", "Materia prima revisada"], ["Relaciones", "Compromiso continuo"]] },
    contact: { kicker: "Contáctanos", title: "Conversemos sobre tu próxima operación.", text: "Indícanos producto, volumen y destino. Nuestro equipo atenderá tu consulta de forma directa.", section: "Estamos listos para escucharte.", note: "Elige el canal que prefieras o completa el formulario para preparar tu consulta." },
    ctaKicker: "Hablemos de negocios", ctaTitle: "Construyamos una oportunidad juntos.", ctaButton: "Contáctanos",
  } : {
    products: { kicker: "Catalogue", title: "Peruvian products for demanding markets.", text: "Pulses, grains, corn and spices selected with direct service for each commercial requirement.", section: "Find the product for your next operation.", note: "Ask our team about availability, presentation and volume.", traits: [["Selection", "Inspected raw material"], ["Traceability", "Identified origin and lot"], ["Dispatch", "Service for each destination"]] },
    events: { kicker: "Events", title: "Connections that open new markets.", text: "We present our Peruvian portfolio and build direct relationships with buyers and food industry partners.", section: "Royal Beans Perú at Expoalimentaria.", note: "A space to showcase products, understand the market and create business opportunities.", traits: [["Presentation", "Peruvian portfolio"], ["Connection", "Dialogue with buyers"], ["Follow-up", "Long-term relationships"]] },
    presence: { kicker: "Presence", title: "From Lambayeque to new destinations.", text: "We connect Peruvian agricultural origin with domestic and international opportunities from our Chiclayo operation.", section: "A close operation with commercial reach.", note: "We coordinate every enquiry from origin to the required destination.", traits: [["Origin", "Chiclayo · Lambayeque"], ["Coverage", "Domestic market"], ["Outlook", "International trade"]] },
    impact: { kicker: "Impact", title: "Growing by caring for every relationship.", text: "We support field work, inspect raw materials and encourage responsible relationships throughout the chain.", section: "Shared value from the source.", note: "A reliable chain begins with presence, clear criteria and direct communication.", traits: [["Field", "Close support"], ["Quality", "Inspected raw material"], ["Relationships", "Ongoing commitment"]] },
    contact: { kicker: "Contact us", title: "Let’s discuss your next operation.", text: "Tell us the product, volume and destination. Our team will handle your enquiry directly.", section: "We are ready to listen.", note: "Choose your preferred channel or complete the form to prepare your enquiry." },
    ctaKicker: "Let's talk business", ctaTitle: "Let’s build an opportunity together.", ctaButton: "Contact us",
  };
  const productLines = lang === "es" ? {
    overview: {
      kicker: "Nuestras líneas", title: "Una oferta para cada canal.", text: "Elige la línea que se ajusta a tu operación y conoce sus soluciones.", details: "Ver detalles",
      conventional: { title: "Línea Convencional", text: "Productos agrícolas seleccionados para exportadores, distribuidores y compradores mayoristas.", label: "Comercio a granel" },
      retail: { title: "Línea Retail", text: "Presentaciones pensadas para marcas, tiendas y canales de consumo final.", label: "Soluciones de consumo" },
    },
    conventional: { kicker: "Línea Convencional", title: "Origen peruano para operaciones de volumen.", text: "Nuestro portafolio de exportación reúne legumbres, granos, maíces, semillas y especias con atención comercial directa.", section: "Encuentra el producto para tu próxima operación.", note: "Consulta disponibilidad, presentación y volumen con nuestro equipo." },
    retail: { kicker: "Línea Retail", title: "Productos listos para acercarse al consumidor.", text: "Desarrollamos propuestas para canales minoristas según producto, presentación y requerimiento comercial.", section: "Una línea flexible para cada punto de venta.", note: "Nuestro equipo coordina formatos y alternativas según las necesidades de tu marca o canal.", features: [["Presentación", "Formatos definidos según el canal"], ["Producto", "Selección de origen peruano"], ["Atención", "Desarrollo comercial directo"]], button: "Solicitar catálogo retail" },
    back: "Volver a líneas de productos",
  } : {
    overview: {
      kicker: "Our lines", title: "A portfolio for every channel.", text: "Choose the line that fits your operation and explore its solutions.", details: "View details",
      conventional: { title: "Conventional Line", text: "Selected agricultural products for exporters, distributors and wholesale buyers.", label: "Bulk trade" },
      retail: { title: "Retail Line", text: "Presentations designed for brands, stores and consumer channels.", label: "Consumer solutions" },
    },
    conventional: { kicker: "Conventional Line", title: "Peruvian origin for volume operations.", text: "Our export portfolio includes pulses, grains, corn, seeds and spices with direct commercial service.", section: "Find the product for your next operation.", note: "Ask our team about availability, presentation and volume." },
    retail: { kicker: "Retail Line", title: "Products prepared to reach consumers.", text: "We develop proposals for retail channels according to product, presentation and commercial requirements.", section: "A flexible line for every point of sale.", note: "Our team coordinates formats and alternatives based on the needs of your brand or channel.", features: [["Presentation", "Formats defined for each channel"], ["Product", "Selection of Peruvian origin"], ["Service", "Direct commercial development"]], button: "Request retail catalogue" },
    back: "Back to product lines",
  };
  const productOverviewPath = pagePath("productos", lang);
  const conventionalPath = lang === "es" ? "/productos/linea-convencional/" : "/en/products/conventional-line/";
  const retailPath = lang === "es" ? "/productos/linea-retail/" : "/en/products/retail-line/";
  const cmsPage = productLine ? `productos-${productLine}` : page;
  const productListPath = productLine === "conventional" ? conventionalPath : pagePath("productos", lang);
  const schema = page === "inicio" || productLine === "conventional" ? {
    "@context": "https://schema.org",
    "@graph": [
      organizationSchema,
      {
        "@type": "ItemList",
        name: lang === "es" ? "Productos de Royal Beans Perú" : "Royal Beans Perú products",
        numberOfItems: products.length,
        itemListElement: products.map((product, index) => ({
          "@type": "ListItem",
          position: index + 1,
          item: {
            "@type": "Product",
            name: product[lang],
            image: `https://royalbeansperu.com${product.image}`,
            url: `https://royalbeansperu.com${productListPath}`,
            brand: { "@type": "Brand", name: "Royal Beans Perú" },
          },
        })),
      },
    ],
  } : organizationSchema;
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
    traceTitle: "Del campo al contenedor.",
    traceText: "Siete etapas conectan el origen del producto con una entrega comercial ordenada.",
    trace: [
      ["Acopio", "Recepción de origen"], ["Proceso", "Limpieza y selección"], ["Control de Calidad", "Parámetros verificados"],
      ["Inspección", "Revisión del lote"], ["Carga", "Acondicionamiento seguro"], ["Documentación", "Expediente completo"], ["Despacho", "Salida coordinada"],
    ],
    standardsTitle: "Calidad respaldada en cada etapa.",
    standardsText: "Referencias y controles aplicados a nuestra operación comercial.",
    standards: [
      ["FDA", "Registro para mercados internacionales"], ["SENASA", "Control sanitario nacional"], ["HACCP Interno", "Procedimientos preventivos"],
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
    traceTitle: "From field to container.",
    traceText: "Seven stages connect product origin with an orderly commercial delivery.",
    trace: [
      ["Sourcing", "Origin reception"], ["Processing", "Cleaning and selection"], ["Quality Control", "Verified parameters"],
      ["Inspection", "Lot review"], ["Loading", "Safe preparation"], ["Documentation", "Complete records"], ["Dispatch", "Coordinated departure"],
    ],
    standardsTitle: "Quality supported at every stage.",
    standardsText: "References and controls applied to our commercial operation.",
    standards: [
      ["FDA", "International market registration"], ["SENASA", "National sanitary control"], ["Internal HACCP", "Preventive procedures"],
    ],
    ctaKicker: "Let's talk business",
    ctaTitle: "Let’s discuss your next operation.",
    ctaButton: "Contact us",
  };

  return (
    <>
      <a className="skip-link" href="#contenido">{lang === "es" ? "Ir al contenido" : "Skip to content"}</a>
      <ConversionTracker />
      <ContentHydrator page={cmsPage} lang={lang} />
      <Header nav={nav} lang={lang} page={page} languagePaths={productLine ? { es: productLine === "conventional" ? "/productos/linea-convencional/" : "/productos/linea-retail/", en: productLine === "conventional" ? "/en/products/conventional-line/" : "/en/products/retail-line/" } : undefined} />
      <main id="contenido" className={page === "inicio" ? "home-page" : page === "nosotros" ? "inner-page about-page" : `inner-page content-page ${page}-page`}>
        {page === "inicio" && (<section id="inicio" className="hero" aria-labelledby="hero-title">
          <picture className="hero-picture" aria-hidden="true">
            <source media="(max-width: 599px)" type="image/avif" srcSet="/images/hero-mobile.avif" />
            <source media="(max-width: 599px)" type="image/webp" srcSet="/images/hero-mobile.webp" />
            <source media="(max-width: 1199px)" type="image/avif" srcSet="/images/hero-tablet.avif" />
            <source media="(max-width: 1199px)" type="image/webp" srcSet="/images/hero-tablet.webp" />
            <source type="image/avif" srcSet="/images/hero-desktop.avif" />
            <img className="hero-image" src="/images/hero-desktop.webp" alt="" fetchPriority="high" width="1920" height="1080" />
          </picture>
          <div className="hero-noise" aria-hidden="true" />
          <div className="hero-content container">
            <p className="eyebrow eyebrow-light"><span />{t.heroEyebrow}</p>
            <h1 id="hero-title" data-cms="hero.title">{t.heroTitle}</h1>
            <p className="hero-copy" data-cms="hero.description">{t.heroText}</p>
            <div className="hero-buttons"><a className="button button-lime" href={pagePath("productos", lang)}>{t.heroCta}<ArrowUpRight size={18} /></a><a className="button button-outline" href={pagePath("contacto", lang)}>{lang === "es" ? "Hablemos de negocios" : "Let’s talk business"}<ArrowUpRight size={18} /></a></div>
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
              <div className="home-about-copy">
                <p className="eyebrow"><span />{lang === "es" ? "¿Quiénes somos?" : "Who we are"}</p>
                <h2 id="home-about-title" data-cms="about.title">{lang === "es" ? "Calidad peruana, lista para el mundo." : "Peruvian quality, ready for the world."}</h2>
                <p data-cms="about.description">{lang === "es" ? "Royal Beans Perú conecta el trabajo del campo con compradores que valoran el origen, la constancia y una relación comercial directa." : "Royal Beans Perú connects the work of the field with buyers who value origin, consistency and direct business relationships."}</p>
                <ul>
                  {(lang === "es" ? ["Origen en Lambayeque", "Acopio y proceso especializado", "Cercanía con agricultores", "Selección con trazabilidad", "Atención nacional e internacional"] : ["Based in Lambayeque", "Specialized sourcing and processing", "Close to growers", "Selection with traceability", "Domestic and international service"]).map(item => <li key={item}><span><Check size={16} strokeWidth={2.5} /></span>{item}</li>)}
                </ul>
                <a className="text-link" href={pagePath("nosotros", lang)}>{lang === "es" ? "Conocer nuestra historia" : "Discover our story"}<ArrowUpRight size={18} /></a>
              </div>
              <div className="home-about-media">
                <img data-cms="about.image" src="/images/expo-team.webp" alt={lang === "es" ? "Equipo de Royal Beans Perú en Expoalimentaria" : "Royal Beans Perú team at Expoalimentaria"} loading="lazy" width="1024" height="683" />
                <div className="product-stat"><strong><AnimatedCounter target={15} suffix="+" /></strong><span>{lang === "es" ? "Productos" : "Products"}</span><i aria-hidden="true" /></div>
              </div>
            </div>
          </section>
          <HomeProductCarousel lang={lang} />
          <section className="home-cta" aria-labelledby="home-cta-title">
            <div className="container"><div><p className="eyebrow eyebrow-light"><span />{lang === "es" ? "Hablemos de negocios" : "Let’s talk business"}</p><h2 id="home-cta-title" data-cms="cta.title">{lang === "es" ? "Cultivemos una oportunidad juntos." : "Let’s grow an opportunity together."}</h2></div><a className="button button-lime" href={pagePath("contacto", lang)}>{lang === "es" ? "Contáctanos" : "Contact us"}<ArrowUpRight size={18} /></a></div>
          </section>
        </>}

        {page === "nosotros" && <>
          <section id="nosotros" className="about-hero" aria-labelledby="about-hero-title">
            <div className="container about-hero-editorial">
              <div className="about-hero-content">
                <p className="about-hero-kicker"><span />{pages.nosotros.label[lang]}</p>
                <h1 id="about-hero-title" data-cms="hero.title">{aboutPage.heroTitle}</h1>
                <p data-cms="hero.description">{aboutPage.heroText}</p>
              </div>
              <figure className="about-hero-media"><img data-cms="hero.image" src="/images/about-field.webp" alt={lang === "es" ? "Campo agrícola de origen peruano" : "Peruvian agricultural field"} width="1400" height="800" fetchPriority="high" /><figcaption><MapPin size={17} />Lambayeque · Perú</figcaption></figure>
            </div>
          </section>

          <section className="about-story section-pad" aria-labelledby="about-story-title">
            <div className="container">
              <div className="about-story-heading">
                <div><p className="eyebrow"><span />{lang === "es" ? "Nuestra esencia" : "Our essence"}</p><h2 id="about-story-title">{aboutPage.introTitle}</h2></div>
                <p>{aboutPage.introText}</p>
              </div>
              <div className="about-pillar-grid">
                {aboutPage.pillars.map(([title, text], index) => {
                  const Icon = [Sprout, History, Handshake][index];
                  return <article key={title}><span className="about-card-icon"><Icon size={24} strokeWidth={1.6} /></span><div><h3>{title}</h3><p>{text}</p></div></article>;
                })}
              </div>
              <div className="about-video-grid">
                <div className="about-video-copy"><p className="eyebrow"><span />{aboutPage.videoKicker}</p><h3>{aboutPage.videoTitle}</h3><p>{lang === "es" ? "Conoce el trabajo, el equipo y el origen que forman parte de Royal Beans Perú." : "Discover the work, team and origin behind Royal Beans Perú."}</p></div>
                {lang === "es" ? <div className="about-video-frame"><AboutVideo lang={lang} /></div> : <div className="about-video-pending" role="img" aria-label="English corporate video coming soon"><img src="/images/expo-team.webp" alt="" loading="lazy" /><div><span>Royal Beans Perú</span><strong>English video coming soon</strong></div></div>}
              </div>
            </div>
          </section>

          <section className="about-principles section-pad" aria-labelledby="about-principles-title">
            <div className="container">
              <p className="eyebrow eyebrow-light"><span />{lang === "es" ? "Nuestra dirección" : "Our direction"}</p>
              <h2 id="about-principles-title">{aboutPage.principlesTitle}</h2>
              <div className="about-principles-grid">
                <article><span className="about-card-icon"><Target size={24} strokeWidth={1.6} /></span><h3>{lang === "es" ? "Nuestra Misión" : "Our Mission"}</h3><p>{aboutPage.mission}</p></article>
                <article><span className="about-card-icon"><Telescope size={24} strokeWidth={1.6} /></span><h3>{lang === "es" ? "Nuestra Visión" : "Our Vision"}</h3><p>{aboutPage.vision}</p></article>
                <article><span className="about-card-icon"><Fingerprint size={24} strokeWidth={1.6} /></span><h3>{lang === "es" ? "Lo que nos define" : "What defines us"}</h3><ul>{aboutPage.values.map(value => <li key={value}><Check size={16} />{value}</li>)}</ul></article>
              </div>
            </div>
          </section>

          <section className="about-trace section-pad" aria-labelledby="about-trace-title">
            <div className="container">
              <div className="about-section-heading"><div><p className="eyebrow"><span />{lang === "es" ? "Trazabilidad de productos" : "Product traceability"}</p><h2 id="about-trace-title">{aboutPage.traceTitle}</h2></div><p>{aboutPage.traceText}</p></div>
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
              <div className="about-section-heading"><div><p className="eyebrow"><span />{lang === "es" ? "Estándares de calidad" : "Quality standards"}</p><h2 id="about-standards-title">{aboutPage.standardsTitle}</h2></div><p>{aboutPage.standardsText}</p></div>
              <div className="standards-viewport" tabIndex={0} aria-label={lang === "es" ? "Certificaciones y estándares" : "Certifications and standards"}><div className="standards-grid">{aboutPage.standards.map(([title, text]) => <article key={title}><span className="certificate-icon"><BadgeCheck size={27} strokeWidth={1.55} /></span><div><h3>{title}</h3><p>{text}</p></div></article>)}</div></div>
            </div>
          </section>

          <section className="about-cta" aria-labelledby="about-cta-title"><div className="container"><div><p className="eyebrow eyebrow-light"><span />{aboutPage.ctaKicker}</p><h2 id="about-cta-title">{aboutPage.ctaTitle}</h2></div><a className="button button-lime" href={pagePath("contacto", lang)}>{aboutPage.ctaButton}<ArrowUpRight size={18} /></a></div></section>
        </>}

        {page === "productos" && !productLine && <>
          <section id="productos" className="subpage-hero subpage-hero-products" aria-labelledby="products-hero-title"><div className="container product-hero-layout"><div className="subpage-hero-content"><p className="eyebrow eyebrow-light"><span />{secondary.products.kicker}</p><h1 id="products-hero-title" data-cms="hero.title">{secondary.products.title}</h1><p data-cms="hero.description">{secondary.products.text}</p></div><div className="product-hero-mosaic" aria-hidden="true"><figure><img src="/images/product-1.webp" alt="" fetchPriority="high" /></figure><figure><img src="/images/product-4.webp" alt="" fetchPriority="high" /></figure><figure><img src="/images/product-13.webp" alt="" fetchPriority="high" /></figure><figure><img src="/images/expo-team.webp" alt="" fetchPriority="high" /></figure><span>2 <small>{lang === "es" ? "líneas" : "lines"}</small></span></div></div></section>
          <section className="product-lines-section section-pad" aria-labelledby="product-lines-title"><div className="container"><div className="product-lines-heading"><p className="eyebrow"><span />{productLines.overview.kicker}</p><h2 id="product-lines-title">{productLines.overview.title}</h2><p>{productLines.overview.text}</p></div><div className="product-lines-grid">
            <article className="product-line-card product-line-card-conventional"><a href={conventionalPath} aria-label={`${productLines.overview.details}: ${productLines.overview.conventional.title}`}><img src="/images/product-4.webp" alt="" loading="lazy" /><div className="product-line-card-copy"><small>{productLines.overview.conventional.label}</small><h3>{productLines.overview.conventional.title}</h3><p>{productLines.overview.conventional.text}</p><span>{productLines.overview.details}<ArrowUpRight size={18} /></span></div></a></article>
            <article className="product-line-card product-line-card-retail"><a href={retailPath} aria-label={`${productLines.overview.details}: ${productLines.overview.retail.title}`}><img src="/images/expo-team.webp" alt="" loading="lazy" /><div className="product-line-card-copy"><small>{productLines.overview.retail.label}</small><h3>{productLines.overview.retail.title}</h3><p>{productLines.overview.retail.text}</p><span>{productLines.overview.details}<ArrowUpRight size={18} /></span></div></a></article>
          </div></div></section>
          <section className="content-cta" aria-labelledby="products-cta-title"><div className="container"><div><p className="eyebrow eyebrow-light"><span />{secondary.ctaKicker}</p><h2 id="products-cta-title">{secondary.ctaTitle}</h2></div><a className="button button-lime" href={pagePath("contacto",lang)}>{secondary.ctaButton}<ArrowUpRight size={18} /></a></div></section>
        </>}

        {page === "productos" && productLine === "conventional" && <>
          <section id="productos" className="subpage-hero product-line-detail-hero product-line-detail-conventional" aria-labelledby="conventional-hero-title"><div className="container product-line-detail-layout"><div className="subpage-hero-content"><a className="product-line-back" href={productOverviewPath}><ArrowRight size={16} />{productLines.back}</a><p className="eyebrow eyebrow-light"><span />{productLines.conventional.kicker}</p><h1 id="conventional-hero-title" data-cms="hero.title">{productLines.conventional.title}</h1><p data-cms="hero.description">{productLines.conventional.text}</p></div><div className="product-line-detail-visual" aria-hidden="true"><img src="/images/product-1.webp" alt="" fetchPriority="high" /><img src="/images/product-4.webp" alt="" fetchPriority="high" /><span>29 <small>{lang === "es" ? "productos" : "products"}</small></span></div></div></section>
          <section className="content-traits" aria-label={lang === "es" ? "Características del catálogo" : "Catalogue features"}><div className="container">{secondary.products.traits.map(([title,text],index) => { const Icon=[PackageCheck,ScanLine,Ship][index]; return <article key={title}><span><Icon size={23} strokeWidth={1.6} /></span><div><h2>{title}</h2><p>{text}</p></div></article>; })}</div></section>
          <section className="products products-catalog section-pad" aria-labelledby="products-catalog-title"><div className="container"><div className="content-heading"><div><p className="eyebrow"><span />{productLines.conventional.kicker}</p><h2 id="products-catalog-title">{productLines.conventional.section}</h2></div><p>{productLines.conventional.note}</p></div><ProductCatalog lang={lang} /></div></section>
          <section className="content-cta" aria-labelledby="products-cta-title"><div className="container"><div><p className="eyebrow eyebrow-light"><span />{secondary.ctaKicker}</p><h2 id="products-cta-title">{secondary.ctaTitle}</h2></div><a className="button button-lime" href={pagePath("contacto",lang)}>{secondary.ctaButton}<ArrowUpRight size={18} /></a></div></section>
        </>}

        {page === "productos" && productLine === "retail" && <>
          <section id="productos" className="subpage-hero product-line-detail-hero product-line-detail-retail" aria-labelledby="retail-hero-title"><div className="container product-line-detail-layout"><div className="subpage-hero-content"><a className="product-line-back" href={productOverviewPath}><ArrowRight size={16} />{productLines.back}</a><p className="eyebrow eyebrow-light"><span />{productLines.retail.kicker}</p><h1 id="retail-hero-title" data-cms="hero.title">{productLines.retail.title}</h1><p data-cms="hero.description">{productLines.retail.text}</p></div><figure className="retail-hero-visual"><img src="/images/expo-team.webp" alt={lang === "es" ? "Equipo Royal Beans Perú presentando productos" : "Royal Beans Perú team presenting products"} fetchPriority="high" /><figcaption><PackageCheck size={20} /><span>{lang === "es" ? "Propuestas según canal" : "Channel-specific proposals"}</span></figcaption></figure></div></section>
          <section className="content-traits" aria-label={lang === "es" ? "Características del catálogo Retail" : "Retail catalogue features"}><div className="container">{productLines.retail.features.map(([title,text],index) => { const Icon=[PackageCheck,Leaf,Handshake][index]; return <article key={title}><span><Icon size={23} strokeWidth={1.6} /></span><div><h2>{title}</h2><p>{text}</p></div></article>; })}</div></section>
          <section className="products products-catalog section-pad" aria-labelledby="retail-line-title"><div className="container"><div className="content-heading"><div><p className="eyebrow"><span />{productLines.retail.kicker}</p><h2 id="retail-line-title">{productLines.retail.section}</h2></div><p>{productLines.retail.note}</p></div><ProductCatalog lang={lang} line="retail" /></div></section>
          <section className="content-cta" aria-labelledby="retail-cta-title"><div className="container"><div><p className="eyebrow eyebrow-light"><span />{secondary.ctaKicker}</p><h2 id="retail-cta-title">{secondary.ctaTitle}</h2></div><a className="button button-lime" href={pagePath("contacto",lang)}>{secondary.ctaButton}<ArrowUpRight size={18} /></a></div></section>
        </>}

        {page === "participacion" && <>
          <section id="participacion" className="subpage-hero subpage-hero-events" aria-labelledby="events-hero-title"><div className="container event-hero-layout"><div className="subpage-hero-content"><p className="eyebrow eyebrow-light"><span />{secondary.events.kicker}</p><h1 id="events-hero-title" data-cms="hero.title">{secondary.events.title}</h1><p data-cms="hero.description">{secondary.events.text}</p></div><div className="event-hero-gallery"><figure className="event-hero-main"><img src="/images/expo-main.webp" alt={lang === "es" ? "Royal Beans Perú participando en Expoalimentaria" : "Royal Beans Perú participating at Expoalimentaria"} fetchPriority="high" /></figure><figure className="event-hero-secondary"><img src="/images/expo-team.webp" alt="" fetchPriority="high" /></figure><span><CalendarCheck size={16} />Expoalimentaria · 2024—2025</span></div></div></section>
          <section className="event-story section-pad" aria-labelledby="events-title"><div className="container"><div className="content-heading"><div><p className="eyebrow"><span />Expoalimentaria</p><h2 id="events-title">{secondary.events.section}</h2></div><p>{secondary.events.note}</p></div><div className="event-grid"><figure className="event-main"><img src="/images/expo-main.webp" alt={lang === "es" ? "Royal Beans Perú en Expoalimentaria 2024" : "Royal Beans Perú at Expoalimentaria 2024"} loading="lazy" /><figcaption><span>2024</span><h3>Expoalimentaria</h3><p>{t.eventLabel} · Lima, Perú</p></figcaption></figure><figure><img src="/images/expo-team.webp" alt={lang === "es" ? "Equipo Royal Beans Perú en Expoalimentaria 2025" : "Royal Beans Perú team at Expoalimentaria 2025"} loading="lazy" /><figcaption><span>2025</span><h3>Expoalimentaria</h3><p>{t.eventLabel} · Lima, Perú</p></figcaption></figure></div></div></section>
          <section className="content-traits content-traits-dark" aria-label={lang === "es" ? "Valor de nuestra participación" : "Value of our participation"}><div className="container">{secondary.events.traits.map(([title,text],index) => { const Icon=[CalendarCheck,UsersRound,Handshake][index]; return <article key={title}><span><Icon size={23} strokeWidth={1.6} /></span><div><h2>{title}</h2><p>{text}</p></div></article>; })}</div></section>
          <section className="content-cta" aria-labelledby="events-cta-title"><div className="container"><div><p className="eyebrow eyebrow-light"><span />{secondary.ctaKicker}</p><h2 id="events-cta-title">{secondary.ctaTitle}</h2></div><a className="button button-lime" href={pagePath("contacto",lang)}>{secondary.ctaButton}<ArrowUpRight size={18} /></a></div></section>
        </>}

        {page === "presencia" && <>
          <section id="presencia" className="subpage-hero subpage-hero-presence" aria-labelledby="presence-hero-title"><div className="container presence-hero-layout"><div className="subpage-hero-content"><p className="eyebrow eyebrow-light"><span />{secondary.presence.kicker}</p><h1 id="presence-hero-title" data-cms="hero.title">{secondary.presence.title}</h1><p data-cms="hero.description">{secondary.presence.text}</p></div><div className="presence-hero-map" aria-hidden="true"><span className="presence-map-orbit presence-map-orbit-one" /><span className="presence-map-orbit presence-map-orbit-two" /><img src="/icons/peru-map.svg" alt="" width="220" height="300" /><i className="presence-map-origin" /><span className="presence-map-label">Chiclayo<br /><small>Lambayeque</small></span><Globe2 className="presence-map-globe" size={32} /></div></div></section>
          <section className="presence presence-story section-pad" aria-labelledby="presence-title"><div className="container"><div className="content-heading"><div><p className="eyebrow"><span />{lang === "es" ? "Nuestro alcance" : "Our reach"}</p><h2 id="presence-title">{secondary.presence.section}</h2></div><p>{secondary.presence.note}</p></div><div className="presence-grid"><div className="presence-visual"><img src="/images/hero-tablet.webp" alt={lang === "es" ? "Cultivo peruano de origen" : "Peruvian crop at origin"} loading="lazy" /><span><MapPin size={19} />Chiclayo · Lambayeque</span></div><div className="route-panel"><div className="route-origin"><span className="pulse" /><img src="/icons/peru-map.svg" alt="" width="24" height="24" /><div><small>{t.origin}</small><strong>Chiclayo · Lambayeque</strong></div></div><div className="route-line"><span /><span /><span /></div><div className="destination-list"><small>{t.destinations}</small>{secondary.presence.traits.map(([title,text],index) => { const Icon=[MapPin,Route,Globe2][index]; return <div key={title}><Icon size={18} /><div><strong>{title}</strong><p>{text}</p></div><ArrowUpRight size={17} /></div>; })}</div></div></div></div></section>
          <section className="content-cta" aria-labelledby="presence-cta-title"><div className="container"><div><p className="eyebrow eyebrow-light"><span />{secondary.ctaKicker}</p><h2 id="presence-cta-title">{secondary.ctaTitle}</h2></div><a className="button button-lime" href={pagePath("contacto",lang)}>{secondary.ctaButton}<ArrowUpRight size={18} /></a></div></section>
        </>}

        {page === "impacto" && <>
          <section id="impacto" className="subpage-hero subpage-hero-impact" aria-labelledby="impact-hero-title"><div className="impact-hero-media"><img src="/images/impact-crop.webp" alt={lang === "es" ? "Cultivo agrícola peruano" : "Peruvian agricultural crop"} width="1200" height="800" fetchPriority="high" /></div><div className="impact-hero-copy"><div className="subpage-hero-content"><p className="eyebrow eyebrow-light"><span />{secondary.impact.kicker}</p><h1 id="impact-hero-title" data-cms="hero.title">{secondary.impact.title}</h1><p data-cms="hero.description">{secondary.impact.text}</p><div className="impact-hero-icons" aria-hidden="true"><span><Sprout size={21} /></span><i /><span><ShieldCheck size={21} /></span><i /><span><Handshake size={21} /></span></div></div></div></section>
          <section className="impact-story section-pad" aria-labelledby="impact-title"><div className="container"><div className="content-heading"><div><p className="eyebrow"><span />{lang === "es" ? "Nuestra forma de trabajar" : "How we work"}</p><h2 id="impact-title">{secondary.impact.section}</h2></div><p>{secondary.impact.note}</p></div><div className="impact-feature-grid">{secondary.impact.traits.map(([title,text],index) => { const Icon=[Sprout,ShieldCheck,Handshake][index]; return <article key={title}><span><Icon size={25} strokeWidth={1.55} /></span><h3>{title}</h3><p>{text}</p></article>; })}</div><div className="impact-media"><img src="/images/hero-tablet.webp" alt={lang === "es" ? "Trabajo agrícola en el campo peruano" : "Agricultural work in Peruvian fields"} loading="lazy" /><div><p className="eyebrow eyebrow-light"><span />{lang === "es" ? "Desde el origen" : "From the source"}</p><h2>{lang === "es" ? "Cercanía que fortalece la cadena." : "Proximity that strengthens the chain."}</h2><p>{lang === "es" ? "Conocer el producto y mantener una comunicación directa nos permite responder con mayor claridad a productores y compradores." : "Knowing the product and maintaining direct communication helps us respond more clearly to growers and buyers."}</p></div></div></div></section>
          <section className="content-cta" aria-labelledby="impact-cta-title"><div className="container"><div><p className="eyebrow eyebrow-light"><span />{secondary.ctaKicker}</p><h2 id="impact-cta-title">{secondary.ctaTitle}</h2></div><a className="button button-lime" href={pagePath("contacto",lang)}>{secondary.ctaButton}<ArrowUpRight size={18} /></a></div></section>
        </>}

        {page === "contacto" && <>
          <section id="contacto" className="subpage-hero subpage-hero-contact" aria-labelledby="contact-hero-title"><div className="container contact-hero-layout"><div className="subpage-hero-content"><p className="eyebrow eyebrow-light"><span />{secondary.contact.kicker}</p><h1 id="contact-hero-title" data-cms="hero.title">{secondary.contact.title}</h1><p data-cms="hero.description">{secondary.contact.text}</p></div><aside className="contact-hero-card" aria-label={lang === "es" ? "Canales de contacto rápido" : "Quick contact channels"}><div><span><Phone size={19} /></span><a href="tel:+51961804500">+51 961 804 500</a></div><div><span><Mail size={19} /></span><a href="mailto:administracion@royalbeansperu.com">administracion@royalbeansperu.com</a></div><a className="contact-hero-whatsapp" href="https://wa.me/51961804500" target="_blank" rel="noreferrer"><MessageCircle size={20} />WhatsApp<ArrowUpRight size={17} /></a></aside></div></section>
          <section className="contact contact-page-body section-pad" aria-labelledby="contact-section-title"><div className="container"><div className="content-heading content-heading-light"><div><p className="eyebrow eyebrow-light"><span />{lang === "es" ? "Contacto directo" : "Direct contact"}</p><h2 id="contact-section-title">{secondary.contact.section}</h2></div><p>{secondary.contact.note}</p></div><div className="contact-grid"><div className="contact-copy"><div className="contact-details"><article><span><MapPin size={22} /></span><div><h3>{t.addressLabel}</h3><p>{t.address}</p></div></article><article><span><Mail size={22} /></span><div><h3>{t.emailLabel}</h3><a href="mailto:administracion@royalbeansperu.com">administracion@royalbeansperu.com</a></div></article><article><span><Phone size={22} /></span><div><h3>{t.phoneLabel}</h3><a href="tel:+51961804500">+51 961 804 500</a></div></article><article><span><Clock3 size={22} /></span><div><h3>{lang === "es" ? "Atención" : "Availability"}</h3><p>{lang === "es" ? "Respuesta comercial directa" : "Direct business response"}</p></div></article></div><a className="contact-whatsapp" href="https://wa.me/51961804500" target="_blank" rel="noreferrer"><MessageCircle size={22} /><span><strong>WhatsApp</strong><small>{lang === "es" ? "Iniciar conversación" : "Start conversation"}</small></span><ArrowUpRight size={18} /></a></div><ContactForm lang={lang} /></div></div></section>
        </>}
      </main>

      <footer className="site-footer shared-site-footer">
        <div className="container footer-grid">
          <div className="footer-brand"><a className="brand" href={pagePath("inicio", lang)} aria-label="Royal Beans Perú"><Brand /></a><p>{t.footerText}</p></div>
          <nav className="footer-column" aria-label={lang === "es" ? "Enlaces del sitio" : "Site links"}><h2>{lang === "es" ? "Explora" : "Explore"}</h2>{nav.map(item => <a key={item.href} href={item.href}>{item.label}</a>)}</nav>
          <div className="footer-column footer-contact"><h2>{lang === "es" ? "Contacto" : "Contact"}</h2><p><MapPin size={18} />Chiclayo, Lambayeque, Perú</p><a href="mailto:administracion@royalbeansperu.com"><Mail size={18} />administracion@royalbeansperu.com</a><a href="tel:+51961804500"><Phone size={18} />+51 961 804 500</a></div>
          <div className="footer-column footer-social"><h2>{lang === "es" ? "Síguenos" : "Follow us"}</h2><a href="https://www.instagram.com/royalbeans_peru/" target="_blank" rel="noreferrer"><InstagramIcon size={18} />Instagram</a><a href="https://www.facebook.com/profile.php?id=61573866174999" target="_blank" rel="noreferrer"><FacebookIcon size={18} />Facebook</a><a href="https://wa.me/51961804500" target="_blank" rel="noreferrer"><WhatsappIcon size={18} />WhatsApp</a></div>
        </div>
        <div className="container footer-bottom"><span>© {new Date().getFullYear()} Royal Beans Perú S.A.C.</span><span>{lang === "es" ? "Origen peruano · Alcance global" : "Peruvian origin · Global reach"}</span><a href={lang === "es" ? "/politica-de-privacidad/" : "/en/privacy-policy/"}>{t.privacy}</a></div>
      </footer>
      {page !== "contacto" && <a className="whatsapp-float" href="https://wa.me/51961804500" target="_blank" rel="noreferrer" aria-label={lang === "es" ? "Abrir WhatsApp" : "Open WhatsApp"}><WhatsappIcon size={25} /></a>}
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }} />
    </>
  );
}
