# Change Log
All notable changes to this project will be documented in this file, formatted via [this recommendation](http://keepachangelog.com/).

## [1.3.1]
### Fixed
- Load the GitHub updater outside admin requests so scheduled, CLI, REST, AJAX, and external management checks can register update hooks.
- Add the Update URI plugin header for the GitHub-hosted update source.

## [1.3.0]
### Changed
- Update Plugin Update Checker to 5.7.
- Use the plugin text domain for Category Pages labels.

### Fixed
- Prevent PHP deprecation warnings from malformed cached update data.
- Escape archive title output.
- Validate connected term links before using them in admin and permalink URLs.
- Sanitize SVG icon names and classes.
- Add noopener attributes to dashboard widget external links.

## [1.2.0]
### Added
- Fix styling of "Read More" link in Post Listing block (WP 6.8 styling made it full width)
- Adds dashboard widget with recent tutorials

## [1.1.0]
### Added
- Support for previewing landing pages.
- Adds a warning when h1 added to post content.

## [1.0.2] 
### Added
- Body class for better ad targeting by AdThrive & Mediavine

## [1.0.0] = 2022-08-04
### Added
- Initial release
