<?php
/**
 * Langauge switcher
 *
 * @package WordPress
 * @subpackage Chocante
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="switcher switcher--language" data-no-translation>
	<span class="switcher__current"></span>
	<div class="switcher__items">
		<?php foreach ( $args['languages'] as $item ) : ?>
			<a href="#" data-language="<?php echo esc_attr( $item['language_code'] ); ?>"><?php echo esc_html( $item['short_language_name'] ); ?></a>
		<?php endforeach; ?>
	</div>
</div>

<script>
(function() {
	const el = document.currentScript.previousElementSibling;
	const links = el.querySelectorAll('a[data-language]');
	Array.from(links).forEach(function(link) {
		const languageCode = link.dataset.language.replace('_', '-');
		const linkHref = document.querySelector('link[rel="alternate"][hreflang="' + languageCode + '"]');
		if(linkHref) {
			const url = new URL(linkHref.href);
			link.href = url.origin + url.pathname;
		}
		if(languageCode === document.documentElement.lang) {
			link.style.display = 'none';
			el.querySelector('.switcher__current').textContent = link.textContent;
		}
	});
})();
</script>