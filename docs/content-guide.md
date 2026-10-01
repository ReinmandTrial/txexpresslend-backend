# Content guide

How to edit the TX Express Lend website in the WordPress admin: pages, shared texts, menus and
form submissions. No coding needed.

**Admin:** https://thexpresslend.pp.ua/wp-admin

| I want to… | Go to |
|---|---|
| change a page's text, images or blocks | **Pages** → the page |
| create a new page | **Pages → Add Page** |
| change the header, footer, hero texts, loan amounts or form messages | **Site Settings** |
| change menu links in the header or footer | **Appearance → Menus** |
| see submitted forms | **Leads** |
| upload or replace images | **Media**, or right inside a block |

---

## 1. Pages are built from blocks

Every page, including the home page, is a list of **blocks**. Open a page in **Pages** and you'll see
them under **Page Builder → Blocks**, in the same order as on the site.

- **Open a block** — click its title bar. Collapsed blocks show their type and heading,
  e.g. "FAQ — Frequently Asked Questions".
- **Add a block** — **Add block** at the top or bottom of the list, then pick the type.
- **Reorder** — drag a block by its number on the left.
- **Duplicate or remove** — use the buttons on the right of the block's title bar.
- **Hide without deleting** — switch on **Hide this block** at the bottom of the block. It disappears
  from the site but keeps its content; switch it off to bring it back. Hidden blocks are marked
  *(hidden)* in the list.
- **Save** — **Update** on the right. **Preview Changes** opens the page with your edits before saving.

### Text formatting

- **Plain text fields** keep line breaks: press Enter where a new line should start.
- **Visual editor fields** (with B / I / link buttons) are for short texts. **Bold** is how a phrase
  gets highlighted in the design (darker or semibold), so use it for that.
- **The Text block** has a full editor: headings, lists, links, quotes, tables and images. Use
  **Heading 2** for section headings inside an article.

### Images

Click **Add Image** in a block, then upload a new file or choose one from the Media Library. To
replace an image, hover over it and click the pencil, or remove it with the cross and add another.

- Use JPG or PNG for photos and illustrations, SVG for icons. Keep files under 1 MB if you can.
- Fill in **Alternative Text** for meaningful images (shown to screen readers and search engines).
  Decorative images can leave it empty.

### Anchors (links to a block)

**Anchor** gives a block an address on the page, so a menu item can scroll to it. An anchor
`faq` on the home page makes `https://thexpresslend.pp.ua/#faq` jump straight to that block.
Use lowercase Latin letters, digits and dashes.

The header menu links to these anchors on the home page: `how-it-works`, `cab-transparency`,
`rates-terms`, `texas-compliance`, `faq`. If you remove or rename one of them, update the menu too.

---

## 2. Block types

| Block | What it is | Good to know |
|---|---|---|
| **Hero with Quick Loan** | Top section: heading, intro, key figures and the Quick Loan form | Only the heading and intro are edited in the block; everything else is in **Site Settings → Hero**. Leave the block's fields empty to use the shared texts. If you fill in any of the three title parts, all three are taken from the block. |
| **Loan options (cards)** | Heading and a row of cards with an image | 4 cards fit in a row on desktop. |
| **Steps** | Numbered steps with images on a light background | Numbers 01, 02… are added automatically. |
| **How it works** | Image with notes, and cost components on the right | Separate images for mobile and desktop. |
| **Rates table** | Table of example costs | In column headings press Enter to put the rest on a second line. On phones each row turns into a card. |
| **Eligibility** | A card with requirements and an image next to it | |
| **Regulations (image collage)** | A large card on the left, smaller cards on the right | The large card stretches to the height of the cards next to it. |
| **Callout** | Heading and a highlighted note with an info icon | |
| **FAQ** | Questions and answers | The first question is shown open. |
| **Contacts and contact form** | Opening hours, email, address and the feedback form | Sits right under the previous block, without a gap. Submissions go to **Leads**. |
| **Text** | An article: heading and text | If the heading is empty, the page title is used. |
| **Loan form (full screen)** | The loan application form with the benefits list | Meant to be the only block on its page. Benefits are in **Site Settings → Get Started block**. |
| **Partner form (full screen)** | An empty column for the partner's application form, with the benefits list | Paste the partner's code into **Widget code** and **Code before &lt;/body&gt;** exactly as received. Meant to be the only block on its page. |

