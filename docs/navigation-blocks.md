# Site Navigation Block System

## Overview

The site navigation system is built using a collection of custom WordPress blocks that provide flexible, accessible, and maintainable navigation structures. This system replaces traditional WordPress menu systems with a more modern block-based approach.

## Block Architecture

### Block Hierarchy

The navigation system consists of four main blocks that work together with a centralized styling approach:

1. **Navigation Wrapper** (`tribe/navigation-wrapper`) - Central styling coordinator and structural container
2. **Mega Menu Item** (`tribe/mega-menu-item`) - Complex dropdown menus
3. **Standard Menu Item** (`tribe/standard-menu-item`) - Simple menu items with optional dropdowns
4. **Navigation Link** (`tribe/navigation-link`) - Individual link items

### CSS Architecture

The styling architecture follows a centralized coordinator pattern:

**Navigation Wrapper** acts as the central styling coordinator that handles:
- Consolidated toggle button styles (shared between mega and standard menu items)
- First-level navigation styling with animated underlines
- Context-specific behavior for Primary vs Utility menus
- Responsive adaptations and mobile behavior

**Individual Blocks** handle only their specific content:
- **Navigation Link**: Base link styling only
- **Mega Menu Item**: Dropdown container and content styling
- **Standard Menu Item**: Basic positioning and submenu wrapper styling

This architecture eliminates code duplication and creates a single source of truth for navigation styling.


## HTML Structure & Implementation


### Primary Menu Structure

```html
<!-- Navigation Wrapper (outer container) -->
<nav class="wp-block-tribe-navigation-wrapper">
  <!-- Template part with enforced ul tag -->
  <ul class="site-header__navigation">
    
    <!-- Mega menu item (complex dropdown) -->
    <li class="wp-block-tribe-mega-menu-item">
      <button class="tribe-mega-menu-item__toggle">Mega Menu Label</button>
      <div class="site-header__mega-menu-item-wrapper">
        <div class="wp-block-group mega-menu-item__dropdown">
          <!-- Complex layout content via patterns/mega-dropdown -->
        </div>
      </div>
    </li>
    
    <!-- Simple menu item (no submenu) -->
    <li class="wp-block-tribe-standard-menu-item">
      <a href="/simple-link" class="wp-block-tribe-navigation-link">Simple Link</a>
    </li>
    
  </ul>
</nav>
```

### Utility Menu Structure

```html
<!-- Navigation Wrapper (outer container) -->
<nav class="wp-block-tribe-navigation-wrapper">
  <!-- Template part with enforced ul tag -->
  <ul class="site-header__utility-menu">
    
    <!-- Standard menu item with submenu -->
    <li class="wp-block-tribe-standard-menu-item">
      <button class="tribe-standard-menu-item__toggle">
        <span class="tribe-standard-menu-item__toggle-label">Menu Label</span>
      </button>
      <div class="site-header__standard-menu-item-wrapper">
        <a href="/link1" class="wp-block-tribe-navigation-link">Link 1</a>
        <a href="/link2" class="wp-block-tribe-navigation-link">Link 2</a>
      </div>
    </li>
    
    <!-- Simple menu item (no submenu) -->
    <li class="wp-block-tribe-standard-menu-item">
      <a href="/simple-link" class="wp-block-tribe-navigation-link">Simple Link</a>
    </li>
    
  </ul>
</nav>
```

## Template Implementation Notes

### Important Template Configuration

1. **Template Part Requirements**: 
   - The `<ul>` tag must be manually enforced in `header.html` template part
   - Use `"tagName":"ul"` attribute in template part configuration
   - Classes `site-header__utility-menu` and `site-header__navigation` are applied to these `<ul>` elements

2. **Block Structure Hierarchy**:
   - Navigation Wrapper provides the `<nav>` container
   - Template part provides the `<ul>` list container
   - Menu item blocks provide `<li>` list items
   - Navigation link blocks provide `<a>` anchor elements

### Editor Experience

1. **Add Navigation Wrapper** - Start with the structural container
2. **Choose Menu Type** - Add either Standard Menu Items or Mega Menu Items
3. **Configure Settings** - Use Inspector Controls for labels and behavior
4. **Add Links** - Use Navigation Link blocks within Standard Menu Items
5. **Design Mega Menus** - Use patterns and inner blocks for complex layouts


## Block Details

### 1. Navigation Wrapper (`tribe/navigation-wrapper`)

**Purpose**: Central styling coordinator that provides semantic structure and handles all first-level navigation styling.

