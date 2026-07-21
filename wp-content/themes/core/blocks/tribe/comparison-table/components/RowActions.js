import { __ } from '@wordpress/i18n';
import { Button, Flex, FlexItem } from '@wordpress/components';

/**
 * Footer actions for inserting new category and feature rows.
 *
 * @param {Object}   root0
 * @param {Function} root0.onAddCategory Inserts a new category row.
 * @param {Function} root0.onAddRow      Inserts a new feature row.
 */
export default function RowActions( { onAddCategory, onAddRow } ) {
	return (
		<Flex
			className="b-comparison-table-editor__row-actions"
			align="center"
			justify="flex-start"
			gap={ 2 }
		>
			<FlexItem>
				<Button variant="secondary" onClick={ onAddCategory }>
					{ __( 'Add category', 'tribe' ) }
				</Button>
			</FlexItem>
			<FlexItem>
				<Button variant="secondary" onClick={ onAddRow }>
					{ __( 'Add feature', 'tribe' ) }
				</Button>
			</FlexItem>
		</Flex>
	);
}
