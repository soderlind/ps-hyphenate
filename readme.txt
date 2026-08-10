=== PS Hyphenate ===
Contributors: PerS
Tags: hyphenation, soft hyphen, typography, compound words, wrapping
Requires at least: 6.8
Tested up to: 7.0
Requires PHP: 8.3
Stable tag: 1.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Improves text wrapping for long compound words in German, Norwegian, Swedish, Dutch, and other languages.

== Description ==

PS Hyphenate helps long compound words wrap gracefully instead of overflowing their containers.

**How it works:**

1. Enables native CSS hyphenation on frontend content.
2. Inserts soft hyphens from your exception dictionary at render time.
3. Falls back to TeX-pattern hyphenation for 30+ supported locales.

**Safe by design:**

* Never modifies saved post content.
* Skips code, scripts, links, buttons, and other risky elements.
* Server-inserted soft hyphens override browser automatic hyphenation.

**Supported languages:**

German, Norwegian (Bokmål/Nynorsk), Danish, Dutch, Swedish, Icelandic, English, Spanish, French, Italian, Portuguese, Polish, Czech, Slovak, Slovenian, Hungarian, and many more via TeX dictionaries.

== Exception Dictionary ==

Add entries under **Settings → PS Hyphenate → Exception dictionary**.

**Explicit mapping:**

`Donaudampfschifffahrtsgesellschaft=Donau-dampf-schiff-fahrts-gesellschaft`

Use double hyphens when a compound boundary should keep a visible hyphen:

`nb_NO:personvernforordningen=per-son-vern--for-ord-nin-gen`

**Shorthand (hyphenated word is both key and replacement):**

`digitaliserings-organisasjon`

**Locale-prefixed:**

`nb_NO:menneske-rettighets-organisasjon`

Single hyphens in the replacement mark where soft hyphens will be inserted. Double hyphens mark a soft break before a visible compound hyphen.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate **PS Hyphenate**.
3. Configure under **Settings → PS Hyphenate**.

== Frequently Asked Questions ==

= Does this modify my saved content? =

No. Soft hyphens are inserted at render time only.

= Which block types are processed? =

By default: post titles, headings, paragraphs, lists, quotes, tables, and common layout blocks. You can customize this in settings.

= How do I add exceptions for a specific language? =

Prefix the entry with a locale code: `nb_NO:my-hyphen-ated-word`

= Does this plugin update automatically? =

Yes. When installed via Composer or with the vendor folder included, the plugin checks GitHub for new releases and offers updates through the standard WordPress plugin update mechanism.

== Changelog ==

= 1.0.5 =
* Refactor internal string handling to centralize multibyte helpers; no functional changes.

= 1.0.4 =
* Prioritize inserted soft hyphens by marking processed elements, allowing server mode without native CSS hyphenation, preserving visible compound hyphens from double hyphen exceptions, and normalizing duplicate soft hyphen opportunities.

= 1.0.3 =
* Apply frontend hyphenation CSS to paragraph elements inside block and post content.

= 1.0.2 =
* Fix runtime version metadata, updater repository URL, and classic theme title hyphenation.
* Replace block type text input with a local Select2 multi-select and preserve array submissions.

= 1.0.1 =
* Rename plugin to PS Hyphenate.
* Update installation instructions in README.md for clarity and completeness.

= 1.0.0 =
* First stable release.

= 0.1.9 =
* Improved render-time performance with request-local option caches, cheaper cache keys, and a DOM preflight for short content.

= 0.1.8 =
* Server-inserted soft hyphens now take priority over browser automatic hyphenation.

= 0.1.7 =
* Removed editor preview feature.

= 0.1.4 =
* Added TeX-pattern hyphenation fallback for 30+ locales.

= 0.1.3 =
* Added common block types preset.

= 0.1.2 =
* Case-insensitive exception matching with case-preserving output.

= 0.1.1 =
* Fixed block theme title wrapping and locale-prefixed shorthand exceptions.

= 0.1.0 =
* Initial release.