**Location**: `wp-content/themes/core/blocks/tribe/navigation-wrapper/`

**Features**:
- Provides semantic HTML structure with `<nav>` element
- Supports `aria-label` attribute for accessibility
- **Consolidated toggle button styling** for both mega and standard menu items
- **Unified first-level navigation styling** with animated underlines
- **Context-aware styling** for Primary vs Utility menu placement
- Responsive behavior and mobile adaptations
- Acts as the styling foundation for all navigation components

**Styling Responsibilities**:
- Toggle button styles (chevrons, padding, hover states)
- First-level navigation item styling (fonts, colors, underlines)
- Context-specific overrides for different menu types
- Mobile responsive adaptations

**Attributes**:
- `ariaLabel` (string) - Accessible label for the navigation

**Usage**:
```html
<nav aria-label="Main Navigation">
  <!-- Menu items go here -->
</nav>
```

### 2. Mega Menu Item (`tribe/mega-menu-item`)

**Purpose**: Handles mega menu dropdown content and container functionality.

**Location**: `wp-content/themes/core/blocks/tribe/mega-menu-item/`

**Features**:
- Configurable menu toggle label
- Uses pattern template (`patterns/mega-dropdown`) for consistent content structure
- Supports complex layouts and rich content within dropdowns
- **Container queries** for responsive dropdown layouts
- JavaScript interactions for opening/closing mega menus
- Event-driven architecture with custom events

**Styling Scope**:
- **Dropdown content styling only** (first-level button styling handled by Navigation Wrapper)
- Container queries for responsive layouts
- Mega menu wrapper positioning and background

**Attributes**:
- `menuToggleLabel` (string) - Text label for the top-level navigation button

**Template Structure**:
- Uses `patterns/mega-dropdown` pattern for inner content
- Allows for complex layouts with multiple columns, images, and rich content

**JavaScript Interactions**:
- Toggle mega menu visibility on click
- Close on outside click or Escape key
- Mutual exclusivity with other open menus
- Custom event triggers for integration with other components

### 3. Standard Menu Item (`tribe/standard-menu-item`)

**Purpose**: Handles basic menu item positioning and submenu wrapper functionality.

**Location**: `wp-content/themes/core/blocks/tribe/standard-menu-item/`

**Features**:
- Toggle for enabling simple dropdown submenus
- Only allows `tribe/navigation-link` as child blocks
- Configurable menu toggle label when used as dropdown
- **Lightweight positioning and layout** (first-level styling handled by Navigation Wrapper)
- Perfect for simple link lists

**Styling Scope**:
- **Basic positioning and layout only** (first-level button styling handled by Navigation Wrapper)
- Toggle label styling (underline on hover)
- Submenu wrapper positioning and behavior

**Attributes**:
- `hasSubMenu` (boolean) - Enables dropdown functionality
- `menuToggleLabel` (string) - Text label when used as dropdown toggle

**Allowed Child Blocks**:
- `tribe/navigation-link` only

**Editor Controls**:
- Toggle control for "Simple Dropdown" functionality
- Text control for menu toggle label (when dropdown is enabled)

### 4. Navigation Link (`tribe/navigation-link`)

**Purpose**: Provides base link styling with consistent behavior across all navigation contexts.

**Location**: `wp-content/themes/core/blocks/tribe/navigation-link/`

**Features**:
- Rich text editing for link text
- URL configuration with WordPress LinkControl component
- Target/new window option
- **Base link styling only** (context-specific enhancements handled by Navigation Wrapper)
- Consistent foundation across all menu types

**Styling Scope**:
- **Base link styles only**: underline, transitions, focus states
- Context-specific styling (Primary vs Utility menu) handled by Navigation Wrapper

**Attributes**:
- `text` (string) - Link text content
- `url` (string) - Link destination URL
- `target` (boolean) - Open in new window/tab

**Parent Restrictions**:
- Can only be used within `tribe/standard-menu-item` blocks

**Editor Features**:
- Inline text editing
- Link control popover for URL management
- Toolbar controls for link/unlink actions

## Styling Architecture

### Centralized Coordinator Pattern

The navigation system uses a **centralized coordinator pattern** where the Navigation Wrapper acts as the central styling authority:

```
Navigation Wrapper (Coordinator)
├── Toggle Button Styles (shared by mega & standard menu items)
├── First-Level Navigation Styling (animated underlines, hover states)
├── Context-Specific Overrides (Primary vs Utility menu)
└── Responsive Adaptations (mobile behavior)

Individual Blocks (Content-Specific)
├── Navigation Link: Base link styles only
├── Mega Menu Item: Dropdown container & content
└── Standard Menu Item: Basic positioning & submenu wrapper
```

