<?php

namespace DT\Autolink\Services\Charts;

use DT_Posts;

/**
 * Class TreeChart
 *
 * Represents a tree chart and provides methods for manipulating and displaying the tree structure.
 */
class TreeChart {

	const UNNESTED_META_KEY = 'dt_autolink_unnested';

	/**
	 * Class constructor
	 *
	 * Initializes the object and adds necessary action hooks.
	 * If the current action is not 'tree', the constructor returns without executing the remaining code.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'p2p_created_connection' ] );
		add_action( 'dt_autolink_group_created', [ $this, 'dt_autolink_group_created' ], 10, 2 );

		$action = sanitize_key( wp_unslash( $_GET['action'] ?? '' ) );
		if ( $action !== 'tree' ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', [ $this, 'wp_enqueue_scripts' ], 100 );
	}

	/**
	 * Enqueue scripts and styles for the front end.
	 *
	 * This method is used to enqueue scripts and styles for the front end of the website.
	 * It registers and enqueues the necessary scripts and styles using WordPress functions.
	 * The scripts and styles are loaded from the plugin directory.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	public function wp_enqueue_scripts() {
		$plugin_url  = plugins_url() . '/disciple-tools-autolink';
		$plugin_path = WP_PLUGIN_DIR . '/disciple-tools-autolink';

		wp_register_script( 'jquery-touch-punch', '/wp-includes/js/jquery/jquery.ui.touch-punch.js' ); // @phpcs:ignore
		wp_enqueue_script( 'portal-app-domenu-js', $plugin_url . '/magic-link/jquery.domenu-0.100.77.min.js', [ 'jquery' ],
        filemtime( $plugin_path . '/magic-link/jquery.domenu-0.100.77.min.js' ), false );

		wp_enqueue_style( 'portal-app-domenu-css', $plugin_url . '/magic-link/jquery.domenu-0.100.77.css', [],
        filemtime( $plugin_path . '/magic-link/jquery.domenu-0.100.77.css' ) );
	}

	/**
	 * Autolink the group that was created
	 *
	 * @param array $group The created group data
	 */
	public function dt_autolink_group_created( $group ) {
		add_post_meta( $group['ID'], self::UNNESTED_META_KEY, true );
	}

	/**
	 * Handle the creation of a connection between groups
	 *
	 * @param int $connection_id The ID of the connection being created
	 *
	 * @return void
	 */
	public function p2p_created_connection( $connection_id ) {
		$connection = p2p_get_connection( $connection_id );
		if ( ! $connection ) {
			return;
		}
		if ( $connection->p2p_type == 'groups_to_groups' ) {
			$group_id                     = $connection->p2p_to;
			$is_newly_created_by_autolink = get_post_meta( $group_id, self::UNNESTED_META_KEY, true );
			if ( $is_newly_created_by_autolink ) {
				return;
			}
			delete_post_meta( $group_id, self::UNNESTED_META_KEY );
		}
	}

