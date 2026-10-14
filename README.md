# Cera-Mik — Installation and website management

The public website is built with HTML, CSS and JavaScript. PHP handles dashboard authentication, content saving and customer enquiries. No database is required. The website and dashboard are in English, with left-to-right layouts.

## Install on cPanel

1. Back up any existing website.
2. Upload and extract Cera-Mik-cPanel.zip inside public_html. Place index.html directly in that directory. Include the hidden .htaccess files.
3. Enable PHP 8.2 or newer and HTTPS. Enable the fileinfo PHP extension for dashboard image uploads.
4. Set upload_max_filesize to at least 6M and post_max_size to at least 8M.
5. Open config.php in cPanel File Manager and copy SETUP_KEY. Visit https://your-domain.com/admin/ and use the key to create a password of at least 12 characters. Setup is available once; subsequent visits require your password.
6. Add confirmed contact details and review the website content. Click Save all changes after editing.
7. Submit a test quote from quote.html and check that it appears in the dashboard. Quote requests do not include image attachments.
8. Confirm that direct access to config.php, data/content.php and includes/defaults.php is blocked. Keep all supplied .htaccess files.
9. PHP needs write access to data, data/uploads and assets/images. Start with directory permissions 755 and file permissions 644; check ownership with your host if necessary. Do not use 777.

## Update an existing installation

Back up data, config.php and assets/images before uploading updates. Preserve your existing config.php, authentication, enquiries and uploaded images. Replace the public HTML pages, admin and api directories, includes, and the JavaScript/CSS files in assets. Refresh the browser cache after uploading.

The included data/content.php contains the translated English content. If you have not customised your content, back up your existing data/content.php and replace that file with this version. If you have customised services, projects or other content, keep your existing data/content.php and translate those entries through the dashboard. Never replace the entire live data directory: that would discard existing enquiries and login information.

## Pages

- index.html: homepage, company overview, featured services, projects, materials and contact form.
- services.html: service listing and service detail pages using ?id=.
- projects.html: project gallery, search and project detail pages using ?id=.
- catalog.html: material filters, saved selections, comparisons and material detail pages using ?id=.
- faq.html: frequently asked questions.
- quote.html: quote request form, with optional project, service or material preselection.
- admin/: dashboard and enquiry management.

## Dashboard

Manage homepage headline, introductory description, company overview, phone, email and WhatsApp. Add, edit, reorder, publish or hide services, projects, materials and FAQs. Upload service, project and material images. Add project gallery images and material specifications. Manage enquiry status and internal notes, and change your password.

Upload images first, then click Save all changes to save and publish your edits. Deleting a project does not automatically delete its image files. Remove unused files only after checking that they are not referenced elsewhere and taking a backup.

Layout, typography, animation settings, homepage slideshow images and some fixed homepage copy are maintained in the HTML/CSS/JavaScript files.

## Enquiries and hosting

Quote requests collect contact information, project location, space type, approximate area, preferred start date and project details. The customer receives a reference after successful submission. The dashboard supports search, status filters and internal notes. Historical private attachments remain accessible only to an authenticated administrator.

GitHub Pages, file previews and HTML-only servers can display the public pages, but cannot run PHP. Dashboard sign-in, content saving and enquiry submission require PHP hosting such as cPanel. A static preview must not be mistaken for a working enquiry service.

## Content and images

Branches are in Dubai and Egypt. No unconfirmed addresses, contact details, completed-project claims or stock information have been added. Images marked Inspiration or Material concept are illustrative, not documented company projects or available product samples. Confirm material sizes, specifications, availability and costs before any agreement.

Saved materials are stored in the visitor's browser. Up to three materials can be compared. The floating WhatsApp link appears only after a valid number with country code is configured.

Demo images are stored locally and were downloaded from Unsplash:

- Living space: https://images.unsplash.com/photo-1600210492486-724fe5c67fb0
- Bathroom: https://images.unsplash.com/photo-1620626011761-996317b8d101
- Pool: https://images.unsplash.com/photo-1576013551627-0cc20b96c2a7

Replace demo images with company-owned project photographs when available. Manrope is loaded through Google Fonts, with Arial as a fallback. Keep regular private backups of data, assets/images and config.php.
