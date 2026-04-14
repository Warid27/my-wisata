# SCSS Setup for MyWisata

This directory contains the SCSS source files for MyWisata's custom styling.

## File Structure

```
scss/
├── main.scss              # Main entry point that imports all SCSS files
├── _variables.scss        # Custom variables and Bootstrap overrides
└── custom/
    ├── _components.scss   # Custom component styles
    ├── _layout.scss      # Layout-specific styles
    └── _utilities.scss   # Utility classes and animations
```

## Installation and Build Process

1. Install Node.js (if not already installed)
2. Install dependencies:
   ```bash
   npm install
   # or with pnpm
   pnpm install
   ```

3. Compile SCSS to CSS:
   ```bash
   # One-time compilation
   npm run build-css
   # or with pnpm
   pnpm build-css
   
   # Watch for changes (development)
   npm run watch-css
   # or with pnpm
   pnpm watch-css
   ```

## Notes

- The compiled CSS is output to `assets/css/style.css`
- Bootstrap's SCSS is imported and customized via variable overrides
- No need to modify Bootstrap's core files
- Deprecation warnings are from Bootstrap's internal code and don't affect functionality

## Customization

### Colors
Edit `scss/_variables.scss` to customize the color scheme:
- `$primary-color`: Light Green (#90EE90)
- `$secondary-color`: Deep Teal (#004040)
- `$tertiary-color`: Pale Greyish (#F0F5F0)
- `$accent-color`: Coral (#FF7F50)

### Adding New Components
1. Create new SCSS files in the `custom/` directory
2. Import them in `main.scss`
3. Run `npm run build-css` to compile

## Notes

- The compiled CSS is output to `assets/css/style.css`
- Bootstrap's SCSS is imported and customized via variable overrides
- No need to modify Bootstrap's core files