	/**
	 * Generates a tree structure based on the given input data.
	 *
	 * @return array The generated tree structure.
	 */
	public function tree() {
		$title_list          = [];
		$pre_tree            = [];
		$unassigned_group_id = 'u';

		$contact = DT_Posts::list_posts( 'contacts', [
			'corresponds_to_user' => get_current_user_id(),
			'limit'               => 1000
		], false )['posts'][0];

		$meta = [
			'assigned' => [],
			'coaching' => [],
			'parents'  => [],
			'titles'   => [],
		];

		$groups = DT_Posts::list_posts( 'groups', [
			'assigned_to' => [ get_current_user_id() ],
		], false )['posts'] ?? [];

		$coaching = DT_Posts::list_posts( 'contacts', [
			'coached_by' => [ $contact['ID'] ],
		], false )['posts'] ?? [];

		$allowed_group_ids = [];
		foreach ( $groups as $group ) {
			$allowed_group_ids[]              = (int) $group['ID'];
			$groups[]                         = $group;
			$meta['assigned'][ $group['ID'] ] = true;
			$meta['leading'][ $group['ID'] ]  = array_filter( $group['leaders'], function ( $leader ) use ( $contact ) {
				return $leader['ID'] == $contact['ID'];
			} );
			$meta['coaching'][ $group['ID'] ] = false;
			$meta['titles'][ $group['ID'] ]   = $group['name'];
		}

		foreach ( $coaching as $coached ) {
			$coached_user_id = $coached['corresponds_to_user'] ?? false;
			if ( ! $coached_user_id ) {
				continue;
			}
			$coached_groups = DT_Posts::list_posts( 'groups', [
				'assigned_to' => [ $coached_user_id ],
			], false )['posts'] ?? [];

			foreach ( $coached_groups as $coached_group ) {
				$allowed_group_ids[]                      = (int) $coached_group['ID'];
				$groups[]                                 = $coached_group;
				$meta['leading'][ $coached_group['ID'] ]  = array_filter( $coached_group['leaders'], function ( $leader ) use ( $contact ) {
					return $leader['ID'] == $contact['ID'];
				} );
				$meta['assigned'][ $coached_group['ID'] ] = false;
				$meta['coaching'][ $coached_group['ID'] ] = true;
				$meta['titles'][ $coached_group['ID'] ]   = $coached_group['name'];
			}
		}

		$meta['groups'] = $allowed_group_ids;

		foreach ( $groups as $p ) {
			$first_root_group = null;
			foreach ( $pre_tree as $pre_group_id => $pre_parent_group_id ) {
				if ( ! $pre_parent_group_id ) {
					$first_root_group = $pre_group_id;
					break;
				}
			}


			$parents = [];
			foreach ( $p['parent_groups'] as $parent_group ) {
				$parents[] = $parent_group['ID'];
			}

			$has_parent          = ! empty( $parents );
			$has_allowed_parent  = $has_parent && array_filter( $parents, function ( $parent ) use ( $allowed_group_ids ) {
					return in_array( $parent, $allowed_group_ids );
			} );
			$contact_is_assigned = $meta['assigned'][ $p['ID'] ] ?? false;


			if ( isset( $p['child_groups'] ) && ! empty( $p['child_groups'] ) ) {
				foreach ( $p['child_groups'] as $children ) {
					$pre_tree[ $children['ID'] ] = $p['ID'];
				}
			}

			if ( ! $has_allowed_parent && $contact_is_assigned ) {
				$pre_tree[ $p['ID'] ] = null;
			} elseif ( ! $has_allowed_parent ) {
				if ( ! $has_parent ) {
					$pre_tree[ $p['ID'] ] = $unassigned_group_id;
				} else {
					$pre_tree[ $p['ID'] ] = null;
				}
			}

			$title                  = $p['name'];
			$title_list[ $p['ID'] ] = $title;

			$meta['parents'][ $p['ID'] ] = $has_allowed_parent;

			if ( isset( $pre_tree[ $p['ID'] ] ) && is_null( $pre_tree[ $p['ID'] ] ) && is_null( $first_root_group ) ) {
				$first_root_group = $p['ID'];
			}
		}

		if ( array_search( $unassigned_group_id, $pre_tree ) ) {
			$meta['groups'][]                         = $unassigned_group_id;
			$pre_tree[ $unassigned_group_id ]         = null;
			$meta['assigned'][ $unassigned_group_id ] = false;
			$meta['coaching'][ $unassigned_group_id ] = false;
			$meta['parents'][ $unassigned_group_id ]  = false;
			$meta['titles'][ $unassigned_group_id ]   = 'Unassigned';
		}

		$tree = $this->parse_tree( $pre_tree, $meta, null );

		if ( is_null( $tree ) ) {
			$tree = [];
		}

		return [
			'tree' => $tree
		];
	}

	/**
	 * Parses the given tree structure and generates a nested tree structure based on the provided metadata.
	 *
	 * @param array $tree The tree structure to parse.
	 * @param array $meta The metadata required for generating the tree structure.
	 * @param string|null $root The root element to start the parsing. Default is null.
	 *
	 * @return array|null The nested tree structure generated from the given input data.
	 */
	private function parse_tree( $tree, $meta, $root = null ) {
		$return = [];
		# Traverse the tree and search for direct children of the root
		foreach ( $tree as $child => $parent ) {
			# A direct child is found
			if ( $parent == $root && in_array( $child, $meta['groups'] ) ) {
				# Remove item from tree (we don't need to traverse this again)
				unset( $tree[ $child ] );
				# Append the child into result array and parse its children
				$return[] = [
					'id'         => $child,
					'title'      => $child,
					'name'       => $meta['titles'][ $child ] ?? 'No Name',
					'children'   => $this->parse_tree( $tree, $meta, $child ),
					'assigned'   => $meta['assigned'][ $child ] ?? false,
					'has_parent' => $meta['parents'][ $child ] ?? false,
					"leading"    => $meta['leading'][ $child ] ?? false,
					"coaching"   => $meta['coaching'][ $child ] ?? false
				];
			}
		}

		return empty( $return ) ? null : $return;
	}
}
