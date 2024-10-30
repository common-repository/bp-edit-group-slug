<?php
/**
 * Plugin Name: BuddyPress Edit Group Slug
 * Plugin URI:  http://buddypress.org/
 * Description: Allows group administrators to manually edit group slug
 * Author:      John James Jacoby
 * Version:     2.0.0
 * Tags:        buddypress, groups, slug, edit
 * Author URI:  https://jjj.blog
 * Network:     true
*/

/**
 * Handle loading if/when BuddyPress is loaded
 *
 * @since 1.0.0
 */
function bp_groups_edit_slug_init() {
	bp_groups_edit_slug_wrapper();
}
add_action( 'bp_groups_includes', 'bp_groups_edit_slug_init' );

/**
 * @since 1.0.0
 */
function bp_groups_edit_slug_wrapper() {

	// Slug slug
	if ( ! defined( 'BP_GROUP_CHANGE_SLUG' ) ) {
		define( 'BP_GROUP_CHANGE_SLUG', 'slug' );
	}

	/**
	 * Extend the group extension
	 *
	 * @since 1.0.0
	 */
	class BP_Groups_Edit_Slug extends BP_Group_Extension {

		/**
		 * @var string Private visibility
		 */
		public $visibility = 'private';

		/**
		 * @var bool Enabled in navigation
		 */
		public $enable_nav_item = false;

		/**
		 * @var bool Enabled on creation
		 */
		public $enable_create_step = true;

		/**
		 * @var bool Enabled on edit
		 */
		public $enable_edit_item = true;

		/**
		 * Class constructor
		 *
		 * @since 2.0.0
		 */
		public function __construct() {
			$this->name = __( 'Slug', 'bp-groupslugs' );
			$this->slug = BP_GROUP_CHANGE_SLUG;

			$this->create_step_position = 11;
			$this->nav_item_position    = 11;
		}

		/**
		 * Output the create screen
		 *
		 * @since 1.0.0
		 *
		 * @param int $group_id
		 * @return boolean
		 */
		public function create_screen( $group_id = null ) {

			// Bail if not on this step
			if ( ! bp_is_group_creation_step( $this->slug ) ) {
				return false;
			} ?>

			<div class="editfield">
				<label for=""><?php esc_html_e( 'Slug', 'bp-edit-group-slug' ); ?></label>
				<p><?php esc_html_e( 'This slug has been automatically created from the group name you entered in step 1.', 'bp-edit-group-slug' ); ?></p>
				<p><?php esc_html_e( 'You can keep it, or change it to something more accurate.', 'bp-edit-group-slug' ); ?></p>
				<div>
					<?php echo $this->get_group_slug_screen( $group_id ); ?>
				</div>
			</div>

			<?php wp_nonce_field( 'groups_create_save_' . $this->slug );
		}

		/**
		 * Save the create screen
		 *
		 * @since 1.0.0
		 *
		 * @param int $group_id
		 */
		public function create_screen_save( $group_id = null ) {
			check_admin_referer( 'groups_create_save_' . $this->slug );

			$this->method = 'create';
			$this->save( $group_id );
		}

		/**
		 * Output the edit screen
		 *
		 * @since 1.0.0
		 *
		 * @param int $group_id
		 * @return boolean
		 */
		public function edit_screen( $group_id = null ) {

			// Bail if not on this step
			if ( ! bp_is_group_admin_screen( $this->slug ) ) {
				return false;
			} ?>

			<div class="editfield">
				<p><?php esc_html_e( 'The slug was automatically created from the group name. You can keep it, or change it to something more accurate.', 'bp-edit-group-slug' ); ?></p>

				<div class="slug-wrapper">
					<?php echo $this->get_group_slug_screen( $group_id ); ?>
				</div>
			</div>

			<div class="form-submit">
				<input type="submit" name="save" value="<?php esc_html_e( 'Update Slug', 'bp-edit-group-slug' ); ?>" />
			</div>

			<?php wp_nonce_field( 'groups_edit_save_' . $this->slug );
		}

		/**
		 * Save the edit screen
		 *
		 * @since 1.0.0
		 *
		 * @param int $group_id
		 * @return boolean
		 */
		public function edit_screen_save( $group_id = null ) {

			// Bail if not saving
			if ( empty( $_POST['save'] ) ) {
				return false;
			}

			check_admin_referer( 'groups_edit_save_' . $this->slug );

			$this->method = 'edit';
			$this->save( $group_id );
		}

		/**
		 * Output the group slug screen
		 *
		 * @since 1.0.0
		 *
		 * @param int $group_id
		 * @param string $new_title
		 * @param string $new_slug
		 * @return type
		 */
		public function get_group_slug_screen( $group_id, $new_title = null, $new_slug = null ) {

			// Get updated group data that group creation methods do not provide
			$new_group  = new BP_Groups_Group( $group_id );
			$permalink  = bp_get_groups_directory_permalink();
			$group_slug = $new_group->slug;

			$title           = __( 'Current group slug', 'bp-edit-group-slug' );
			$group_name_html = '<input type="text" id="editable-group-name" name="editable-group-name" title="' . $title . '" value="' . $group_slug . '" />';

			$return = '<label for="editable-group-name">' . __( 'Group Link', 'bp-edit-group-slug' ) . "</label>\n" . '<span id="sample-permalink">' . $permalink . "</span>" . $group_name_html;

			return apply_filters( 'bp_get_group_permalink_html', $return, $group_id, $new_title, $new_slug );
		}

		/**
		 * Save a group's slug
		 *
		 * @since 1.0.0
		 */
		public function save( $group_id = 0 ) {

			// Get group ID
			if ( empty( $group_id ) ) {
				$group_id = groups_get_current_group()->id;
			}

			// Set error redirect based on save method
			$redirect_url = ( $this->method === 'create' )
				? bp_get_groups_directory_permalink() . 'create/step/' . $this->slug
				: bp_get_group_permalink( groups_get_current_group() ) . 'admin/' . $this->slug;

			// Slug cannot be empty
			if ( empty( $_POST['editable-group-name'] ) ) {
				bp_core_add_message( __( 'Slug cannot be empty. Please try again.', 'bp-edit-group-slug' ), 'error' );
				bp_core_redirect( $redirect_url );
			}

			// Never trust an input box
			$new_slug = sanitize_title( $_POST['editable-group-name'] );

			// Check if slug exists and handle accordingly
			$success = $this->check_slug( $group_id, $new_slug );

			// Slug is no good or already taken
			if ( empty( $success ) ) {
				bp_core_add_message( __( 'That slug is not available. Please try again.', 'bp-edit-group-slug' ), 'error' );
				bp_core_redirect( $redirect_url );

			// Slug is good so try to save it
			} else {
				$save = $this->update_slug( $group_id, $new_slug );

				if ( false === $save ) {
					bp_core_add_message( __( 'An unknown error has occurred. Slug was not saved.', 'bp-edit-group-slug' ), 'error' );
					bp_core_redirect( $redirect_url );

				// Reset error redirect based on save method
				} elseif ( $this->method !== 'create' ) {
					groups_get_current_group()->slug = $new_slug;

					$redirect_url = bp_get_group_permalink( groups_get_current_group() ) . 'admin/' . $this->slug;

					bp_core_add_message( __( 'The group slug was saved successfully.', 'bp-edit-group-slug' ), 'success' );
					bp_core_redirect( $redirect_url );
				}
			}
		}

		/**
		 * Check if slug is OK
		 *
		 * @since 1.0.0
		 *
		 * @param int $group_id
		 * @param string $slug
		 *
		 * @return boolean
		 */
		private function check_slug( $group_id = 0, $slug = '' ) {

			// Allow save if no change
			if ( $slug === groups_get_current_group()->slug ) {
				return true;
			}

			// Group slugs cannot start with wp
			if ( 'wp' === substr( $slug, 0, 2 ) ) {
				$slug = substr( $slug, 2, strlen( $slug ) - 2 );
			}

			// Don't allow forbidden names
			if ( in_array( $slug, buddypress()->groups->forbidden_names ) ) {
				return false;
			}

			// Run it through the BP core slug checker
			if ( BP_Groups_Group::check_slug( $slug ) ) {
				if ( $slug !== BP_Groups_Group::get_slug( $group_id ) ) {
					return false;
				}
			}

			// Slug is good, return true
			return true;
		}

		/**
		 * Update a group's slug
		 *
		 * @since 1.0.0
		 *
		 * @param int $group_id
		 * @param string $slug
		 *
		 * @return boolean
		 */
		private function update_slug( $group_id = 0, $slug = '' ) {

			// Sanitize the slug
			$slug = sanitize_key( $slug );

			// Bail if missing data
			if ( empty( $group_id ) || empty( $slug ) ) {
				return false;
			}

			// Setup new group slug
			$group       = groups_get_group( $group_id );
			$old_slug    = $group->slug;
			$group->slug = $slug;

			// Maybe save old slug
			$old_slugs = array_values( groups_get_groupmeta( $group_id, 'previous_slug', false ) );
			if ( false === array_search( $old_slug, $old_slugs, true ) ) {
				groups_add_groupmeta( $group_id, 'previous_slug', $old_slug, false );
			}

			// Return results of group saving
			return $group->save();
		}
	}

	// Register the extension
	bp_register_group_extension( 'BP_Groups_Edit_Slug' );
}
