import { __ } from '@wordpress/i18n';
import { createBlock } from '@wordpress/blocks';
import {
	BlockControls,
	InspectorControls,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { ToolbarButton, ToolbarGroup } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	useCallback,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import { seen, unseen } from '@wordpress/icons';

import ColumnBar from './components/ColumnBar';
import ColumnInspectorPanel from './components/ColumnInspectorPanel';
import RowListEditor from './components/RowListEditor';
import TablePreviewPanel from './components/TablePreviewPanel';
import TableSettingsPanel from './components/TableSettingsPanel';
import {
	createColumn,
	cellsEqual,
	getDefaultColumns,
	getDefaultRowTemplate,
	getSectionBounds,
	isCategoryRow,
	mapRowBlockToEditorRow,
	reorderCategorySection,
	syncCellsToColumnCount,
} from './js/utils';

import './editor.pcss';

/**
 * Editor UI for the comparison table block: column bar, row list, preview,
 * and sidebar controls. Row content is edited via child comparison-row blocks.
 *
 * @param {Object}   root0
 * @param {string}   root0.clientId      Block client id.
 * @param {Object}   root0.attributes    Table block attributes.
 * @param {Function} root0.setAttributes Updates table block attributes.
 */
export default function Edit( { clientId, attributes, setAttributes } ) {
	const blockProps = useBlockProps( {
		className: 'b-comparison-table-editor',
	} );
	const {
		insertBlock,
		moveBlockToPosition,
		removeBlocks,
		replaceInnerBlocks,
		selectBlock,
		updateBlockAttributes,
	} = useDispatch( 'core/block-editor' );
	const innerBlocks = useSelect(
		( select ) => select( 'core/block-editor' ).getBlocks( clientId ),
		[ clientId ]
	);
	const rowBlocks = useMemo(
		() =>
			innerBlocks.filter(
				( block ) => block.name === 'tribe/comparison-row'
			),
		[ innerBlocks ]
	);

	const {
		columns,
		showFooterCtas,
		ctaPlacement,
		mobileCardView,
		mobileCardCarousel,
	} = attributes;
	const [ selectedColumnId, setSelectedColumnId ] = useState(
		columns[ 0 ]?.id || ''
	);
	const [ showPreview, setShowPreview ] = useState( false );
	const [ draggingSectionBounds, setDraggingSectionBounds ] =
		useState( null );
	const hasInitialized = useRef( false );
	const previousColumnCount = useRef( columns.length );

	useEffect( () => {
		if ( columns.length === 0 ) {
			return;
		}

		const columnExists = columns.some(
			( column ) => column.id === selectedColumnId
		);

		if ( ! columnExists ) {
			setSelectedColumnId( columns[ 0 ].id );
		}
	}, [ columns, selectedColumnId ] );

	useEffect( () => {
		if ( hasInitialized.current ) {
			return;
		}

		const needsColumns = columns.length === 0;
		const needsRows = innerBlocks.length === 0;

		if ( ! needsColumns && ! needsRows ) {
			hasInitialized.current = true;
			return;
		}

		const columnCount = needsColumns ? 3 : columns.length;

		if ( needsColumns ) {
			const defaultColumns = getDefaultColumns( columnCount );
			setAttributes( { columns: defaultColumns } );
			setSelectedColumnId( defaultColumns[ 0 ]?.id || '' );
		}

		if ( needsRows ) {
			const initialRowBlocks = getDefaultRowTemplate( columnCount ).map(
				( [ blockName, blockAttributes ] ) =>
					createBlock( blockName, blockAttributes )
			);
			replaceInnerBlocks( clientId, initialRowBlocks, false );
		}

		hasInitialized.current = true;
	}, [
		clientId,
		columns.length,
		innerBlocks.length,
		replaceInnerBlocks,
		setAttributes,
	] );

	useEffect( () => {
		const columnCount = columns.length;

		if ( columnCount === previousColumnCount.current ) {
			return;
		}

		previousColumnCount.current = columnCount;

		rowBlocks.forEach( ( block ) => {
			if ( isCategoryRow( block.attributes.rowType ) ) {
				return;
			}

			const nextCells = syncCellsToColumnCount(
				block.attributes.cells,
				columnCount
			);

			if ( ! cellsEqual( nextCells, block.attributes.cells ) ) {
				updateBlockAttributes( block.clientId, {
					cells: nextCells,
				} );
			}
		} );
	}, [ columns.length, rowBlocks, updateBlockAttributes ] );

	const rows = useMemo(
		() =>
			rowBlocks.map( ( block ) =>
				mapRowBlockToEditorRow( block, columns.length )
			),
		[ rowBlocks, columns.length ]
	);

	const previewRows = useMemo(
		() =>
			rows.map( ( { rowType, label, cells } ) => ( {
				rowType,
				label,
				...( isCategoryRow( rowType ) ? {} : { cells } ),
			} ) ),
		[ rows ]
	);

	const selectedColumnIndex = columns.findIndex(
		( column ) => column.id === selectedColumnId
	);
	const selectedColumn =
		selectedColumnIndex >= 0 ? columns[ selectedColumnIndex ] : null;

	const selectedRowClientId = useSelect(
		( select ) => {
			const selected =
				select( 'core/block-editor' ).getSelectedBlockClientId();

			if ( ! selected ) {
				return '';
			}

			const block = select( 'core/block-editor' ).getBlock( selected );

			if ( block?.name !== 'tribe/comparison-row' ) {
				return '';
			}

			const parents =
				select( 'core/block-editor' ).getBlockParents( selected );

			return parents.includes( clientId ) ? selected : '';
		},
		[ clientId ]
	);

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'wp-block-tribe-comparison-table__inner-blocks',
		},
		{
			allowedBlocks: [ 'tribe/comparison-row' ],
			renderAppender: false,
		}
	);

	/**
	 * Merges partial changes into a single column by index.
	 *
	 * @param {number} index   Column index to update.
	 * @param {Object} changes Partial column attribute changes.
	 */
	const updateColumn = ( index, changes ) => {
		const nextColumns = columns.map( ( column, columnIndex ) =>
			columnIndex === index ? { ...column, ...changes } : column
		);

		setAttributes( { columns: nextColumns } );
	};

	/**
	 * Removes a column and the matching cell from every row.
	 *
	 * @param {number} index Column index to remove.
	 */
	const removeColumn = ( index ) => {
		if ( columns.length <= 1 ) {
			return;
		}

		rowBlocks.forEach( ( block ) => {
			if ( isCategoryRow( block.attributes.rowType ) ) {
				return;
			}

			const nextCells = syncCellsToColumnCount(
				block.attributes.cells,
				columns.length
			);
			nextCells.splice( index, 1 );

			updateBlockAttributes( block.clientId, {
				cells: nextCells,
			} );
		} );

		const nextColumns = columns.filter(
			( _, columnIndex ) => columnIndex !== index
		);

		setAttributes( { columns: nextColumns } );
	};

	/**
	 * Adds a new column and matching cells on every row.
	 */
	const addColumn = () => {
		const nextColumn = createColumn( columns.length, {
			label: __( 'New plan', 'tribe' ),
		} );

		setAttributes( {
			columns: [ ...columns, nextColumn ],
		} );
		setSelectedColumnId( nextColumn.id );
	};

	const moveColumn = useCallback(
		( fromIndex, toIndex ) => {
			if (
				fromIndex === toIndex ||
				toIndex < 0 ||
				toIndex >= columns.length
			) {
				return;
			}

			const nextColumns = [ ...columns ];
			const [ moved ] = nextColumns.splice( fromIndex, 1 );
			nextColumns.splice( toIndex, 0, moved );

			rowBlocks.forEach( ( block ) => {
				if ( isCategoryRow( block.attributes.rowType ) ) {
					return;
				}

				const cells = [ ...( block.attributes.cells || [] ) ];
				const [ movedCell ] = cells.splice( fromIndex, 1 );
				cells.splice( toIndex, 0, movedCell || { type: 'dash' } );

				updateBlockAttributes( block.clientId, {
					cells: syncCellsToColumnCount( cells, nextColumns.length ),
				} );
			} );

			setAttributes( { columns: nextColumns } );
		},
		[ columns, rowBlocks, setAttributes, updateBlockAttributes ]
	);

	const handleColumnDragEnd = useCallback(
		( event ) => {
			const { active, over } = event;

			if ( ! over || active.id === over.id ) {
				return;
			}

			const oldIndex = columns.findIndex(
				( column ) => column.id === active.id
			);
			const newIndex = columns.findIndex(
				( column ) => column.id === over.id
			);

			if ( oldIndex === -1 || newIndex === -1 ) {
				return;
			}

			moveColumn( oldIndex, newIndex );
		},
		[ columns, moveColumn ]
	);

	const addRow = useCallback(
		( rowType = 'feature' ) => {
			const newRowAttributes = {
				rowType,
				label:
					rowType === 'category'
						? __( 'New category', 'tribe' )
						: __( 'New feature', 'tribe' ),
			};

			if ( ! isCategoryRow( rowType ) ) {
				newRowAttributes.cells = syncCellsToColumnCount(
					[],
					columns.length
				);
			}

			const newRow = createBlock(
				'tribe/comparison-row',
				newRowAttributes
			);

			insertBlock( newRow, innerBlocks.length, clientId, false );
			selectBlock( newRow.clientId );
		},
		[
			clientId,
			columns.length,
			innerBlocks.length,
			insertBlock,
			selectBlock,
		]
	);

	const addCategoryRow = useCallback( () => {
		addRow( 'category' );
	}, [ addRow ] );

	const selectRow = useCallback(
		( rowClientId ) => {
			selectBlock( rowClientId );
		},
		[ selectBlock ]
	);

	const deleteRow = useCallback(
		( rowClientId ) => {
			removeBlocks( [ rowClientId ], false );

			if ( selectedRowClientId === rowClientId ) {
				selectBlock( clientId );
			}
		},
		[ clientId, removeBlocks, selectBlock, selectedRowClientId ]
	);

	const reorderRows = useCallback(
		( nextRows ) => {
			const nextBlocks = nextRows
				.map( ( row ) =>
					innerBlocks.find(
						( block ) => block.clientId === row.clientId
					)
				)
				.filter( Boolean );

			if ( nextBlocks.length !== innerBlocks.length ) {
				return;
			}

			replaceInnerBlocks( clientId, nextBlocks, false );
		},
		[ clientId, innerBlocks, replaceInnerBlocks ]
	);

	const moveRow = useCallback(
		( fromIndex, toIndex ) => {
			if ( fromIndex === toIndex ) {
				return;
			}

			const block = rowBlocks[ fromIndex ];

			if ( ! block ) {
				return;
			}

			moveBlockToPosition( block.clientId, toIndex, clientId );
		},
		[ clientId, moveBlockToPosition, rowBlocks ]
	);

	const handleRowDragStart = useCallback(
		( event ) => {
			const activeIndex = rows.findIndex(
				( row ) => row.clientId === event.active.id
			);

			if ( activeIndex === -1 ) {
				return;
			}

			const activeRow = rows[ activeIndex ];

			if ( activeRow.rowType !== 'category' ) {
				setDraggingSectionBounds( null );
				return;
			}

			setDraggingSectionBounds( getSectionBounds( rows, activeIndex ) );
		},
		[ rows ]
	);

	const handleRowDragCancel = useCallback( () => {
		setDraggingSectionBounds( null );
	}, [] );

	const handleRowDragEnd = useCallback(
		( event ) => {
			setDraggingSectionBounds( null );

			const { active, over } = event;

			if ( ! over || active.id === over.id ) {
				return;
			}

			const oldIndex = rows.findIndex(
				( row ) => row.clientId === active.id
			);
			const newIndex = rows.findIndex(
				( row ) => row.clientId === over.id
			);

			if ( oldIndex === -1 || newIndex === -1 ) {
				return;
			}

			const activeRow = rows[ oldIndex ];

			if ( activeRow.rowType === 'category' ) {
				const sourceBounds = getSectionBounds( rows, oldIndex );
				const nextRows = reorderCategorySection(
					rows,
					sourceBounds.start,
					sourceBounds.end,
					newIndex
				);
				const orderChanged = nextRows.some(
					( row, index ) => row.clientId !== rows[ index ]?.clientId
				);

				if ( orderChanged ) {
					reorderRows( nextRows );
				}

				return;
			}

			moveRow( oldIndex, newIndex );
		},
		[ moveRow, reorderRows, rows ]
	);

	return (
		<div { ...blockProps }>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarButton
						icon={ showPreview ? unseen : seen }
						label={
							showPreview
								? __( 'Hide preview', 'tribe' )
								: __( 'Preview table', 'tribe' )
						}
						onClick={ () => setShowPreview( ( value ) => ! value ) }
					/>
				</ToolbarGroup>
			</BlockControls>

			<ColumnBar
				columns={ columns }
				selectedColumnId={ selectedColumnId }
				onSelectColumn={ setSelectedColumnId }
				onRemoveColumn={ removeColumn }
				onAddColumn={ addColumn }
				onColumnDragEnd={ handleColumnDragEnd }
			/>

			<RowListEditor
				rows={ rows }
				columns={ columns }
				selectedRowClientId={ selectedRowClientId }
				draggingSectionBounds={ draggingSectionBounds }
				onSelectRow={ selectRow }
				onDeleteRow={ deleteRow }
				onAddRow={ () => addRow( 'feature' ) }
				onAddCategory={ addCategoryRow }
				onRowDragStart={ handleRowDragStart }
				onRowDragEnd={ handleRowDragEnd }
				onRowDragCancel={ handleRowDragCancel }
			/>

			<div { ...innerBlocksProps } />

			{ showPreview && (
				<TablePreviewPanel
					attributes={ attributes }
					previewRows={ previewRows }
				/>
			) }

			<InspectorControls>
				<TableSettingsPanel
					showFooterCtas={ showFooterCtas }
					onChangeShowFooterCtas={ ( value ) =>
						setAttributes( { showFooterCtas: value } )
					}
					ctaPlacement={ ctaPlacement || 'footer' }
					onChangeCtaPlacement={ ( value ) =>
						setAttributes( { ctaPlacement: value } )
					}
					mobileCardView={ mobileCardView }
					onChangeMobileCardView={ ( value ) =>
						setAttributes( {
							mobileCardView: value,
							...( ! value ? { mobileCardCarousel: false } : {} ),
						} )
					}
					mobileCardCarousel={ mobileCardCarousel }
					onChangeMobileCardCarousel={ ( value ) =>
						setAttributes( { mobileCardCarousel: value } )
					}
				/>

				<ColumnInspectorPanel
					selectedColumn={ selectedColumn }
					selectedColumnIndex={ selectedColumnIndex }
					showFooterCtas={ showFooterCtas }
					onUpdateColumn={ updateColumn }
				/>
			</InspectorControls>
		</div>
	);
}
