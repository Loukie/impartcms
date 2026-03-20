import 'grapesjs/dist/css/grapes.min.css';
import grapesjs             from 'grapesjs';
import grapesjsBlocksBasic  from 'grapesjs-blocks-basic';
import grapesjsForms        from 'grapesjs-plugin-forms';
import grapesjsCustomCode   from 'grapesjs-custom-code';
import grapesjsTabs         from 'grapesjs-tabs';
import grapesjsTooltip      from 'grapesjs-tooltip';
import grapesjsTyped        from 'grapesjs-typed';
import grapesjsNavbar       from 'grapesjs-navbar';
import grapesjsFlexbox      from 'grapesjs-blocks-flexbox';
import grapesjsCountdown    from 'grapesjs-component-countdown';
import grapesjsStyleBg      from 'grapesjs-style-bg';

document.addEventListener('DOMContentLoaded', () => {
    const cfg = window.__VE__ || {};

    const baseCanvasCSS = `
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; }
        img { max-width: 100%; height: auto; }
    `;

    // Override scroll-reveal animations — no IntersectionObserver runs in the
    // canvas iframe, so elements with opacity:0 would stay hidden forever.
    const editorOverrideCSS = `
        .reveal, [class*="reveal-"] {
            opacity: 1 !important;
            transform: none !important;
            transition: none !important;
        }
    `;

    // ─── Extract @imports from page CSS before editor init ───────────────────
    // @import rules must come first in any stylesheet; non-@import page CSS is
    // loaded into the CSS manager (not protectedCss) so the Style Manager panel
    // can display and edit existing class values (e.g. .split-image height).
    const cssImportLines = [];
    const canvasCssBody  = (cfg.canvasCSS || '').replace(
        /@import\s+url\(['"][^'"]*['"]\)\s*;/gi,
        m => { cssImportLines.push(m); return ''; }
    ).trim();

    // ─── GrapesJS init ───────────────────────────────────────────────────────
    const editor = grapesjs.init({
        container: '#ve-editor',
        height:    '100%',
        width:     'auto',
        storageManager: false,
        components: cfg.html || '',

        // protectedCss = @imports (for fonts) + base reset + reveal override.
        // The page-specific CSS body goes into the CSS manager instead (see below),
        // so it appears in the Style Manager when a classed element is selected.
        protectedCss: cssImportLines.join('\n') + '\n' + baseCanvasCSS + '\n' + editorOverrideCSS,
        avoidInlineStyle: false,
        forceClass:       false,

        deviceManager: {
            devices: [
                { name: 'Desktop', width: ''      },
                { name: 'Tablet',  width: '768px', widthMedia: '992px' },
                { name: 'Mobile',  width: '375px', widthMedia: '480px' },
            ],
        },

        plugins: [
            grapesjsBlocksBasic,
            grapesjsForms,
            grapesjsCustomCode,
            grapesjsTabs,
            grapesjsTooltip,
            grapesjsTyped,
            grapesjsNavbar,
            grapesjsFlexbox,
            grapesjsCountdown,
            grapesjsStyleBg,
        ],
        pluginsOpts: {
            [grapesjsBlocksBasic]: { flexGrid: true },
            [grapesjsForms]:       {},
            [grapesjsCustomCode]:  {},
            [grapesjsTabs]:        {},
            [grapesjsTooltip]:     {},
            [grapesjsTyped]:       {},
            [grapesjsNavbar]:      {},
            [grapesjsFlexbox]:     {},
            [grapesjsCountdown]:   {},
            [grapesjsStyleBg]:     {},
        },

        canvas: {},
        assetManager: {
            autoAdd: true,
            upload:  false,
            assets:  [],
        },
    });

    // ─── Load media library into asset manager ───────────────────────────────
    // Fetch once on load so double-clicking any image opens the built-in picker
    // pre-populated with the media library.
    editor.on('load', () => {
        fetch(cfg.assetsUrl, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(json => {
                const assets = (json.data || []).map(a => ({
                    type: 'image', src: a.src, name: a.name || '',
                    width: a.width || 0, height: a.height || 0,
                }));
                editor.AssetManager.add(assets);
            })
            .catch(() => {});
    });

    // ─── Load page CSS into CSS manager ──────────────────────────────────────
    // After GrapesJS finishes loading, inject the page's CSS into the CSS manager
    // (not just protectedCss). This makes the Style Manager right panel show
    // existing property values — e.g. selecting .split-image shows its height.
    editor.on('load', () => {
        if (canvasCssBody.trim()) {
            editor.setStyle(canvasCssBody);
        }
    });

    // ─── Extra blocks ────────────────────────────────────────────────────────
    const ic = path =>
        `<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${path}</svg>`;

    const bm = editor.BlockManager;

    bm.add('heading', {
        label: 'Heading', category: 'Basic',
        content: '<h2>Your Heading Here</h2>',
        media: ic('<path d="M4 6h16M4 12h10M4 18h7"/>'),
    });
    bm.add('ve-button', {
        label: 'Button', category: 'Basic',
        content: '<a href="#" style="display:inline-block;padding:12px 28px;background:#2563eb;color:#fff;border-radius:6px;text-decoration:none;font-weight:600;">Click Me</a>',
        media: ic('<rect x="3" y="8" width="18" height="8" rx="3"/><path d="M9 12h6"/>'),
    });
    bm.add('divider', {
        label: 'Divider', category: 'Basic',
        content: '<hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">',
        media: ic('<path d="M5 12h14"/>'),
    });
    bm.add('section', {
        label: 'Section', category: 'Layout',
        content: '<section style="padding:60px 20px;"><div style="max-width:1200px;margin:0 auto;"><h2>Section Title</h2><p>Section content goes here.</p></div></section>',
        media: ic('<rect x="2" y="4" width="20" height="16" rx="2"/><path d="M2 9h20"/>'),
    });
    bm.add('hero', {
        label: 'Hero', category: 'Layout',
        content: '<section style="padding:100px 20px;background:linear-gradient(135deg,#1e3a5f,#2563eb);color:#fff;text-align:center;"><h1 style="font-size:2.5rem;margin-bottom:16px;">Hero Title</h1><p style="font-size:1.1rem;margin-bottom:32px;opacity:.9;">Supporting subtitle text goes here.</p><a href="#" style="display:inline-block;padding:14px 32px;background:#fff;color:#2563eb;border-radius:6px;font-weight:700;text-decoration:none;">Get Started</a></section>',
        media: ic('<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>'),
    });

    // ─── Save ────────────────────────────────────────────────────────────────
    const saveBtn  = document.getElementById('ve-save');
    const statusEl = document.getElementById('ve-status');

    function setStatus(msg, colour) {
        if (!statusEl) return;
        statusEl.textContent = msg;
        statusEl.style.color = colour || '#64748b';
    }

    // Return only the editable page-body HTML, excluding any nav/footer wrappers
    // that were injected for visual context (they are marked selectable:false/hoverable:false).
    // GrapesJS strips HTML comments so we cannot rely on <!-- ve-body-start --> markers —
    // instead we read the component tree directly.
    function getCleanHtml() {
        const allComponents = editor.getComponents();
        const bodyParts = [];
        let hasLayoutWrappers = false;

        allComponents.each(comp => {
            if (comp.get('selectable') === false || comp.get('hoverable') === false) {
                // This is a read-only nav or footer wrapper — skip it.
                hasLayoutWrappers = true;
            } else {
                bodyParts.push(comp.toHTML());
            }
        });

        if (hasLayoutWrappers) {
            // Return only the editable body components.
            return bodyParts.join('\n').replace(/<\/?(html|head|body)[^>]*>/gi, '').trim();
        }

        // No layout wrappers present (e.g. editing a layout block) — use full output.
        const raw = editor.getHtml();
        const match = raw.match(/<body[^>]*>([\s\S]*)<\/body>/i);
        const inner = match ? match[1] : raw;
        return inner.replace(/<\/?(html|head|body)[^>]*>/gi, '').trim();
    }

    async function save() {
        if (!saveBtn) return;
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving…';
        setStatus('', '');
        try {
            const res = await fetch(cfg.saveUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': cfg.csrfToken,
                },
                body: JSON.stringify({
                    html:          getCleanHtml(),
                    extracted_css: cfg.extractedCSS || '',
                    // full_css = @imports (for fonts) + everything in the CSS manager
                    // (original page CSS + any Style Manager edits). The server uses
                    // this to REPLACE the snippet content so edited values are persisted.
                    full_css: (
                        cssImportLines.join('\n') +
                        (cssImportLines.length ? '\n\n' : '') +
                        (editor.getCss() || '')
                    ).trim(),
                }),
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            setStatus('Saved', '#22c55e');
        } catch (err) {
            setStatus('Save failed', '#ef4444');
            console.error('Visual editor save error:', err);
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save';
        }
    }

    saveBtn && saveBtn.addEventListener('click', save);
    document.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') { e.preventDefault(); save(); }
    });

    // ─── Typography Panel ─────────────────────────────────────────────────────
    if (cfg.typographyUrl) {
        initTypographyPanel(cfg, editor);
    }
});

