# Cats Educations Plugin

A WordPress plugin that registers custom post types and a Gutenberg block for managing and displaying educations (edukacije) on the Kozmeticki Salon Cats website.

## Requirements

- WordPress 6.0+
- PHP 7.4+

## Installation

1. Copy or clone the `cats-educations-plugin` folder into `wp-content/plugins/`.
2. Activate the plugin in **WordPress Admin > Plugins**.

## Structure

```
cats-educations-plugin/
├── assets/          # CSS and JS assets
├── block.json       # Gutenberg block definition
├── index.js         # Block editor script
└── cats-educations-plugin.php  # Plugin entry point (registers CPTs and block)
```

## Features

- Registers custom post types for educations (supports multiple instructors)
- Provides a Gutenberg block for embedding education listings in pages/posts

## Author

David Vidovic — [davidvidovic.com](https://davidvidovic.com)

## License

GPL-2.0-or-later
