## Color Picker (Moose)

Provides a predefined color palette and stores the slug (e.g. white, blue) instead of a hex code. Works with ACF via Extended ACF.

### Add a Color Picker field
```php 
use Extended\ACF\Fields\ColorPickerMoose;

ColorPickerMoose::make('Header background color', 'page_header_bg_color')
->colors([
'white' => '#ffffff',
'black' => '#000000',
'blue'  => '#0073aa',
])
->defaultValue('white');
```

`->colors()` accepts a slug => hex map. In order to use theme values leave field empty

`->defaultValue()` sets the default slug.
`->helperText()` add helper text to the field.

### Get the saved value (slug)
```php 
$color_slug = get_field('page_header_bg_color'); // e.g. "white"
```

Use it directly for class-based styling:

```php
echo '<div class="page-header color--' . esc_attr($color_slug) . '"></div>';
```

Notes

Field type key: `color_picker_tribe`.

Stores slug in DB; front end receives the same slug via get_field().

Ensure your CSS defines classes for slugs, e.g. `.color--white`, `.color--blue`, et
