import { useBlockProps, RichText } from '@wordpress/block-editor';
import { ReactComponent as Icon } from '../../images/icon-info.svg';

export default function save( { attributes } ) {
	const blockProps = useBlockProps.save( {
		className: 'alignfull',
	} );

	return (
		<div { ...blockProps }>
			<div className="wp-block-chocante-infobar__container">
				<Icon className="wp-block-chocante-infobar__icon" />
				<RichText.Content value={ attributes.content } tagName="p" />
			</div>
		</div>
	);
}
