/** Default cell value used when a column has no explicit cell data. */
export const DEFAULT_CELL = { type: 'dash' };

/**
 * Returns the inclusive index range for a category section, or the category
 * that owns a feature row at the given index.
 *
 * @param {Array}  rows  Row objects with `rowType` and `clientId`.
 * @param {number} index Row index to resolve.
 * @return {{ start: number, end: number }} Inclusive section bounds.
 */
export function getSectionBounds( rows, index ) {
	const row = rows[ index ];

	if ( ! row ) {
		return { start: index, end: index };
	}

	if ( row.rowType === 'category' ) {
		let end = index;

		while (
			end + 1 < rows.length &&
			rows[ end + 1 ].rowType === 'feature'
		) {
			end++;
		}

		return { start: index, end };
	}

	let categoryIndex = -1;

	for ( let i = index; i >= 0; i-- ) {
		if ( rows[ i ].rowType === 'category' ) {
			categoryIndex = i;
			break;
		}
	}

	if ( categoryIndex === -1 ) {
		return { start: index, end: index };
	}

	let end = index;

	while ( end + 1 < rows.length && rows[ end + 1 ].rowType === 'feature' ) {
		end++;
	}

	return { start: categoryIndex, end };
}

/**
 * Whether a feature row sits directly under a preceding category row.
 *
 * @param {Array}  rows  Row objects with `rowType`.
 * @param {number} index Row index to check.
 * @return {boolean} True when the row is a nested feature.
 */
export function isNestedFeature( rows, index ) {
	if ( rows[ index ]?.rowType !== 'feature' ) {
		return false;
	}

	for ( let i = index - 1; i >= 0; i-- ) {
		if ( rows[ i ].rowType === 'category' ) {
			return true;
		}
	}

	return false;
}

/**
 * Whether a row is the last item in its visual category section.
 *
 * @param {Array}  rows  Row objects with `rowType`.
 * @param {number} index Row index to check.
 * @return {boolean} True when the next row starts a new section.
 */
export function isLastInSection( rows, index ) {
	const nextRow = rows[ index + 1 ];

	if ( ! nextRow ) {
		return true;
	}

	if ( rows[ index ].rowType === 'category' ) {
		return nextRow.rowType === 'category';
	}

	return nextRow.rowType === 'category';
}

/**
 * Moves a category and its nested feature rows to a new position in the list.
 *
 * @param {Array}  rows        Full row list.
 * @param {number} sourceStart Inclusive start index of the section being moved.
 * @param {number} sourceEnd   Inclusive end index of the section being moved.
 * @param {number} overIndex   Drop target row index from drag-and-drop.
 * @return {Array} Reordered rows, or the original list when the move is a no-op.
 */
export function reorderCategorySection(
	rows,
	sourceStart,
	sourceEnd,
	overIndex
) {
	if ( overIndex >= sourceStart && overIndex <= sourceEnd ) {
		return rows;
	}

	const remaining = rows.filter(
		( _, index ) => index < sourceStart || index > sourceEnd
	);
	const targetBounds = getSectionBounds( rows, overIndex );
	const movingDown = sourceStart < overIndex;
	let insertAt;

	if ( movingDown ) {
		const anchorClientId = rows[ targetBounds.end ].clientId;
		insertAt =
			remaining.findIndex( ( row ) => row.clientId === anchorClientId ) +
			1;
	} else {
		const anchorClientId = rows[ targetBounds.start ].clientId;
		insertAt = remaining.findIndex(
			( row ) => row.clientId === anchorClientId
		);
	}

	if ( insertAt < 0 ) {
		return rows;
	}

	const group = rows.slice( sourceStart, sourceEnd + 1 );

	return [
		...remaining.slice( 0, insertAt ),
		...group,
		...remaining.slice( insertAt ),
	];
}

/**
 * Advances a cell through dash → check → text → dash for inline editing.
 *
 * @param {Object} cell Current cell with optional `type` and `value`.
 * @return {Object} Next cell state for the cycle.
 */
export function cycleCellType( cell ) {
	const type = cell?.type || 'dash';

	if ( type === 'dash' ) {
		return { type: 'check' };
	}

	if ( type === 'check' ) {
		return { type: 'text', value: '' };
	}

	return { type: 'dash' };
}

