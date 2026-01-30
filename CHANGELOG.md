# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-30
### Added
- Initial public release.
- Core logic to index ACF fields into `post_content` hidden block.
- Admin Settings page to configure Target Post Types and Fields.
- Batch Processing tool (Backfill) to index existing content in 50-item batches.
- Input sanitization for comma-separated lists in settings.