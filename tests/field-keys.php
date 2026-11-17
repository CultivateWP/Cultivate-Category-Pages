<?php
/**
 * Run on a disposable WordPress install with ACF Pro:
 * wp --skip-themes --skip-plugins=cultivate-category-pages eval-file /path/to/plugin/tests/field-keys.php
 * Creates temporary fixtures inside a rolled-back database transaction.
 */

if ( ! function_exists( 'acf_add_local_field_group' ) || function_exists( 'cultivate_category_pages' ) ) {
	WP_CLI::error( 'Load ACF Pro and skip the installed Category Pages plugin.' );
}
require dirname( __DIR__ ) . '/cultivate-category-pages.php';

function cwp_check( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function cwp_term_fields( $plugin, $taxonomies ) {
	foreach ( [ 'local-fields', 'local-groups', 'fields', 'values' ] as $store ) {
		acf_get_store( $store )->reset();
	}
	$plugin->supported_taxonomies = $taxonomies;
	$plugin->register_metabox();
	$fields = [];
	foreach ( acf_get_local_field_groups() as $group ) {
		foreach ( acf_get_local_fields( $group['key'] ) as $field ) {
			if ( 'taxonomy' === $field['type'] ) {
				$fields[ $field['taxonomy'] ] = $field;
			}
		}
	}
	return $fields;
}

$plugin = cultivate_category_pages();
$plugin->post_type();
$plugin->register_cpt();
register_taxonomy( 'cwp_topic', 'post', [ 'public' => true ] );
register_taxonomy( 'cwp_mood', 'post', [ 'public' => true ] );
$posts = $terms = [];
$failure = null;
global $wpdb;
$wpdb->query( 'START TRANSACTION' );
try {
	$before = cwp_term_fields( $plugin, [ 'category', 'cwp_topic' ] );
	cwp_check( $before['category']['key'] === 'field_100', 'Default category key changed' );
	$landing = wp_insert_post( [ 'post_type' => $plugin->post_type, 'post_title' => 'Field key regression', 'post_status' => 'publish' ], true );
	cwp_check( ! is_wp_error( $landing ), 'Could not create landing fixture' );
	$posts[] = $landing;
	$term = wp_insert_term( 'Field key ' . wp_generate_uuid4(), 'cwp_topic' );
	cwp_check( ! is_wp_error( $term ), 'Could not create term fixture' );
	$terms[] = $term['term_id'];
	update_field( $before['cwp_topic']['key'], $term['term_id'], $landing );
	$after = cwp_term_fields( $plugin, [ 'cwp_mood', 'cwp_topic', 'category' ] );
	foreach ( $before as $taxonomy => $field ) {
		cwp_check( $after[ $taxonomy ]['key'] === $field['key'], 'Reordering changed saved term selector: ' . $taxonomy );
	}
	cwp_check( (int) get_field( 'be_connected_cwp_topic', $landing ) === $term['term_id'], 'Saved term did not reload after reordering' );
	$removed = cwp_term_fields( $plugin, [ 'category', 'cwp_topic' ] );
	cwp_check( $removed['cwp_topic']['key'] === $after['cwp_topic']['key'], 'Removing a taxonomy shifted another selector' );
	// A pre-update custom taxonomy could have occupied any index, including zero.
	foreach ( [ 'field_100', 'field_101', 'field_1012' ] as $legacy_key ) {
		update_post_meta( $landing, '_be_connected_cwp_topic', $legacy_key );
		acf_get_store( 'values' )->reset();
		$field = get_field_object( 'be_connected_cwp_topic', $landing );
		cwp_check( $field && $field['taxonomy'] === 'cwp_topic' && (int) $field['value'] === $term['term_id'], 'Legacy reference resolved incorrectly: ' . $legacy_key );
		cwp_check( get_post_meta( $landing, '_be_connected_cwp_topic', true ) === $legacy_key, 'Reading rewrote legacy metadata' );
	}
	update_field( $after['cwp_topic']['key'], $term['term_id'], $landing );
	cwp_check( get_post_meta( $landing, '_be_connected_cwp_topic', true ) === $after['cwp_topic']['key'], 'Saving did not write the stable key' );
	$other = wp_insert_post( [ 'post_type' => 'post', 'post_title' => 'Unrelated field fixture' ], true );
	cwp_check( ! is_wp_error( $other ), 'Could not create unrelated fixture' );
	$posts[] = $other;
	update_post_meta( $other, '_be_connected_cwp_topic', 'field_101' );
	cwp_check( acf_get_reference( 'be_connected_cwp_topic', $other ) === 'field_101', 'Compatibility filter affected another post type' );
	update_post_meta( $landing, '_be_connected_cwp_topic', 'field_custom_override' );
	cwp_check( acf_get_reference( 'be_connected_cwp_topic', $landing ) === 'field_custom_override', 'Compatibility filter replaced a custom key' );
} catch ( Throwable $error ) {
	$failure = $error->getMessage();
} finally {
	foreach ( $posts as $post_id ) {
		wp_delete_post( $post_id, true );
	}
	foreach ( $terms as $term_id ) {
		wp_delete_term( $term_id, 'cwp_topic' );
	}
	$wpdb->query( 'ROLLBACK' );
}
if ( $failure ) {
	WP_CLI::error( $failure );
}
WP_CLI::success( 'Reordering, saved term values, legacy references, stable saves and compatibility scope passed.' );
