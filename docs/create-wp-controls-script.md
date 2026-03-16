# Create WP Controls Script

The "Create WP Controls" script is used to quickly and easily add custom, core WP controls to core blocks. Note that while this can be used to add controls to custom blocks, it should only be used with core blocks, as there are better ways to add controls to custom blocks.

## Supported Control Types

- [ToggleControl](https://wordpress.github.io/gutenberg/?path=/docs/components-togglecontrol--docs) / `toggle`: A true/false field that can be used to assign a class or property when the control is toggled.
- [InputControl](https://wordpress.github.io/gutenberg/?path=/docs/components-inputcontrol--docs) (experimental, `type="number"`) / `number`: A number input used to set a numeric property. Uses the InputControl that will supersede NumberControl.
- [SelectControl](https://wordpress.github.io/gutenberg/?path=/docs/components-selectcontrol--docs) / `select`: A normal select box field that can be used to give the user options and assign a property based on the value selected.

## Usage

1. Import the script
```js
import createWPControls from 'utils/create-wp-controls';
```

2. Create your settings object
```js
const settings = {
	attributes: {
		stackingOrder: {
			type: 'string',
		},
	},
	blocks: [ 'core/column' ],
	controls: [
		{
			applyClass: 'tribe-has-stacking-order',
			applyStyleProperty: '--tribe-stacking-order',
			attribute: 'stackingOrder',
			defaultValue: 0,
			helpText: __(
				'The stacking order of the element at mobile breakpoints. This setting only applies if the "Stack on mobile" setting for the Columns block is turned on.',
				'tribe'
			),
			label: __( 'Stacking Order', 'tribe' ),
			type: 'number',
		},
	],
};
```

Let's break this down a little bit:

```js
attributes: {
	stackingOrder: {
		type: 'string',
	},
},
```
> :bulb: First, we're creating an `attributes` object that is defining the attributes we want to add to the block. In this case, we're creating a `stackingOrder` attribute for the `core/column` block. 

```js
blocks: [ 'core/column' ],
```
> :bulb: Next, we define what blocks we want the controls to appear on. In this case we're saying that this control should appear on the `core/column` block.

```js
controls: [
	{
		applyClass: 'tribe-has-stacking-order',
		applyStyleProperty: '--tribe-stacking-order',
		attribute: 'stackingOrder',
		defaultValue: 0,
		helpText: __(
			'The stacking order of the element at mobile breakpoints. This setting only applies if the "Stack on mobile" setting for the Columns block is turned on.',
			'tribe'
		),
		label: __( 'Stacking Order', 'tribe' ),
		type: 'number',
	},
],
```
> :bulb: Lastly, we're defining the controls array that will be used to define what controls we want to create for the block we've defined. In this case we're defining a `number` control with a default value of `0`. The `applyClass` property tells the create control script that when the value is changed from the default, we should apply the `tribe-has-stacking-order` class. The `applyStyleProperty` property tells the create control script that the `--tribe-stacking-order` style property should be set to the value of the control when the default is changed.

> :memo: **Note:** You do not always have to set both the `applyClass` and `applyStyleProperty` properties. You can use one or the other separately. Using them together though can be a powerful tool in order to only apply the style property if the class is applied.

### Class application for all control types

- **Toggle:** When the toggle is on, `applyClass` is added; when off, it is removed.
- **Number:** When the value is not the default, `applyClass` is added; when reset to default, it is removed.
- **Select:** When a non-default value is selected, a class is added in the form `applyClass-{value}` (e.g. `applyClass: 'align-type'` with value `'full-width'` → class `align-type-full-width`). The value is slugified for the class name. When the selection is cleared or set back to default, that class is removed and no other `applyClass-*` from this control remains.

Managed classes are always added/removed in a single pass, so changing a control value never leaves stale classes on the block.

### Conditional visibility (showWhen)

To show a control only when certain block settings are in place (e.g. only when the block is full-width), add a `showWhen` function that receives the block’s `attributes` and returns a boolean:

```js
{
	attribute: 'fullWidthOption',
	label: __( 'Full-width only option', 'tribe' ),
	showWhen: ( attributes ) => attributes.align === 'full',
	type: 'toggle',
	// ...
}
```

When `showWhen` returns `false`, the control is hidden in the sidebar; existing attribute values are still saved and applied (e.g. classes/styles still output).

### Inspector group and panel

Controls can be placed in any [InspectorControls group](https://github.com/WordPress/gutenberg/blob/trunk/packages/block-editor/src/components/inspector-controls/groups.js) (Settings tab, Styles tab, or a specific Styles section like color or dimensions).

- **`group`** (optional): Which sidebar group the control appears in. Omit or use `'default'` / `'settings'` for the Settings tab. Use `'styles'` for the Styles tab. Use `'color'`, `'typography'`, `'dimensions'`, `'border'`, `'background'`, `'position'`, `'effects'`, `'filter'`, `'content'`, `'list'`, `'advanced'`, or `'bindings'` to place the control directly in that section. If not set, the control goes in the **default** group under a panel titled "Custom Block Settings".
- **`panel`** (optional): The title of the panel that wraps the control. Only used when the control is in the **default** or **styles** group (those two groups always use a `PanelBody`). Defaults to "Custom Block Settings". Reuse the same `panel` value on multiple controls to put them in the same collapsible panel; use a different `panel` name to create a separate panel.

**Behavior:**

- **Default / Settings** (`group` omitted or `'default'` / `'settings'`): Controls are rendered inside a `PanelBody` with the given `panel` title (default "Custom Block Settings").
- **Styles** (`group: 'styles'`): Controls are rendered in the Styles tab inside a `PanelBody` with the given `panel` title.
- **Any other group** (e.g. `'color'`, `'dimensions'`): Controls are rendered **directly** in that group with no `PanelBody` wrapper. They are wrapped in a full-width container (`grid-column: 1 / -1`) so they span the group’s grid and don’t get a cramped layout.

**Visibility in Style subgroups:** Subgroups like `typography` or `color` are only shown when the block editor decides to render that section (e.g. when the block has typography/color support and the panel is expanded). If nothing in that group is “active,” the whole section may be collapsed or hidden, and your custom control won’t appear. For controls that should **always** be visible in the Styles tab, use **`group: 'styles'`** with a **`panel`** title instead of a subgroup like `typography` or `color`. That way the control lives in its own panel in the Styles tab and is always shown when the block is selected.

Example: two controls in one panel in Settings, one control in the Styles tab in its own panel:

```js
controls: [
	{ attribute: 'optionA', panel: __( 'Layout', 'tribe' ), type: 'toggle', ... },
	{ attribute: 'optionB', panel: __( 'Layout', 'tribe' ), type: 'toggle', ... },
	{ attribute: 'styleOption', group: 'styles', panel: __( 'Appearance', 'tribe' ), type: 'select', ... },
]
```

Example: one control in the Styles → Dimensions section (no panel):

```js
{ attribute: 'customWidth', group: 'dimensions', type: 'number', ... }
```

3. Call the create controls script and pass in your settings array.

```js
createWPControls( settings );
```

## Limitations

- Because the script currently only supports specific controls, additional time will be required to extend the script if a different type of control needs to be added. This can be done in the `determineControlToRender` function within the script. Make sure to import your control type!
- Currently the script only supports adding a class or a style property. There are no other functions of the script in terms of block output.
- Not really a limitation but I should note that we shouldn't use this script unless it's decided with the project team that these controls should be created using this method. There are other ways that may be better for your projects (block styles, adding classes manually, etc). If you feel strongly that this script gives the client the best experience, go for it!
