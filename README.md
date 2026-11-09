# Study site package

A minimal page theme for TYPO3 14.3 and PHP 8.2+.

## First lesson

- `composer.json` identifies the extension and its dependencies.
- `Configuration/Sets/Main/config.yaml` makes its site set selectable in TYPO3.
- `Configuration/Sets/Main/setup.typoscript` tells TYPO3 to render a Fluid page.
- `Resources/Private/Templates/Pages/Default.fluid.html` contains the page body.

We use `FLUIDTEMPLATE` with an explicit file for this single-template lesson.
Existing pages may still have Camino layouts assigned; `PAGEVIEW` would look
for templates matching those layout names. Layout-based selection comes later.

Installing the package does not activate its set on an existing site.
Select **Study: Simple site package** in **Sites > Setup** when ready to use it.
Use one page theme for that site; replace its previous theme set when switching.

## Main content

In the page properties, choose **Appearance > Backend Layout > Study: Main content and sidebar**.
Then open **Content > Layout**, select that page, and add a **Text** element in
**Main content**. Save and preview the page.

`page.tsconfig` defines Main content (`colPos = 0`, identifier `main`) and Sidebar
(`colPos = 1`, identifier `sidebar`). Add text to either area in **Content > Layout**. The
`page-content` processor loads its records, and `<f:render.contentArea>` renders
them using Fluid Styled Content. Each page displays its own content.

The existing layout identifier is unchanged, so pages already using the Study
layout gain the Sidebar area automatically. `Resources/Public/Css/site.css` uses
a two-column grid on wide screens and stacks the areas on narrow screens.

The greeting remains in the template. No custom PHP controller or database table is needed.

## Navigation

The `menu` processor loads the site root's direct children as `mainMenu`.
Fluid renders their links in page-tree order, using the navigation title when
set, otherwise the page title. The current page has `aria-current="page"` and a
visual highlight. Hidden pages, pages disabled in menus and folders are excluded.
Nested pages are not listed; this lesson has one menu level.

To add a link, create and enable a page directly below the site root, keeping
**Page enabled in menus** switched on. No template edit is needed.

[TYPO3 site-package tutorial](https://docs.typo3.org/m/typo3/tutorial-sitepackage/14.3/en-us/MinimalExample/Index.html)
