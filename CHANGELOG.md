# Changelog

All notable changes to PS Hyphenate are documented in this file.

## 1.0.5 - 2026-08-10

- Refactor internal string handling to centralize multibyte helpers; no functional changes.

## 1.0.4

- Prioritize inserted soft hyphens by marking processed elements, allowing server mode without native CSS hyphenation, preserving visible compound hyphens from double hyphen exceptions, and normalizing duplicate soft hyphen opportunities.

## 1.0.3

- Apply frontend hyphenation CSS to paragraph elements inside block and post content.

## 1.0.2

- Fix runtime version metadata, updater repository URL, and classic theme title hyphenation.
- Replace block type text input with a local Select2 multi-select and preserve array submissions.

## 1.0.1

- Rename plugin to PS Hyphenate.
- Update installation instructions in README.md for clarity and completeness.

## 1.0.0

- First stable release.

## 0.1.9

- Improved render-time performance with request-local option caches, cheaper cache keys, and a DOM preflight for short content.

## 0.1.8

- Preferred server-inserted soft hyphens over browser automatic hyphenation when render-time processing is enabled.

## 0.1.7

- Removed editor-only assets, settings, and REST preview support.

## 0.1.6

- Added a selected-block editor sidebar preview that showed server-processed soft hyphen positions without changing saved content.

## 0.1.5

- Added optional block editor hyphenation preview that did not modify saved block content.

## 0.1.4

- Added TeX-pattern hyphenation fallback for supported locales using `org_heigl/hyphenator`.

## 0.1.3

- Prefilled Block types with common title, prose, list, quote, table, and layout blocks.

## 0.1.2

- Preserved original word casing for case-insensitive exception dictionary matches.

## 0.1.1

- Fixed block theme title wrapping and locale-prefixed shorthand exceptions.

## 0.1.0

- Added initial CSS hyphenation and render-time exception support.