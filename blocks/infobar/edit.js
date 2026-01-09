import { __ } from '@wordpress/i18n';
import { useBlockProps, RichText } from '@wordpress/block-editor';
import { ReactComponent as Icon } from '../../images/icon-info.svg';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( {
		className: 'alignfull',
	} );

	return (
		<div { ...blockProps }>
			<div className="wp-block-chocante-infobar__container">
				<Icon className="wp-block-chocante-infobar__icon" />
				<RichText
					placeholder={ __( 'Insert message…', 'chocante' ) }
					value={ attributes.content }
					onChange={ ( newContent ) =>
						setAttributes( { content: newContent } )
					}
					tagName="p"
					spellCheck="false"
				/>
			</div>
		</div>
	);
}