// Popular Google Fonts list
const GOOGLE_FONTS = [
    'ABeeZee','Abel','Abril Fatface','Acme','Actor','Adamina','Advent Pro','Aguafina Script',
    'Alata','Alatsi','Aldrich','Alegreya','Alegreya Sans','Alegreya SC','Alfa Slab One',
    'Alice','Alike','Allan','Allerta','Allerta Stencil','Allison','Allura','Almendra',
    'Alumni Sans','Amatic SC','Amethysta','Amiri','Amita','Anaheim','Andada Pro',
    'Andika','Anek Bangla','Annie Use Your Telescope','Anonymous Pro','Antic',
    'Antic Didone','Antic Slab','Anton','Antonio','Anybody','Arapey','Arbutus',
    'Arbutus Slab','Architects Daughter','Archivo','Archivo Black','Archivo Narrow',
    'Are You Serious','Aref Ruqaa','Arima','Arimo','Arizonia','Armata','Arsenal',
    'Artifika','Arvo','Arya','Asap','Asap Condensed','Asar','Asset','Assistant',
    'Astloch','Asul','Athiti','Atkinson Hyperlegible','Atma','Atomic Age','Aubrey',
    'Audiowide','Autour One','Average','Average Sans','Averia Gruesa Libre',
    'Averia Libre','Averia Sans Libre','Averia Serif Libre','Azeret Mono',
    'B612','B612 Mono','BIZ UDGothic','BIZ UDMincho','BIZ UDPGothic','BIZ UDPMincho',
    'Babylonica','Bacasime Antique','Bad Script','Bagel Fat One','Bahiana','Bahianita',
    'Bai Jamjuree','Bakbak One','Ballet','Baloo 2','Baloo Bhai 2','Baloo Bhaina 2',
    'Baloo Chettan 2','Baloo Da 2','Baloo Paaji 2','Baloo Tamma 2','Baloo Tammudu 2',
    'Baloo Thambi 2','Balsamiq Sans','Balthazar','Bangers','Barlow','Barlow Condensed',
    'Barlow Semi Condensed','Barriecito','Barrio','Basic','Baskervville','Battambang',
    'Baumans','Bayon','Be Vietnam Pro','Bebas Neue','Belgrano','Bellefair','Belleza',
    'Bellota','Bellota Text','BenchNine','Benne','Bentham','Berkshire Swash',
    'Besley','Beth Ellen','Bevan','BioRhyme','BioRhyme Expanded','Birthstone',
    'Birthstone Bounce','Biryani','Bitter','Black And White Picture','Black Han Sans',
    'Black Ops One','Blaka','Blaka Hollow','Blaka Ink','Blinker','Bodoni Moda',
    'Bokor','Bona Nova','Bonbon','Bonheur Royale','Boogaloo','Bowlby One',
    'Bowlby One SC','Brawler','Bree Serif','Bricolage Grotesque','Brygada 1918',
    'Bubblegum Sans','Bubbler One','Buda','Buenard','Bungee','Bungee Hairline',
    'Bungee Inline','Bungee Outline','Bungee Shade','Bungee Spice','Butcherman',
    'Butterfly Kids','Cabin','Cabin Condensed','Cabin Sketch','Caesar Dressing',
    'Cagliostro','Cairo','Cairo Play','Caladea','Calistoga','Calligraffitti',
    'Cambay','Cambo','Candal','Cantarell','Cantata One','Cantora One','Capriola',
    'Caramel','Carattere','Cardo','Carme','Carrois Gothic','Carrois Gothic SC',
    'Carter One','Castoro','Catamaran','Caudex','Caveat','Caveat Brush','Cedarville Cursive',
    'Ceviche One','Chakra Petch','Changa','Changa One','Chango','Charis SIL',
    'Charm','Charmonman','Chathura','Chau Philomene One','Chela One','Chelsea Market',
    'Chenla','Cherry Bomb One','Cherry Cream Soda','Cherry Swash','Chewy',
    'Chicle','Chilanka','Chivo','Chivo Mono','Chonburi','Cinzel','Cinzel Decorative',
    'Clicker Script','Coda','Coda Caption','Codystar','Coiny','Combo','Comfortaa',
    'Comforter','Comforter Brush','Comic Neue','Coming Soon','Commissioner',
    'Concert One','Condiment','Content','Contrail One','Convergence','Cookie',
    'Copse','Corben','Cormorant','Cormorant Garamond','Cormorant Infant',
    'Cormorant SC','Cormorant Unicase','Cormorant Upright','Courgette',
    'Courier Prime','Cousine','Coustard','Covered By Your Grace','Crafty Girls',
    'Creepster','Crete Round','Crimson Pro','Crimson Text','Croissant One',
    'Crushed','Cuprum','Cute Font','Cutive','Cutive Mono',
    'DM Mono','DM Sans','DM Serif Display','DM Serif Text','Damion','Dancing Script',
    'Dangrek','Darker Grotesque','Darumadrop One','David Libre','Dawning of a New Day',
    'Days One','Dekko','Dela Gothic One','Delicious Handrawn','Delius',
    'Delius Swash Caps','Delius Unicase','Della Respira','Denk One','Devonshire',
    'Dhurjati','Didact Gothic','Diplomata','Diplomata SC','Do Hyeon','Dokdo',
    'Domine','Donegal One','Dongle','Doppio One','Dorsa','Dosis','DotGothic16',
    'Dr Sugiyama','Duru Sans','Dynalight',
    'EB Garamond','Eagle Lake','East Sea Dokdo','Eastman','Eczar','Edu NSW ACT Foundation',
    'Edu QLD Beginner','Edu SA Beginner','Edu TAS Beginner','Edu VIC WA NT Beginner',
    'El Messiri','Electrolize','Elsie','Elsie Swash Caps','Emblema One','Emilys Candy',
    'Encode Sans','Encode Sans Condensed','Encode Sans Expanded','Encode Sans SC',
    'Encode Sans Semi Condensed','Encode Sans Semi Expanded','Engagement',
    'Englebert','Enriqueta','Ephesis','Epilogue','Erica One','Esteban','Estonia',
    'Euphoria Script','Ewert','Exo','Exo 2','Expletus Sans','Explora',
    'Fahkwang','Familjen Grotesk','Fanwood Text','Farro','Farsan','Fascinate',
    'Fascinate Inline','Faster One','Fasthand','Fauna One','Faustina','Federant',
    'Federo','Felipa','Fenix','Festive','Figtree','Finger Paint','Finlandica',
    'Fira Code','Fira Mono','Fira Sans','Fira Sans Condensed','Fira Sans Extra Condensed',
    'Fjalla One','Fjord One','Flamenco','Flavors','Fleur De Leah','Flow Block',
    'Flow Circular','Flow Rounded','Foldit','Fondamento','Fontdiner Swanky',
    'Forum','Fragment Mono','Frank Ruhl Libre','Fraunces','Freckle Face',
    'Fredericka the Great','Fredoka','Freehand','Fresca','Frijole','Fruktur',
    'Fugaz One','Fuggles','Fuzzy Bubbles',
    'GFS Didot','GFS Neohellenic','Gajraj One','Galada','Galdeano','Galindo',
    'Gamja Flower','Gantari','Gasoek One','Gayathri','Gelasio','Gemunu Libre',
    'Genos','Gentium Book Plus','Gentium Plus','Geo','Georama','Geostar',
    'Geostar Fill','Germania One','Gideon Roman','Gidugu','Gilda Display',
    'Girassol','Give You Glory','Glass Antiqua','Glegoo','Gluten','Gloria Hallelujah',
    'Glory','Gluten','Goldman','Golos Text','Gorditas','Gothic A1','Gotu',
    'Goudy Bookletter 1911','Gowun Batang','Gowun Dodum','Graduate','Grand Hotel',
    'Grandstander','Grape Nuts','Gravitas One','Great Vibes','Grechen Fuemen',
    'Grenze','Grenze Gotisch','Grey Qo','Griffy','Gruppo','Gudea','Gugi','Gulzar',
    'Gupter','Gurajada','Gwendolyn',
    'Habibi','Hachi Maru Pop','Hahmlet','Halant','Hammersmith One','Hanalei',
    'Hanalei Fill','Handlee','Hanuman','Happy Monkey','Harmattan','Headland One',
    'Hedvig Letters Sans','Hedvig Letters Serif','Heebo','Henny Penny','Hepta Slab',
    'Herr Von Muellerhoff','Hi Melody','Higuen Serif','Hina Mincho','Hind',
    'Hind Guntur','Hind Madurai','Hind Siliguri','Hind Vadodara','Holtwood One SC',
    'Homemade Apple','Homenaje','Hubballi','Hurricane','Hypnerotomachia',
    'IBM Plex Mono','IBM Plex Sans','IBM Plex Sans Arabic','IBM Plex Sans Condensed',
    'IBM Plex Sans Devanagari','IBM Plex Sans Hebrew','IBM Plex Sans JP',
    'IBM Plex Sans KR','IBM Plex Sans Thai','IBM Plex Sans Thai Looped',
    'IBM Plex Serif','IM Fell DW Pica','IM Fell DW Pica SC','IM Fell Double Pica',
    'IM Fell Double Pica SC','IM Fell English','IM Fell English SC','IM Fell French Canon',
    'IM Fell French Canon SC','IM Fell Great Primer','IM Fell Great Primer SC',
    'Ibarra Real Nova','Iceberg','Iceland','Imbue','Imperial Script','Imprima',
    'Inconsolata','Inder','Indie Flower','Ingrid Darling','Inknut Antiqua',
    'Inria Sans','Inria Serif','Inspiration','Instrument Sans','Instrument Serif',
    'Inter','Inter Tight','Irish Grover','Island Moments','Istok Web','Italiana',
    'Italianno','Itim',
    'Jacques Francois','Jacques Francois Shadow','Jaldi','Jaro','Jersey 10',
    'Jersey 10 Charted','Jersey 15','Jersey 15 Charted','Jersey 20','Jersey 20 Charted',
    'Jersey 25','Jersey 25 Charted','JetBrains Mono','Jim Nightshade','Joan',
    'Jockey One','Jolly Lodger','Jomhuria','Jomolhari','Josefin Sans','Josefin Slab',
    'Jost','Joti One','Jua','Judson','Julee','Julius Sans One','Junge','Jura',
    'Just Another Hand','Just Me Again Down Here',
    'K2D','Kablammo','Kadwa','Kaisei Decol','Kaisei HarunoUmi','Kaisei Opti',
    'Kaisei Tokumin','Kalam','Kalnia','Kameron','Kanit','Kantumruy Pro','Karantina',
    'Karma','Katibeh','Kaushan Script','Kavivanar','Kavoon','Kay Pho Du','Keania One',
    'Kelly Slab','Kenia','Khand','Khmer','Khula','Kings','Kirang Haerang',
    'Kite One','Kiwi Maru','Klee One','Knewave','KoHo','Kodchasan','Koh Santepheap',
    'Kolker Brush','Kosugi','Kosugi Maru','Koulen','Kranky','Kreon','Kristi',
    'Krona One','Kufam','Kulim Park','Kumar One','Kumar One Outline','Kumbh Sans',
    'Kurale',
    'La Belle Aurore','Labrada','Lacquer','Laila','Lakki Reddy','Lalezar','Lancelot',
    'Lateef','Lato','Lavishly Yours','League Gothic','League Script','League Spartan',
    'Leckerli One','Ledger','Lekton','Lemon','Lemonada','Lexend','Lexend Deca',
    'Lexend Exa','Lexend Giga','Lexend Mega','Lexend Peta','Lexend Tera','Lexend Zetta',
    'Libre Barcode 128','Libre Barcode 128 Text','Libre Barcode 39','Libre Barcode 39 Extended',
    'Libre Barcode 39 Extended Text','Libre Barcode 39 Text','Libre Barcode EAN13 Text',
    'Libre Baskerville','Libre Bodoni','Libre Caslon Display','Libre Caslon Text',
    'Libre Franklin','Licorice','Life Savers','Lilita One','Lily Script One',
    'Limelight','Linden Hill','Literata','Liu Jian Mao Cao','Livvic','Lobster',
    'Lobster Two','Londrina Outline','Londrina Shadow','Londrina Sketch','Londrina Solid',
    'Long Cang','Lora','Love Light','Love Ya Like A Sister','Loved by the King',
    'Lovers Quarrel','Luckiest Guy','Lugrasimo','Lumanosimo','Lunasima','Lusitana',
    'Lustria','Luxurious Roman','Luxurious Script',
    'M PLUS 1','M PLUS 1 Code','M PLUS 1p','M PLUS 2','M PLUS Code Latin','M PLUS Rounded 1c',
    'Ma Shan Zheng','Macondo','Macondo Swash Caps','Mada','Magra','Maiden Orange',
    'Maitree','Major Mono Display','Mako','Mali','Mallanna','Mandali','Manjari',
    'Manrope','Mansalva','Manuale','Marcellus','Marcellus SC','Marck Script',
    'Margarine','Marhey','Markazi Text','Marko One','Marmelad','Martel',
    'Martel Sans','Marvel','Mate','Mate SC','Maven Pro','McLaren','Mea Culpa',
    'Meddon','MedievalSharp','Medula One','Meera Inimai','Megrim','Meie Script',
    'Meow Script','Merienda','Merriweather','Merriweather Sans','Metal','Metal Mania',
    'Metamorphous','Metrophobic','Michroma','Milonga','Miltonian','Miltonian Tattoo',
    'Mina','Mingzat','Mitr','Mochiy Pop One','Mochiy Pop P One','Modak','Modern Antiqua',
    'Mogra','Mohave','Moirai One','Molengo','Molle','Monda','Monofett','Monomaniac One',
    'Monoton','Monsieur La Doulaise','Montaga','Montagu Slab','MonteCarlo','Montez',
    'Montserrat','Montserrat Alternates','Montserrat Subrayada','Moo Lah Lah',
    'Mooli','Moon Dance','Moul','Moulpali','Mountains of Christmas','Mouse Memoirs',
    'Mr Bedfort','Mr Dafoe','Mr De Haviland','Mrs Saint Delafield','Mrs Sheppards',
    'Mukta','Mukta Mahee','Mukta Malar','Mukta Vaani','Mulish','Murecho',
    'MuseoModerno','My Soul','Mynerve','Mystery Quest',
    'NTR','Nabla','Nanum Brush Script','Nanum Gothic','Nanum Gothic Coding',
    'Nanum Myeongjo','Nanum Pen Script','Neonderthaw','Nerko One','Neucha',
    'Neuton','New Rocker','New Tegomin','News Cycle','Newsreader','Niconne',
    'Niramit','Nixie One','Nobile','Nokora','Norican','Nosifer','Notable',
    'Nothing You Could Do','Noticia Text','Noto Color Emoji','Noto Emoji',
    'Noto Kufi Arabic','Noto Music','Noto Naskh Arabic','Noto Nastaliq Urdu',
    'Noto Rashi Hebrew','Noto Sans','Noto Sans HK','Noto Sans JP','Noto Sans KR',
    'Noto Sans SC','Noto Sans TC','Noto Serif','Noto Serif HK','Noto Serif JP',
    'Noto Serif KR','Noto Serif SC','Noto Serif TC','Nova Cut','Nova Flat','Nova Mono',
    'Nova Oval','Nova Round','Nova Script','Nova Slim','Nova Square','Numans','Nunito',
    'Nunito Sans',
    'Odibee Sans','Odor Mean Chey','Offside','Oi','Ojuju','Ole','Oleo Script',
    'Oleo Script Swash Caps','Onest','Oooh Baby','Open Sans','Oranienbaum','Orbit',
    'Orbitron','Oregano','Orienta','Original Surfer','Oswald','Outfit','Over the Rainbow',
    'Overlock','Overlock SC','Overpass','Overpass Mono','Ovo','Oxanium','Oxygen',
    'Oxygen Mono',
    'PT Mono','PT Sans','PT Sans Caption','PT Sans Narrow','PT Serif','PT Serif Caption',
    'Pacifico','Padauk','Padyakke Expanded One','Palanquin','Palanquin Dark',
    'Palette Mosaic','Pangolin','Paprika','Parisienne','Passero One','Passion One',
    'Passions Conflict','Pathway Extreme','Pathway Gothic One','Patrick Hand',
    'Patrick Hand SC','Pattaya','Patua One','Pavanam','Paytone One','Peddana',
    'Peralta','Permanent Marker','Petemoss','Petit Formal Script','Petrona','Philosopher',
    'Phudu','Piazzolla','Piedra','Pinyon Script','Pirata One','Pixelify Sans','Plaster',
    'Platypi','Play','Playball','Playfair Display','Playfair Display SC','Playpen Sans',
    'Plus Jakarta Sans','Podkova','Poiret One','Poller One','Poltawski Nowy',
    'Poly','Pompiere','Pontano Sans','Poor Story','Poppins','Port Lligat Sans',
    'Port Lligat Slab','Potta One','Pragati Narrow','Praise','Prata','Preahvihear',
    'Press Start 2P','Pridi','Princess Sofia','Prociono','Prompt','Prosto One',
    'Proza Libre','Public Sans','Puppies Play','Puritan','Purple Purse',
    'Qahiri','Quando','Quantico','Quattrocento','Quattrocento Sans','Questrial',
    'Quicksand','Quintessential','Qwitcher Grypen',
    'Racing Sans One','Radio Canada','Radio Canada Big','Rajdhani','Rakkas',
    'Raleway','Raleway Dots','Ramabhadra','Ramaraja','Rambla','Rammetto One',
    'Rampart One','Rancho','Ranga','Rasa','Rationale','Ravi Prakash','Readex Pro',
    'Recursive','Red Hat Display','Red Hat Mono','Red Hat Text','Red Rose',
    'Redacted','Redacted Script','Reem Kufi','Reem Kufi Fun','Reem Kufi Ink',
    'Reenie Beanie','Reggae One','Rethink Sans','Revalia','Rhodium Libre',
    'Ribeye','Ribeye Marrow','Righteous','Risque','Road Rage','Roboto','Roboto Condensed',
    'Roboto Flex','Roboto Mono','Roboto Serif','Roboto Slab','Rochester','Rock 3D',
    'Rock Salt','RocknRoll One','Rokkitt','Romanesco','Ropa Sans','Rosario',
    'Rosarivo','Rouge Script','Rowdies','Rozha One','Rubik','Rubik 80s Fade',
    'Rubik Beastly','Rubik Bubbles','Rubik Burned','Rubik Dirt','Rubik Distressed',
    'Rubik Doodle Shadow','Rubik Doodle Triangles','Rubik Gemstones','Rubik Glitch',
    'Rubik Glitch Pop','Rubik Iso','Rubik Maps','Rubik Maze','Rubik Microbe',
    'Rubik Mono One','Rubik Moonrocks','Rubik One','Rubik Pixels','Rubik Puddles',
    'Rubik Scribble','Rubik Spray Paint','Rubik Storm','Rubik Vinyl','Rubik Wet Paint',
    'Ruda','Rufina','Ruge Boogie','Ruluko','Rum Raisin','Ruslan Display','Russo One',
    'Ruthie','Rye',
    'STIX Two Text','Sacramento','Sahitya','Sail','Saira','Saira Condensed',
    'Saira Extra Condensed','Saira Semi Condensed','Saira Stencil One','Salsa',
    'Sanchez','Sancreek','Sansita','Sansita Swashed','Sarabun','Sarala',
    'Sarina','Sarpanch','Sassy Frass','Satisfy','Sawarabi Gothic','Sawarabi Mincho',
    'Scada','Scheherazade New','Scheherazade New','Schibsted Grotesk','Secular One',
    'Sedgwick Ave','Sedgwick Ave Display','Sen','Send Flowers','Sevillana',
    'Seymour One','Shadows Into Light','Shadows Into Light Two','Shalimar',
    'Shantell Sans','Shanti','Share','Share Tech','Share Tech Mono','Shippori Antique',
    'Shippori Antique B1','Shippori Mincho','Shippori Mincho B1','Shojumaru',
    'Short Stack','Shrikhand','Siemreap','Sigmar','Sigmar One','Signika',
    'Signika Negative','Simonetta','Single Day','Sintony','Sirin Stencil',
    'Six Caps','Sixtyfour','Sixtyfour Convergence','Skranji','Slabo 13px',
    'Slabo 27px','Slackey','Slackside One','Smokum','Smooch','Smooch Sans',
    'Smythe','Sniglet','Snippet','Snowburst One','Sofadi One','Sofia','Sofia Sans',
    'Sofia Sans Condensed','Sofia Sans Extra Condensed','Sofia Sans Semi Condensed',
    'Solitreo','Solway','Song Myung','Sono','Sonsie One','Sora','Sorts Mill Goudy',
    'Source Code Pro','Source Sans 3','Source Serif 4','Space Grotesk','Space Mono',
    'Special Elite','Spectral','Spectral SC','Spicy Rice','Spinnaker','Spirax',
    'Splash','Spline Sans','Spline Sans Mono','Squadaone','Square Peg','Sree Krushnadevaraya',
    'Sriracha','Srisakdi','Staatliches','Stalemate','Stalinist One','Stardos Stencil',
    'Stick','Stick No Bills','Stint Ultra Condensed','Stint Ultra Expanded',
    'Stoke','Strait','Style Script','Sue Ellen Francisco','Sulphur Point',
    'Sumana','Sunshiney','Supermercado One','Sura','Suranna','Suravaram',
    'Suwannaphum','Swanky and Moo Moo','Syncopate',
    'Tai Heritage Pro','Tajawal','Tangerine','Tapestry','Taprom','Tatra',
    'Teko','Telex','Tenali Ramakrishna','Tenor Sans','Text Me One','Thasadith',
    'The Girl Next Door','The Nautigal','Tienne','Tillana','Tilt Neon','Tilt Prism',
    'Tilt Warp','Timmana','Tinos','Tiro Bangla','Tiro Devanagari Hindi',
    'Tiro Devanagari Marathi','Tiro Devanagari Sanskrit','Tiro Gurmukhi',
    'Tiro Kannada','Tiro Tamil','Tiro Telugu','Titan One','Titillium Web',
    'Tomorrow','Tourney','Trade Winds','Trirong','Trispace','Trocchi','Trochut',
    'Truculenta','Trykker','Tsukimi Rounded','Tulpen One','Turret Road','Twinkle Star',
    'Ubuntu','Ubuntu Condensed','Ubuntu Mono','Ubuntu Sans','Ubuntu Sans Mono',
    'Uchen','Ultra','Unbounded','Unna','Updock','Urbanist',
    'VT323','Vampiro One','Varela','Varela Round','Varta','Vesper Libre','Vibes',
    'Vibur','Viga','Vidaloka','Viga','Viksit','Vina Sans','Voces','Volkhov',
    'Vollkorn','Vollkorn SC','Voltaire',
    'Waiting for the Sunrise','Wallpoet','Walter Turncoat','Warnes','Water Brush',
    'Waterfall','Wellfleet','Wendy One','WindSong','Wire One','Wix Madefor Display',
    'Wix Madefor Text','Work Sans',
    'Xanh Mono','Yanone Kaffeesatz','Yantramanav','Yarndings 12','Yarndings 20',
    'Yatra One','Yellowtail','Yeon Sung','Yeseva One','Yesteryear','Yomogi',
    'Young Serif','Yrsa','Yuji Boku','Yuji Hentaigana Akari','Yuji Hentaigana Akebono',
    'Yuji Mai','Yuji Syuku','Yusei Magic',
    'ZCOOL KuaiLe','ZCOOL QingKe HuangYou','ZCOOL XiaoWei','Zain','Zen Antique',
    'Zen Antique Soft','Zen Dots','Zen Kaku Gothic Antique','Zen Kaku Gothic New',
    'Zen Kurenaido','Zen Loop','Zen Maru Gothic','Zen Old Mincho','Zen Tokyo Zoo',
    'Zeyada','Zhi Mang Xing','Zilla Slab','Zilla Slab Highlight',
];

