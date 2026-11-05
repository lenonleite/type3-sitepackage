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

This first template only displays a greeting. Content elements, navigation and
CSS will be separate lessons. No custom PHP controller or database table is needed.

[TYPO3 site-package tutorial](https://docs.typo3.org/m/typo3/tutorial-sitepackage/14.3/en-us/MinimalExample/Index.html)
