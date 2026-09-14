import { BadgeCheck, Check, Globe2, Leaf, MapPin, ArrowUpRight, ArrowRight, Mail, Phone, Sprout, History, Handshake, Target, Telescope, Fingerprint, Warehouse, SlidersHorizontal, ShieldCheck, SearchCheck, Container, FileCheck2, Truck } from "lucide-react";
import { AboutVideo, AnimatedCounter, Brand, ContactForm, ConversionTracker, FacebookIcon, Header, HomeProductCarousel, HorizontalRail, InstagramIcon, ProductCatalog, WhatsappIcon } from "./InteractiveShell";
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

export default function HomePage({ lang, page = "inicio" }: { lang: Lang; page?: PageKey }) {
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
  const schema = page === "inicio" ? {
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
            url: `https://royalbeansperu.com${pagePath("productos", lang)}`,
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
      <Header nav={nav} lang={lang} page={page} />
      <main id="contenido" className={page === "inicio" ? "home-page" : page === "nosotros" ? "inner-page about-page" : "inner-page"}>
        {page !== "inicio" && page !== "nosotros" && <div className="page-intro"><div className="container"><nav className="breadcrumbs" aria-label={lang === "es" ? "Ruta de navegación" : "Breadcrumb"}><a href={pagePath("inicio", lang)}>{pages.inicio.label[lang]}</a><span>/</span><span aria-current="page">{pages[page].label[lang]}</span></nav><h1>{pages[page].label[lang]}</h1><p>{pages[page].description[lang]}</p></div></div>}
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
            <h1 id="hero-title">{t.heroTitle}</h1>
            <p className="hero-copy">{t.heroText}</p>
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
                <h2 id="home-about-title">{lang === "es" ? "Calidad peruana, lista para el mundo." : "Peruvian quality, ready for the world."}</h2>
                <p>{lang === "es" ? "Royal Beans Perú conecta el trabajo del campo con compradores que valoran el origen, la constancia y una relación comercial directa." : "Royal Beans Perú connects the work of the field with buyers who value origin, consistency and direct business relationships."}</p>
                <ul>
                  {(lang === "es" ? ["Origen en Lambayeque", "Acopio y proceso especializado", "Cercanía con agricultores", "Selección con trazabilidad", "Atención nacional e internacional"] : ["Based in Lambayeque", "Specialized sourcing and processing", "Close to growers", "Selection with traceability", "Domestic and international service"]).map(item => <li key={item}><span><Check size={16} strokeWidth={2.5} /></span>{item}</li>)}
                </ul>
                <a className="text-link" href={pagePath("nosotros", lang)}>{lang === "es" ? "Conocer nuestra historia" : "Discover our story"}<ArrowUpRight size={18} /></a>
              </div>
              <div className="home-about-media">
                <img src="/images/expo-team.webp" alt={lang === "es" ? "Equipo de Royal Beans Perú en Expoalimentaria" : "Royal Beans Perú team at Expoalimentaria"} loading="lazy" width="1024" height="683" />
                <div className="product-stat"><strong><AnimatedCounter target={15} suffix="+" /></strong><span>{lang === "es" ? "Productos" : "Products"}</span><i aria-hidden="true" /></div>
              </div>
            </div>
          </section>
          <HomeProductCarousel lang={lang} />
          <section className="home-cta" aria-labelledby="home-cta-title">
            <div className="container"><div><p className="eyebrow eyebrow-light"><span />{lang === "es" ? "Hablemos de negocios" : "Let’s talk business"}</p><h2 id="home-cta-title">{lang === "es" ? "Cultivemos una oportunidad juntos." : "Let’s grow an opportunity together."}</h2></div><a className="button button-lime" href={pagePath("contacto", lang)}>{lang === "es" ? "Contáctanos" : "Contact us"}<ArrowUpRight size={18} /></a></div>
          </section>
        </>}

        {page === "nosotros" && <>
          <section id="nosotros" className="about-hero" aria-labelledby="about-hero-title">
            <div className="container">
              <div className="about-hero-panel">
                <img src="/images/about-field.webp" alt="" width="1400" height="800" fetchPriority="high" />
                <div className="about-hero-content">
                  <p className="about-hero-kicker"><span />{pages.nosotros.label[lang]}</p>
                  <h1 id="about-hero-title">{aboutPage.heroTitle}</h1>
                  <p>{aboutPage.heroText}</p>
                </div>
              </div>
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

        {page === "productos" && (<section id="productos" className="products section-pad">
          <div className="container">
            <div className="section-heading split-heading">
              <div><p className="eyebrow"><span />{t.productsKicker}</p><h2>{t.productsTitle}</h2></div>
              <p>{t.productsIntro}</p>
            </div>
            <ProductCatalog lang={lang} />
          </div>
        </section>)}

        {page === "participacion" && (<section id="participacion" className="participation section-pad">
          <div className="container">
            <div className="section-heading participation-heading">
              <div><p className="eyebrow eyebrow-light"><span />{t.participationKicker}</p><h2>{t.participationTitle}</h2></div>
              <p>{t.participationText}</p>
            </div>
            <div className="event-grid">
              <figure className="event-main"><img src="/images/expo-main.webp" alt={lang === "es" ? "Royal Beans Perú en Expoalimentaria 2024" : "Royal Beans Perú at Expoalimentaria 2024"} loading="lazy" /><figcaption><span>2024</span><h3>Expoalimentaria</h3><p>{t.eventLabel} · Lima, Perú</p></figcaption></figure>
              <figure><img src="/images/expo-team.webp" alt={lang === "es" ? "Equipo Royal Beans Perú en feria internacional" : "Royal Beans Perú team at an international trade fair"} loading="lazy" /><figcaption><span>2025</span><h3>Expoalimentaria</h3><p>{t.eventLabel} · Lima, Perú</p></figcaption></figure>
            </div>
          </div>
        </section>)}

        {page === "presencia" && (<section id="presencia" className="presence section-pad">
          <div className="container presence-grid">
            <div className="presence-intro">
              <p className="eyebrow"><span />{t.presenceKicker}</p>
              <h2>{t.presenceTitle}</h2>
              <p>{t.presenceText}</p>
            </div>
            <div className="route-panel">
              <div className="route-origin"><span className="pulse" /><MapPin size={21} /><div><small>{t.origin}</small><strong>Chiclayo · Lambayeque</strong></div></div>
              <div className="route-line"><span /><span /><span /></div>
              <div className="destination-list"><small>{t.destinations}</small>{t.destinationsList.map((place, index) => <div key={place}><span>0{index + 1}</span><strong>{place}</strong>{index < 3 && <Globe2 size={17} />}</div>)}</div>
            </div>
          </div>
        </section>)}

        {page === "impacto" && (<section id="impacto" className="impact section-pad">
          <div className="impact-image"><img src="/images/impact-crop.webp" alt={lang === "es" ? "Detalle de cultivo agrícola en Perú" : "Detail of a Peruvian crop"} loading="lazy" /></div>
          <div className="container impact-grid">
            <div className="impact-copy">
              <p className="eyebrow eyebrow-light"><span />{t.impactKicker}</p>
              <h2>{t.impactTitle}</h2>
              <p>{t.impactText}</p>
            </div>
            <div className="impact-list">
              {t.impactItems.map(([number, title, text]) => <article key={number}><span>{number}</span><div><h3>{title}</h3><p>{text}</p></div><Leaf size={20} /></article>)}
            </div>
          </div>
        </section>)}

        {page === "contacto" && (<section id="contacto" className="contact section-pad">
          <div className="container contact-grid">
            <div className="contact-copy">
              <p className="eyebrow eyebrow-light"><span />{t.contactKicker}</p>
              <h2>{t.contactTitle}</h2>
              <p>{t.contactText}</p>
              <div className="contact-details">
                <div><h3>{t.addressLabel}</h3><p>{t.address}</p></div>
                <div><h3>{t.emailLabel}</h3><a href="mailto:administracion@royalbeansperu.com">administracion@royalbeansperu.com</a></div>
                <div><h3>{t.phoneLabel}</h3><a href="tel:+51961804500">+51 961 804 500</a></div>
              </div>
            </div>
            <ContactForm lang={lang} />
          </div>
        </section>)}
      </main>

      <footer className="site-footer">
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
