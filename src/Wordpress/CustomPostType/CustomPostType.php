<?php


namespace CommonsBooking\Wordpress\CustomPostType;

use CommonsBooking\Plugin;

abstract class CustomPostType
{

    public static $postType;

    protected $customFields;

    protected $menuPosition;

    protected $listFields = [];

    abstract public function getArgs();

    public static function getPostType()
    {
        return static::$postType;
    }

    public function addRoleCaps() {
        // Add the roles you'd like to administer the custom post types
        $roles = array(Plugin::$CB_MANAGER_ID, 'administrator');

        if(self::getPostType() == Location::$postType) {
            $roles[] = Plugin::$LOCATION_ADMIN_ID;
        }
        if(self::getPostType() == Item::$postType) {
            $roles[] = Plugin::$ITEM_ADMIN_ID;
        }

        // Loop through each role and assign capabilities
        foreach($roles as $the_role) {
            $role = get_role($the_role);
            $role->add_cap( 'read_' . static::$postType);
            $role->add_cap( 'manage_' . CB_PLUGIN_SLUG . '_' . static::$postType);

            $role->add_cap( 'edit_' . static::$postType );
            $role->add_cap( 'edit_' . static::$postType . 's' ); // show item list
            $role->add_cap( 'edit_private_' . static::$postType . 's' );
            $role->add_cap( 'edit_published_' . static::$postType . 's' );

            $role->add_cap( 'publish_' . static::$postType . 's' );

            $role->add_cap( 'delete_' . static::$postType );
            $role->add_cap( 'delete_' . static::$postType . 's' );

            $role->add_cap( 'read_private_' . static::$postType . 's' );
            $role->add_cap( 'edit_others_' . static::$postType . 's' );
            $role->add_cap( 'delete_private_' . static::$postType . 's' );
            $role->add_cap( 'delete_published_' . static::$postType . 's' ); // delete user post
            $role->add_cap( 'delete_others_' . static::$postType . 's' );

            $role->add_cap( 'edit_posts' ); // general: create posts -> even wp_post, affects all cpts

            if($the_role == Plugin::$CB_MANAGER_ID) {
                $role->remove_cap( 'read_private_' . static::$postType . 's' );
                $role->remove_cap( 'delete_private_' . static::$postType . 's' );
                $role->remove_cap( 'delete_others_' . static::$postType . 's' );
            }
        }
    }

    public function getMenuParams()
    {
        return [
            'cb-dashboard',
            $this->getArgs()['labels']['name'],
            $this->getArgs()['labels']['name'],
            'manage_' . CB_PLUGIN_SLUG,
            'edit.php?post_type=' . static::getPostType(),
            '',
            $this->menuPosition ?: null
        ];
    }

    /**
     * Remove the default Custom Fields meta box
     */
    public function removeDefaultCustomFields($type, $context, $post)
    {
        foreach (array('normal', 'advanced', 'side') as $context) {
            remove_meta_box('postcustom', static::getPostType(), $context);
        }
    }

    /**
     * @deprecated Defined in getArgs-function of CTP
     * https://knowthecode.io/docx/wordpress/remove_post_type_support
     */
    public function removeAllFormFields()
    {
        $this->removeFormTitle();
        $this->removeFormDescription();
        $this->removeFormImage();
    }

    /**
     * @deprecated Defined in getArgs-function of CTP
     */
    public function removeFormTitle()
    {
        remove_post_type_support(static::getPostType(), 'title');
    }

    /**
     * @deprecated Defined in getArgs-function of CTP
     */
    public function removeFormDescription()
    {
        remove_post_type_support(static::getPostType(), 'editor');
    }

    /**
     * @deprecated Defined in getArgs-function of CTP
     */
    public function removeFormImage()
    {
        remove_post_type_support(static::getPostType(), 'thumbnail');
    }

    public static function getWPAction()
    {
        return static::getPostType() . "-custom-fields";
    }

    public static function getWPNonceId()
    {
        return static::getPostType() . "-custom-fields" . '_wpnonce';
    }

    public static function getWPNonceField()
    {
        return wp_nonce_field(static::getWPAction(), static::getWPNonceId(), false, true);
    }