Rules of thumb:

- **One form block per page.** Two loan forms or two contact forms on one page will confuse visitors.
- **Every page needs a main heading.** The first **Text** block's heading becomes it; on pages without
  one, the **Loan form** heading or the first **Hero** title does. A page made only of, say, FAQ and
  Steps has no main heading, which is bad for search engines.
- The design was made for specific pages. Any order works, but check unusual combinations with
  **Preview Changes**, e.g. two light-background blocks (Steps, Regulations) one after another.

---

## 3. Creating a page

1. **Pages → Add Page.**
2. Type the title. It's shown in the browser tab and used as the heading of Text and form blocks
   when theirs are empty.
3. Under the title, **Permalink → Edit** sets the address, e.g. `houston-payday-loans` →
   `https://thexpresslend.pp.ua/houston-payday-loans/`. Lowercase Latin letters and dashes.
4. Add blocks. A typical article page is **Hero with Quick Loan** + **Text** (+ **FAQ**).
5. **Publish.** To show the page in the footer or header, add it to a menu (section 5).

A page can be nested under another one (**Page Attributes → Parent**): its address becomes
`/parent/child/`.

---

## 4. Site Settings

Texts shared by all pages. Changes apply everywhere at once.

| Tab | What's there |
|---|---|
| **Header** | Logo (empty = default logo), text of the "Get started" button, and **Loan form page**: where that button and the Quick Loan form lead. |
| **Hero** | Shared texts of the Hero block: heading, intro, key figures, form labels, the items under the form, the illustration. |
| **Loan amounts** | Minimum, maximum, step and default amount for the Quick Loan slider and the loan form dropdown. |
| **Get Started block** | The benefits list next to the Loan form and Partner form blocks. |
| **Footer** | Name, tagline, social links, column titles, disclaimer, copyright (`{year}` becomes the current year). |
| **Forms** | Where lead notifications are emailed, and the messages shown after a form is sent or fails. |

---

## 5. Menus

**Appearance → Menus.** There are three menus: **Header**, **Footer: Company** (first column) and
**Footer: Quick Links** (second column). Use **Select a menu to edit** to switch between them.

- **Add a page** — tick it under **Pages** on the left → **Add to Menu**.
- **Add a link to a block** — **Custom Links**: URL `https://thexpresslend.pp.ua/#faq`, any link text.
- **Reorder** — drag items. Only top-level items are shown on the site; don't nest them.
- **Rename** — open the item and change **Navigation Label**.
- **Save Menu** when done.

Footer column titles are edited in **Site Settings → Footer**, not here.

---

## 6. Leads

**Leads** lists everything sent through the loan form and the contact form, newest first.

- **Filter** by form with the dropdown above the list.
- **Open** a lead to see all fields and the page it was sent from.
- **Delete** a lead with **Move to Trash**, or several at once with the checkboxes and
  **Bulk actions**.

Every lead is also emailed to the address in **Site Settings → Forms** (or the admin email if that's
empty). *Until email sending is set up on the server, these notifications don't go out; the leads
are still saved here.*

Leads hold personal data. Delete the ones you no longer need.

---

## 7. Before the launch

- **Settings → Reading** → untick **Discourage search engines from indexing this site**. Until then
  the site is hidden from Google on purpose.
- **Settings → General** → set a real **Administration Email Address** (lead notifications fall back
  to it).
- Replace the placeholder pages: About Us, Fees and Rates, Marketing Practices, Responsible Lending
  Practices, Terms of Use, Do Not Sell My Personal Information, Privacy Policy and the Quick Links
  pages show "[Placeholder] The text for this page will be added later."
- Check the contact details, social links and disclaimer in **Site Settings** and the Contacts page.

---

## What not to touch

- **SCF → Field Groups.** These define which fields blocks have. Changes there are overwritten
  by the next code update; ask the developer instead.
- **Appearance → Themes** and **Plugins**. The site relies on the TX Express Lend theme and the
  Secure Custom Fields plugin.
