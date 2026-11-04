# Oni Lana WordPress Theme

A responsive college/institutional WordPress theme with a modern homepage, admin-managed content and no page-builder dependency.

## Included / upgraded

- Theme identity renamed to **Oni Lana** throughout the theme header, text domain and PHP function prefix.
- Responsive layout with `@media (max-width: 900px)` and `@media (max-width: 600px)`.
- Hero slider + Notice Board in an **80/20 desktop layout**.
- Multiple Notice Board entries managed from WordPress Admin.
- Multiple slides, programmes, stats and events managed from WordPress Admin.
- Homepage popup overlay can be switched on/off in Customizer, with editable title, content, image, button and URL.
- PDF uploader on Posts and Pages via the WordPress Media Library; attached PDF is embedded on the frontend.
- Blog archive template (`archive.php`).
- Social share buttons on blog posts: Facebook, X/Twitter, WhatsApp, LinkedIn and Copy Link; controlled from Customizer.
- Gallery Items custom post type and per-post gallery image selector.
- Bundled starter image assets in `assets/images/`.
- Optional sidebar on blog/archive listings, plus widgets and footer widget areas.
- Configurable preloader: Spinner / Dots / Progress Bar.
- Contact page with Google Maps embed and configurable recipient email using WordPress `wp_mail()`.
- Modern WordPress login styling.
- Installable Android Progressive Web App (PWA) with a school-branded app name and offline message.
- PDF, YouTube and self-hosted video shortcodes.

## Installation

1. Upload the ZIP in **Appearance → Themes → Add New → Upload Theme**.
2. Activate **Oni Lana**.
3. Assign a menu to **Primary Menu** under Appearance → Menus.
4. Add content using the dashboard menus: Slides, Notice Board, Programmes, Stats, Events and Gallery Items.
5. Configure **Appearance → Customize → School Website Settings** using the School Branding, General, Top Bar, Announcement Bar, About Section, Contact & Footer, Homepage Popup, Blog Social Sharing and Notice Board sections.
6. Add widgets under Appearance → Widgets.

## School branding

Each school site can set its own branding under **Appearance → Customize → School Website Settings → School Branding**:

- School name and tagline
- Primary and accent brand colors
- School logo through **Appearance → Customize → Site Identity → Logo**

The fixed designer credit is kept in the theme footer and is not configurable in School Branding.

Set the WordPress Site Title under **Settings → General** as well; WordPress uses it for browser titles and other site metadata.

The About section image can be selected under **Appearance → Customize → School Website Settings → About Section → About Section Image**. If no image is selected, the placeholder remains visible.

The WordPress admin login can be customized under **Appearance → Customize → School Website Settings → Admin Login Page**. Choose an optional login logo and background image, then set the overlay color and transparency (0% is opaque; 100% is transparent). Leave either image unset to use the theme's text branding or gradient background.

## Android app (PWA)

The website can be installed from a supported Android browser and launched in a standalone app window. Use HTTPS on the live site, then set the school app icon under **Appearance → Customize → Site Identity → Site Icon** (a square image of at least 512 × 512 pixels is recommended). The app name comes from the configured School Name. When offline, it displays a simple reconnect message; it does not cache website pages, forms, or application data. On Android, open the live website in Chrome and choose **Install app** or **Add to Home screen** from the browser menu.

## Homepage slider

Create multiple **Slides**. Set a Featured Image, title, body, button text and URL. If no featured image is supplied, the theme uses the bundled `assets/images/campus-hero.svg` as a fallback.

## Notice Board

Create multiple **Notice Board** entries. Each can have a title, body, link and notice date. The homepage displays the configured number in the right-hand 20% notice panel.

## Inline top-bar search

The search icon in the top bar expands a search field in place. Clicking the icon does not leave the current page; WordPress search results open only after a visitor enters a query and submits the form.

## Homepage popup

Go to **Customizer → School Website Settings → Homepage Popup** and switch **Enable Popup** on/off. You can edit:
- Popup title
- Popup content
- Popup image
- Button text
- Button URL

The popup is designed as a centered overlay with a dimmed/blurred background and an X close button, similar in behaviour to the supplied reference screenshot.

## PDF uploader on posts/pages

Edit any post or page. The **PDF Attachment** box lets an editor select a PDF from the Media Library. The selected PDF is embedded above the article content.

Shortcode:
`[oni_pdf id="123"]`

or:
`[oni_pdf url="https://example.com/document.pdf"]`

## Social sharing

Enable/disable under **Customizer → Blog Social Sharing**. Buttons are provided for Facebook, X/Twitter, WhatsApp, LinkedIn and Copy Link.

## Gallery

- The homepage carousel automatically displays the six newest image uploads from the Media Library and advances every five seconds.
- Use **Gallery Items** in the dashboard to create separate albums. The Gallery page shows one cover per album and opens only the selected album in the photo viewer.
- On a normal blog post, use **Post Gallery Images** to select multiple Media Library images.

## Embeds

PDF:
`[oni_embed url="https://example.com/file.pdf" type="pdf"]`

YouTube:
`[oni_embed url="https://www.youtube.com/watch?v=VIDEO_ID" type="youtube"]`

Video:
`[oni_embed url="https://example.com/video.mp4" type="video"]`

The normal WordPress editor also supports supported oEmbed providers.

## Contact

Create a page using the **Contact** template. It includes the `[oni_contact_form]` form. Configure **Contact Email**, address, phone and Google Maps embed URL in Customizer. For reliable production email delivery, configure SMTP on the hosting server.


## Dedicated Gallery System

Gallery frontend assets are separated into `assets/css/gallery.css` and `assets/js/gallery.js` and are conditionally loaded. The lightbox supports previous/next controls, keyboard navigation, Escape to close, captions, counters and touch swipe.

Create **Gallery Items** from the WordPress dashboard and set a Featured Image for each item. Post-specific galleries continue to work from the Post Gallery Images meta box.

## Dedicated Contact CSS & PHP Validation

Contact styles are in `assets/css/contact.css`. Server-side contact validation is in `inc/contact-validator.php`, including nonce verification, required-field validation, email validation, length limits and a honeypot anti-spam field.
