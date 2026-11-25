import { LinkControl } from '@wordpress/block-editor';
import { Button } from '@wordpress/components';
import { useMemo, useRef } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { dragHandle, trash } from '@wordpress/icons';
import { Fieldset, InputControl, Stack } from '@wordpress/ui';
import {
	closestCenter,
	DndContext,
	KeyboardSensor,
	PointerSensor,
	useSensor,
	useSensors,
} from '@dnd-kit/core';
import {
	arrayMove,
	sortableKeyboardCoordinates,
	SortableContext,
	useSortable,
	verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

const createItem = ( id ) => ( {
	id,
	label: '',
	url: '',
	opensInNewTab: false,
} );

function SortableNavigationItem( { item, index, onChange, onRemove } ) {
	const {
		attributes,
		listeners,
		setNodeRef,
		transform,
		transition,
		isDragging,
	} = useSortable( { id: item.id } );
	const link = useMemo(
		() => ( { url: item.url, opensInNewTab: item.opensInNewTab } ),
		[ item.url, item.opensInNewTab ]
	);

	return (
		<tr
			role="row"
			ref={ setNodeRef }
			className="b-inline-navigation__manual-item"
			style={ {
				transform: CSS.Transform.toString( transform ),
				transition,
				opacity: isDragging ? 0.5 : 1,
			} }
		>
			<td className="b-inline-navigation__manual-drag">
				<Button
					className="b-inline-navigation__drag-handle"
					icon={ dragHandle }
					{ ...attributes }
					{ ...listeners }
					aria-label={ sprintf(
						/* translators: %d is the navigation item number. */
						__( 'Reorder navigation item %d', 'tribe' ),
						index + 1
					) }
				/>
			</td>
			<th
				role="rowheader"
				scope="row"
				className="b-inline-navigation__manual-number"
			>
				{ index + 1 }
			</th>
			<td className="b-inline-navigation__manual-label">
				<span
					className="b-inline-navigation__mobile-label"
					aria-hidden="true"
				>
					{ __( 'Link label', 'tribe' ) }
				</span>
				<InputControl
					label={ sprintf(
						/* translators: %d is the navigation item number. */
						__( 'Link label for navigation item %d', 'tribe' ),
						index + 1
					) }
					hideLabelFromVision
					value={ item.label }
					onValueChange={ ( label ) => onChange( { label } ) }
				/>
			</td>
			<td className="b-inline-navigation__manual-url">
				<Fieldset.Root className="b-inline-navigation__link-control">
					<Fieldset.Legend className="screen-reader-text">
						{ sprintf(
							/* translators: %d is the navigation item number. */
							__( 'Link URL for navigation item %d', 'tribe' ),
							index + 1
						) }
					</Fieldset.Legend>
					<span
						className="b-inline-navigation__mobile-label"
						aria-hidden="true"
					>
						{ __( 'Link URL', 'tribe' ) }
					</span>
					<LinkControl
						value={ link }
						onChange={ ( value ) =>
							onChange( {
								url: value.url,
								opensInNewTab: value.opensInNewTab ?? false,
							} )
						}
						onRemove={ () =>
							onChange( { url: '', opensInNewTab: false } )
						}
					/>
				</Fieldset.Root>
			</td>
			<td className="b-inline-navigation__manual-delete">
				<Button
					icon={ trash }
					isDestructive
					aria-label={ sprintf(
						/* translators: %d is the navigation item number. */
						__( 'Remove navigation item %d', 'tribe' ),
						index + 1
					) }
					onClick={ onRemove }
				/>
			</td>
		</tr>
	);
}

export default function ManualNavigationControl( { items, onChange } ) {
	const addButtonRef = useRef();
	const rows = items.length ? items : [ createItem( 1 ) ];
	const sensors = useSensors(
		useSensor( PointerSensor ),
		useSensor( KeyboardSensor, {
			coordinateGetter: sortableKeyboardCoordinates,
		} )
	);

	const handleDragEnd = ( { active, over } ) => {
		if ( ! over || active.id === over.id ) {
			return;
		}

		const from = rows.findIndex( ( item ) => item.id === active.id );
		const to = rows.findIndex( ( item ) => item.id === over.id );
		if ( from !== -1 && to !== -1 ) {
			onChange( arrayMove( rows, from, to ) );
		}
	};

	return (
		<Stack direction="column" gap="xl" align="flex-start">
			<div className="b-inline-navigation__manual-items">
				<DndContext
					sensors={ sensors }
					collisionDetection={ closestCenter }
					onDragEnd={ handleDragEnd }
				>
					<table
						className="b-inline-navigation__manual-table"
						role="table"
						aria-label={ __( 'Navigation items', 'tribe' ) }
					>
						<colgroup>
							<col className="b-inline-navigation__manual-action-column" />
							<col className="b-inline-navigation__manual-number-column" />
							<col />
							<col />
							<col className="b-inline-navigation__manual-action-column" />
						</colgroup>
						<thead>
							<tr role="row">
								<th role="columnheader" scope="col">
									<span className="screen-reader-text">
										{ __( 'Reorder', 'tribe' ) }
									</span>
								</th>
								<th role="columnheader" scope="col">
									#
								</th>
								<th role="columnheader" scope="col">
									{ __( 'Link label', 'tribe' ) }
								</th>
								<th role="columnheader" scope="col">
									{ __( 'Link URL', 'tribe' ) }
								</th>
								<th role="columnheader" scope="col">
									<span className="screen-reader-text">
										{ __( 'Remove', 'tribe' ) }
									</span>
								</th>
							</tr>
						</thead>
						<tbody>
							<SortableContext
								items={ rows.map( ( item ) => item.id ) }
								strategy={ verticalListSortingStrategy }
							>
								{ rows.map( ( item, index ) => (
									<SortableNavigationItem
										key={ item.id }
										item={ item }
										index={ index }
										onChange={ ( changes ) =>
											onChange(
												rows.map( ( row ) =>
													row.id === item.id
														? { ...row, ...changes }
														: row
												)
											)
										}
										onRemove={ () => {
											const remaining = rows.filter(
												( row ) => row.id !== item.id
											);
											onChange(
												remaining.length
													? remaining
													: [
															createItem(
																item.id + 1
															),
														]
											);
											addButtonRef.current?.focus();
										} }
									/>
								) ) }
							</SortableContext>
						</tbody>
					</table>
				</DndContext>
			</div>
			<Button
				ref={ addButtonRef }
				variant="primary"
				onClick={ () =>
					onChange( [
						...rows,
						createItem(
							Math.max( ...rows.map( ( item ) => item.id ) ) + 1
						),
					] )
				}
			>
				{ __( 'Add navigation item', 'tribe' ) }
			</Button>
		</Stack>
	);
}