    /**
     * Save the new Custom Fields values
     */
    public function saveCustomFields($post_id, $post)
    {
        // if (!current_user_can('edit_post', $post_id)) {
        //     return;
        // }
        if ($post->post_type !== static::getPostType()) {
            return;
        }

        $noDeleteMetaFields = ['start-time', 'end-time', 'timeframe-repetition'];

        /** @var Field $customField */
        foreach ($this->getCustomFields() as $customField) {
            if (current_user_can($customField->getCapability(), $post_id)) {
                $fieldNames = [];
                if ($customField->getType() == "checkboxes") {
                    $fieldNames = $customField->getOptionFieldNames();
                } else {
                    $fieldNames[] = $customField->getName();
                }

                foreach ($fieldNames as $fieldName) {
                    if(!array_key_exists($fieldName, $_REQUEST)) {
                        if(!in_array($fieldName, $noDeleteMetaFields)) {
                            delete_post_meta($post_id, $fieldName);
                        }
                        continue;
                    }

                    $value = $_REQUEST[$fieldName];
                    if(is_string($value)) {
                        $value = trim($value);
                        update_post_meta($post_id, $fieldName, $value);

                        // if we have a booking, there shall be set no repetition
                        if($fieldName == "type" && $value == Timeframe::BOOKING_ID) {
                            update_post_meta($post_id, 'timeframe-repetition', 'norep');
                        }
                    }

                    if (is_array($value)) {
                        // Update time-fields by date-fields
                        /*if(in_array($fieldName, ['repetition-start', 'repetition-end'])) {
                            update_post_meta(
                                $post_id,
                                str_replace('date','time', $fieldName),
                                $value['time']
                            );
                        }*/
                    }
                }
            }
        }
    }

    public function registerMetabox() {
        $cmb = new_cmb2_box([
            'id' => static::getPostType() . "-custom-fields",
            'title' => "Timeframe",
            'object_types' => array(static::getPostType())
        ]);

        /** @var Field $customField */
        foreach ($this->getCustomFields() as $customField) {
            $cmb->add_field( $customField->getParamsArray());
        }
    }

    /**
     * Manages custom columns for list view.
     *
     * @param $columns
     *
     * @return mixed
     */
    public function setCustomColumns($columns)
    {
        if (isset($this->listColumns)) {
            foreach ($this->listColumns as $key => $label) {
                $columns[$key] = $label;
            }
        }

        return $columns;
    }

    public function setSortableColumns($columns)
    {
        if (isset($this->listColumns)) {
            foreach ($this->listColumns as $key => $label) {
                $columns[$key] = $key;
            }
        }

        return $columns;
    }

    public function removeListTitleColumn()
    {
        add_filter('manage_' . static::getPostType() . '_posts_columns', function ($columns) {
            unset($columns['title']);
            return $columns;
        });
    }

    public function removeListDateColumn()
    {
        add_filter('manage_' . static::getPostType() . '_posts_columns', function ($columns) {
            unset($columns['date']);
            return $columns;
        });
    }

    /**
     * Configures list-view
     */
    public function initListView()
    {
        // List-View
        add_filter('manage_' . static::getPostType() . '_posts_columns', array($this, 'setCustomColumns'));
        add_action('manage_' . static::getPostType() . '_posts_custom_column', array($this, 'setCustomColumnsData'), 10,
            2);
        add_filter('manage_edit-' . static::getPostType() . '_sortable_columns', array($this, 'setSortableColumns'));
        if (isset($this->listColumns)) {
            add_action('pre_get_posts', function ($query) {
                if (!is_admin()) {
                    return;
                }

                $orderby = $query->get('orderby');
                if (in_array($orderby, array_keys($this->listColumns))) {
                    $query->set('meta_key', $orderby);
                    $query->set('orderby', 'meta_value');
                }
            });
        }
    }

    /**
     * Adds data to custom columns
     *
     * @param $column
     * @param $post_id
     */
    public function setCustomColumnsData($column, $post_id)
    {
        if ($value = get_post_meta($post_id, $column, true)) {
            echo $value;
        } else {
            echo '-';
        }
    }

    /**
     * @param string $order
     *
     * @return \WP_Query
     */
    public static function getAllPostsQuery($order = 'ASC')
    {
        $args = array(
            'post_type' => static::getPostType(),
            'order' => $order
        );

        return new \WP_Query($args);
    }

    /**
     * @return array
     */
    public static function getAllPosts()
    {
        /** @var \WP_Query $query */
        $query = static::getAllPostsQuery();
        $posts = [];
        if ($query->have_posts()) {
            $posts = $query->get_posts();
        }

        return $posts;
    }

    /**
     * generates a random slug for use as post_name in timeframes/booking to prevent easy access to bookings via get parameters
     *
     * @param  mixed $length
     * @return void
     */
    public static function generateRandomSlug($length='24') {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    abstract public static function getView();

}