// Uploaded custom fonts (populated at runtime when user uploads a font file)
let CUSTOM_FONTS = [];

function initTypographyPanel(cfg, gjsEditor) {
    const TAGS  = ['h1','h2','h3','h4','h5','h6','p'];
    const PROPS = [
        { key: 'font_family',     label: 'Font Family',      type: 'font',   full: true },
        { key: 'font_size',       label: 'Font Size',        type: 'text',   placeholder: 'e.g. 2rem' },
        { key: 'font_weight',     label: 'Font Weight',      type: 'select',
          options: ['','100','200','300','400','500','600','700','800','900','bold','bolder','lighter'] },
        { key: 'font_style',      label: 'Font Style',       type: 'select',
          options: ['','normal','italic','oblique'] },
        { key: 'line_height',     label: 'Line Height',      type: 'text',   placeholder: 'e.g. 1.5' },
        { key: 'letter_spacing',  label: 'Letter Spacing',   type: 'text',   placeholder: 'e.g. 0.05em' },
        { key: 'color',           label: 'Color',            type: 'color',  full: true },
        { key: 'text_transform',  label: 'Text Transform',   type: 'select',
          options: ['','none','uppercase','lowercase','capitalize'] },
        { key: 'text_decoration', label: 'Text Decoration',  type: 'select',
          options: ['','none','underline','line-through','overline'] },
    ];

    const panel    = document.getElementById('ve-typo-panel');
    const btn      = document.getElementById('ve-typo-btn');
    const closeBtn = document.getElementById('ve-typo-close');
    const saveBtn  = document.getElementById('ve-typo-save');
    const msg      = document.getElementById('ve-typo-msg');
    const editor   = document.getElementById('ve-editor');

    if (!panel || !btn) return;

    // State
    let typoData  = { global: {}, page: {} };
    let globalTag = 'h1';
    let pageTag   = 'h1';

    // ── Open / close ──────────────────────────────────────────
    btn.addEventListener('click', () => {
        const isOpen = panel.classList.toggle('open');
        btn.classList.toggle('active', isOpen);
        editor.classList.toggle('typo-open', isOpen);
        if (isOpen && !typoData._loaded) loadTypography();
    });
    closeBtn.addEventListener('click', () => {
        panel.classList.remove('open');
        btn.classList.remove('active');
        editor.classList.remove('typo-open');
    });

    // ── Auto-switch tab when a canvas element is selected ─────
    gjsEditor.on('component:selected', component => {
        if (!panel.classList.contains('open')) return;
        const tag = (component.get('tagName') || '').toLowerCase();
        if (!TAGS.includes(tag)) return;
        globalTag = tag;
        pageTag   = tag;
        renderTabs('global');
        renderTabs('page');
        renderFields('global', globalTag);
        renderFields('page', pageTag);
    });

    // ── Scan canvas iframe for current computed styles ────────
    const CANVAS_PROP_MAP = {
        font_family:     'fontFamily',
        font_size:       'fontSize',
        font_weight:     'fontWeight',
        font_style:      'fontStyle',
        line_height:     'lineHeight',
        letter_spacing:  'letterSpacing',
        color:           'color',
        text_transform:  'textTransform',
        text_decoration: 'textDecoration',
    };

    function scanCanvas() {
        try {
            const iframeDoc = gjsEditor.Canvas.getFrameEl().contentDocument;
            if (!iframeDoc) return {};
            const result = {};
            TAGS.forEach(tag => {
                const el = iframeDoc.querySelector(tag);
                if (!el) return;
                const cs = iframeDoc.defaultView.getComputedStyle(el);
                const tagData = {};
                Object.entries(CANVAS_PROP_MAP).forEach(([key, cssProp]) => {
                    const val = (cs[cssProp] || '').trim();
                    if (val && val !== 'normal' && val !== 'none' && val !== '0px') {
                        tagData[key] = val;
                    }
                });
                if (Object.keys(tagData).length) result[tag] = tagData;
            });
            return result;
        } catch (_) {
            return {};
        }
    }

    // ── Load from server ──────────────────────────────────────
    function loadTypography() {
        fetch(cfg.typographyUrl, { headers: { Accept: 'application/json' } })
            .then(r => r.json())
            .then(data => {
                const canvas = scanCanvas();
                // Pre-fill empty global fields with canvas-detected values so the
                // panel shows what's actually applied, not just saved overrides.
                TAGS.forEach(tag => {
                    const saved  = (data.global && data.global[tag]) || {};
                    const detect = canvas[tag] || {};
                    if (Object.keys(saved).length === 0 && Object.keys(detect).length > 0) {
                        if (!data.global) data.global = {};
                        data.global[tag] = { ...detect, _detected: true };
                    }
                });
                typoData = { ...data, _loaded: true };
                renderTabs('global');
                renderTabs('page');
                renderFields('global', globalTag);
                renderFields('page', pageTag);
            })
            .catch(() => {});
    }

    // ── Tabs ──────────────────────────────────────────────────
    function renderTabs(scope) {
        const container = document.getElementById('ve-typo-' + scope + '-tabs');
        if (!container) return;
        container.innerHTML = '';
        TAGS.forEach(tag => {
            const b = document.createElement('button');
            b.className = 've-typo-tab' + (tag === (scope === 'global' ? globalTag : pageTag) ? ' active' : '');
            b.textContent = tag.toUpperCase();
            b.addEventListener('click', () => {
                if (scope === 'global') globalTag = tag; else pageTag = tag;
                renderTabs(scope);
                renderFields(scope, tag);
            });
            container.appendChild(b);
        });
    }

    // ── Fields ────────────────────────────────────────────────
    function renderFields(scope, tag) {
        const container = document.getElementById('ve-typo-' + scope + '-fields');
        if (!container) return;
        container.innerHTML = '';

        const values   = (typoData[scope] && typoData[scope][tag]) || {};
        const isPage   = scope === 'page';
        const override = isPage ? !!values.override : true;

        if (isPage) {
            const row = document.createElement('div');
            row.className = 've-typo-override';
            const cb  = document.createElement('input');
            cb.type = 'checkbox'; cb.id = 've-typo-override-cb'; cb.checked = override;
            cb.addEventListener('change', () => {
                setVal(scope, tag, 'override', cb.checked);
                renderFields(scope, tag);
            });
            const lbl = document.createElement('label');
            lbl.htmlFor = 've-typo-override-cb';
            lbl.textContent = 'Override global for ' + tag.toUpperCase();
            row.appendChild(cb); row.appendChild(lbl);
            container.appendChild(row);
        }

        const grid = document.createElement('div');
        grid.className = 've-typo-grid';

        PROPS.forEach(prop => {
            const cell = document.createElement('div');
            cell.className = 've-typo-field' + (prop.full ? ' full' : '');

            const label = document.createElement('label');
            label.textContent = prop.label;
            if (values._detected && values[prop.key]) {
                const badge = document.createElement('span');
                badge.textContent = ' detected';
                badge.style.cssText = 'font-size:.6rem;color:#7c3aed;margin-left:4px;opacity:.8;font-weight:400;';
                label.appendChild(badge);
            }
            cell.appendChild(label);

            if (prop.type === 'font') {
                const wrap = document.createElement('div');
                wrap.className = 've-typo-font-wrap';

                const inp = document.createElement('input');
                inp.type = 'text';
                inp.value = values[prop.key] || '';
                inp.placeholder = "e.g. 'Inter', sans-serif";
                inp.disabled = isPage && !override;

                const acList = document.createElement('div');
                acList.className = 've-typo-ac-list';
                let acActive = -1;

                function showAc(query) {
                    const q = query.toLowerCase().trim();
                    if (!q) { acList.classList.remove('open'); return; }
                    const allFonts = [...CUSTOM_FONTS, ...GOOGLE_FONTS];
                    const matches = allFonts.filter(f => f.toLowerCase().startsWith(q))
                        .concat(allFonts.filter(f => !f.toLowerCase().startsWith(q) && f.toLowerCase().includes(q)))
                        .slice(0, 12);
                    if (!matches.length) { acList.classList.remove('open'); return; }
                    acList.innerHTML = '';
                    acActive = -1;
                    matches.forEach(font => {
                        const item = document.createElement('div');
                        item.className = 've-typo-ac-item';
                        item.textContent = font;
                        item.addEventListener('mousedown', e => {
                            e.preventDefault();
                            pickFont(font, inp);
                            acList.classList.remove('open');
                        });
                        acList.appendChild(item);
                    });
                    acList.classList.add('open');
                }

                inp.addEventListener('input', () => {
                    setVal(scope, tag, prop.key, inp.value);
                    showAc(inp.value);
                });
                inp.addEventListener('keydown', e => {
                    const items = acList.querySelectorAll('.ve-typo-ac-item');
                    if (e.key === 'ArrowDown') { e.preventDefault(); acActive = Math.min(acActive + 1, items.length - 1); items.forEach((el, i) => el.classList.toggle('active', i === acActive)); }
                    else if (e.key === 'ArrowUp') { e.preventDefault(); acActive = Math.max(acActive - 1, 0); items.forEach((el, i) => el.classList.toggle('active', i === acActive)); }
                    else if (e.key === 'Enter' && acActive >= 0) { e.preventDefault(); pickFont(items[acActive].textContent, inp); acList.classList.remove('open'); }
                    else if (e.key === 'Escape') { acList.classList.remove('open'); }
                });
                inp.addEventListener('blur', () => setTimeout(() => acList.classList.remove('open'), 150));

                wrap.appendChild(inp);
                wrap.appendChild(acList);

                // Hidden file input for font uploads
                const fontFileInput = document.createElement('input');
                fontFileInput.type = 'file';
                fontFileInput.accept = '.ttf,.otf,.woff,.woff2';
                fontFileInput.style.display = 'none';
                fontFileInput.addEventListener('change', () => {
                    const file = fontFileInput.files && fontFileInput.files[0];
                    if (!file) return;
                    uploadFontFile(file, inp);
                    fontFileInput.value = '';
                });

                const uploadBtn = document.createElement('button');
                uploadBtn.type = 'button';
                uploadBtn.className = 've-typo-font-btn';
                uploadBtn.style.cssText = 'margin-top:4px;width:100%';
                uploadBtn.textContent = 'Upload Font';
                uploadBtn.disabled = isPage && !override;
                uploadBtn.addEventListener('click', () => fontFileInput.click());

                cell.appendChild(wrap);
                cell.appendChild(fontFileInput);
                cell.appendChild(uploadBtn);

            } else if (prop.type === 'color') {
                const row = document.createElement('div');
                row.className = 've-typo-color-row';
                const picker = document.createElement('input');
                picker.type = 'color';
                picker.value = values[prop.key] || '#000000';
                picker.disabled = isPage && !override;
                const text = document.createElement('input');
                text.type = 'text';
                text.value = values[prop.key] || '';
                text.placeholder = '#000000 or rgba(...)';
                text.disabled = isPage && !override;
                picker.addEventListener('input', () => { text.value = picker.value; setVal(scope, tag, prop.key, picker.value); });
                text.addEventListener('input', () => { if (/^#[0-9a-f]{6}$/i.test(text.value)) picker.value = text.value; setVal(scope, tag, prop.key, text.value); });
                row.appendChild(picker); row.appendChild(text);
                cell.appendChild(row);

            } else if (prop.type === 'select') {
                const sel = document.createElement('select');
                sel.disabled = isPage && !override;
                prop.options.forEach(opt => {
                    const o = document.createElement('option');
                    o.value = opt; o.textContent = opt || '— inherit —';
                    if (opt === (values[prop.key] || '')) o.selected = true;
                    sel.appendChild(o);
                });
                sel.addEventListener('change', () => setVal(scope, tag, prop.key, sel.value));
                cell.appendChild(sel);

            } else {
                const inp = document.createElement('input');
                inp.type = 'text';
                inp.value = values[prop.key] || '';
                inp.placeholder = prop.placeholder || '';
                inp.disabled = isPage && !override;
                inp.addEventListener('input', () => setVal(scope, tag, prop.key, inp.value));
                cell.appendChild(inp);
            }

            grid.appendChild(cell);
        });

        container.appendChild(grid);
    }

    function setVal(scope, tag, key, value) {
        if (!typoData[scope]) typoData[scope] = {};
        if (!typoData[scope][tag]) typoData[scope][tag] = {};
        // Once the user edits a field the tag is no longer purely detected
        delete typoData[scope][tag]._detected;
        typoData[scope][tag][key] = value;
    }

    // Strip tags that were only auto-detected (never edited by the user)
    // so we don't persist canvas-inferred values as if the user set them.
    function stripDetected(scopeData) {
        const out = {};
        Object.entries(scopeData).forEach(([tag, vals]) => {
            if (vals && !vals._detected) {
                const { _detected, ...rest } = vals; // eslint-disable-line no-unused-vars
                out[tag] = rest;
            }
        });
        return out;
    }

    // ── Save ──────────────────────────────────────────────────
    saveBtn.addEventListener('click', () => {
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving…';
        msg.textContent = '';
        fetch(cfg.typographyUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': cfg.csrfToken,
            },
            body: JSON.stringify({ global: stripDetected(typoData.global || {}), page: typoData.page || {} }),
        })
        .then(r => r.json())
        .then(() => {
            msg.textContent = 'Saved — reload page to preview changes';
            msg.style.color = '#22c55e';
        })
        .catch(() => {
            msg.textContent = 'Save failed';
            msg.style.color = '#ef4444';
        })
        .finally(() => {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Typography';
        });
    });

    // ── Font helpers ──────────────────────────────────────────
    function pickFont(font, inputEl) {
        const value = "'" + font + "', sans-serif";
        inputEl.value = value;
        inputEl.dispatchEvent(new Event('input'));
    }

    function uploadFontFile(file, inputEl) {
        const statusEl = inputEl.closest('.ve-typo-field').querySelector('.ve-typo-font-btn');
        const origText = statusEl ? statusEl.textContent : '';
        if (statusEl) { statusEl.textContent = 'Uploading…'; statusEl.disabled = true; }

        const form = new FormData();
        form.append('font', file);

        fetch(cfg.fontUploadUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': cfg.csrfToken, Accept: 'application/json' },
            body: form,
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) throw new Error('Upload failed');

            // Register @font-face so the font works immediately on this page
            const style = document.createElement('style');
            style.textContent = `@font-face { font-family: '${data.name}'; src: url('${data.url}') format('${data.ext === 'ttf' ? 'truetype' : data.ext === 'otf' ? 'opentype' : data.ext}'); }`;
            document.head.appendChild(style);

            // Add to custom fonts list so it shows in autocomplete
            if (!CUSTOM_FONTS.includes(data.name)) {
                CUSTOM_FONTS.unshift(data.name);
            }

            // Set the value on the active input
            pickFont(data.name, inputEl);
        })
        .catch(() => {
            alert('Font upload failed. Please try a .ttf, .otf, .woff, or .woff2 file under 5 MB.');
        })
        .finally(() => {
            if (statusEl) { statusEl.textContent = origText; statusEl.disabled = false; }
        });
    }
}