/**
 * Builds a new column object with sensible defaults and a unique id.
 *
 * @param {number} index     Column position used in the generated id.
 * @param {Object} overrides Attribute values to merge onto the defaults.
 * @return {Object} Column configuration object.
 */
export function createColumn( index, overrides = {} ) {
	return {
		id: `col-${ index }-${ Date.now() }`,
		label: '',
		subtitle: '',
		badge: '',
		ctaLabel: '',
		ctaUrl: '',
		ctaOpensInNewTab: false,
		ctaStyle: 'outlined',
		isHighlighted: false,
		...overrides,
	};
}

/**
 * Pads or trims a cells array so it matches the current column count.
 *
 * @param {Array}  cells       Existing cell values.
 * @param {number} columnCount Target number of columns.
 * @return {Array} Normalized cells array.
 */
export function syncCellsToColumnCount( cells, columnCount ) {
	const next = [ ...( cells || [] ) ];

	while ( next.length < columnCount ) {
		next.push( { ...DEFAULT_CELL } );
	}

	return next.slice( 0, columnCount );
}

/**
 * Returns starter column definitions for a newly inserted comparison table.
 *
 * @param {number} columnCount Number of columns to generate.
 * @return {Array} Default column objects.
 */
export function getDefaultColumns( columnCount = 3 ) {
	const presets = [
		{
			label: 'Starter',
			subtitle: '$29/mo',
			ctaLabel: 'Get Started',
			ctaUrl: '#',
		},
		{
			label: 'Pro',
			subtitle: '$79/mo',
			badge: 'Popular',
			isHighlighted: true,
			ctaLabel: 'Start free trial',
			ctaUrl: '#',
		},
		{
			label: 'Enterprise',
			subtitle: 'Custom',
			ctaLabel: 'Contact sales',
			ctaUrl: '#',
		},
	];

	return Array.from( { length: columnCount }, ( _, index ) =>
		createColumn( index, presets[ index ] || {} )
	);
}

/**
 * Returns inner block templates used to seed a new comparison table.
 *
 * @param {number} columnCount Number of columns each row should include.
 * @return {Array<[string, Object]>} Block name and attribute pairs for `createBlock`.
 */
export function getDefaultRowTemplate( columnCount = 3 ) {
	const dashCells = () =>
		syncCellsToColumnCount( [], columnCount ).map( () => ( {
			...DEFAULT_CELL,
		} ) );

	return [
		[
			'tribe/comparison-row',
			{
				rowType: 'category',
				label: 'Core Features',
				cells: dashCells(),
			},
		],
		[
			'tribe/comparison-row',
			{
				rowType: 'feature',
				label: 'Users',
				cells: syncCellsToColumnCount(
					[
						{ type: 'text', value: '5' },
						{ type: 'text', value: '25' },
						{ type: 'text', value: 'Unlimited' },
					],
					columnCount
				),
			},
		],
		[
			'tribe/comparison-row',
			{
				rowType: 'feature',
				label: 'Storage',
				cells: syncCellsToColumnCount(
					[
						{ type: 'text', value: '10 GB' },
						{ type: 'text', value: '100 GB' },
						{ type: 'text', value: 'Unlimited' },
					],
					columnCount
				),
			},
		],
		[
			'tribe/comparison-row',
			{
				rowType: 'category',
				label: 'Security',
				cells: dashCells(),
			},
		],
		[
			'tribe/comparison-row',
			{
				rowType: 'feature',
				label: 'Single sign-on (SSO)',
				cells: syncCellsToColumnCount(
					[ { type: 'dash' }, { type: 'check' }, { type: 'check' } ],
					columnCount
				),
			},
		],
		[
			'tribe/comparison-row',
			{
				rowType: 'category',
				label: 'Support',
				cells: dashCells(),
			},
		],
		[
			'tribe/comparison-row',
			{
				rowType: 'feature',
				label: 'Priority support',
				cells: syncCellsToColumnCount(
					[ { type: 'dash' }, { type: 'check' }, { type: 'check' } ],
					columnCount
				),
			},
		],
	];
}