### Key Benefits

**Eliminates Code Duplication**: Shared styles are defined once in Navigation Wrapper
**Single Source of Truth**: Navigation behavior modifications happen in one place
**Clear Separation**: Each block has a specific, well-defined styling responsibility
**Easy Maintenance**: Changes to navigation styling require updates to only one file
**Consistent Experience**: Unified first-level navigation behavior across all menu types

### CSS File Organization

**Navigation Wrapper** (`navigation-wrapper/style.pcss`):
- Toggle button styles (consolidated)
- Primary navigation first-level styles
- Utility menu style overrides
- Responsive behavior

**Individual Block Files**:
- **Navigation Link**: Base link foundation only
- **Mega Menu Item**: Dropdown content and container queries
- **Standard Menu Item**: Simple positioning and submenu wrapper

## JavaScript Architecture

### Event System

The navigation blocks use a custom event-driven architecture for coordination:

**Custom Events**:
- `modern_tribe/mega_menu_open` - Triggered when mega menu opens
- `modern_tribe/standard_menu_open` - Triggered when standard menu opens
- `modern_tribe/close_on_escape` - Triggered on Escape key press
- `modern_tribe/search_open` - Integration with search overlay
- `modern_tribe/off_nav_click` - Click outside navigation area

### State Management

Each menu type maintains its own state:

```javascript
const state = {
  menuActive: false,    // Is any menu currently open
  activeItem: '',       // Reference to currently active menu item
};
```

### Key Functions

**Mega Menu Item** (`mega-menu-item/view.js`):
- `cacheElements()` - Cache DOM references
- `maybeResetMenuItems()` - Close all active menus
- `openMenuItem(wrapper)` - Open specific mega menu
- `closeMenuItem(wrapper)` - Close specific mega menu
- `handleItemToggle(event)` - Handle click events
- `handleClickOutside(event)` - Close on outside clicks

**Standard Menu Item** (`standard-menu-item/view.js`):
- Similar structure to mega menu with lightweight implementation
- Handles simple dropdown functionality
- Integrates with the same event system

### Integration Points

The navigation system integrates with:
- **Masthead Search**: Closes menus when search is opened
- **Mobile Menu**: Coordination with mobile navigation states
- **Keyboard Navigation**: Escape key closes all menus
- **Resize Events**: Responsive behavior handling

## Accessibility Features

### Semantic HTML
- Proper `<nav>` elements with `aria-label`
- Button elements for interactive toggles
- Proper link elements for navigation

### Keyboard Navigation
- Escape key closes all open menus
- Focus management for dropdown interactions
- Screen reader friendly markup

## Development Guidelines

### Working with the Centralized Architecture

#### **Making Navigation Styling Changes**

**For First-Level Navigation Changes**: Edit `navigation-wrapper/style.pcss`
- Toggle button styling (padding, colors, hover states)
- Animated underlines and transitions
- Font sizes and spacing
- Context-specific behavior (Primary vs Utility)

**For Individual Block Content**: Edit respective block files
- `navigation-link/style.pcss`: Base link foundation only
- `mega-menu-item/style.pcss`: Dropdown content and container queries
- `standard-menu-item/style.pcss`: Basic positioning and submenu wrapper

### Customization Points

- **Navigation Wrapper**: Central coordinator for all first-level styling
- **Patterns**: Modify `patterns/mega-dropdown` for mega menu layouts
- **Individual Blocks**: Content-specific styling only
- **Context Selectors**: Use `.site-header__navigation` and `.site-header__utility-menu` for targeting

## File Structure

```
wp-content/themes/core/blocks/tribe/
├── navigation-wrapper/
│   ├── block.json
│   ├── edit.js
│   ├── index.js
│   ├── save.js
│   └── style.pcss
├── mega-menu-item/
│   ├── block.json
│   ├── edit.js
│   ├── editor.pcss
│   ├── index.js
│   ├── save.js
│   ├── style.pcss
│   └── view.js
├── standard-menu-item/
│   ├── block.json
│   ├── edit.js
│   ├── editor.pcss
│   ├── index.js
│   ├── save.js
│   ├── style.pcss
│   └── view.js
└── navigation-link/
    ├── block.json
    ├── edit.js
    ├── editor.pcss
    ├── index.js
    ├── save.js
    └── style.pcss
```
