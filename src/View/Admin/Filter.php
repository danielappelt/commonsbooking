<?php

namespace CommonsBooking\View\Admin;

class Filter {

	/**
	 * Renders backend list filter.
	 *
	 * @param $postType
	 * @param $label
	 * @param $key
	 * @param $values
	 */
	public static function renderFilter( $postType, $label, $key, $values ) {
		//only add filter to post type you want
		if ( isset( $_GET['post_type'] ) && $postType == $_GET['post_type'] ) {
			?>
            <select name="<?php echo 'admin_' . $key; ?>">
                <option value=""><?php echo $label; ?></option>
				<?php
				$filterValue = isset( $_GET[ 'admin_' . $key ] ) ? sanitize_text_field( $_GET[ 'admin_' . $key ] ) : '';
				foreach ( $values as $value => $label ) {
					printf
					(
						'<option value="%s"%s>%s</option>',
						$value,
						$value == $filterValue ? ' selected="selected"' : '',
						$label
					);
				}
				?>
            </select>
			<?php
		}
	}

	/**
     * Renders Start-/Enddate filters for admin lists.
	 * @param $postType
	 * @param $startDateInputName
	 * @param $endDateInputName
	 * @param $from
	 * @param $to
	 */
	public static function renderDateFilter( $postType, $startDateInputName, $endDateInputName, $from, $to ) {
		if ( isset( $_GET['post_type'] ) && $postType == $_GET['post_type'] ) {
			echo '<style>
                input[name=' . $startDateInputName . '], 
                input[name=' . $endDateInputName . ']{
                    line-height: 28px;
                    height: 28px;
                    margin: 0;
                    width:150px;
                }
            </style>
     
            <input type="text" name="' . $startDateInputName . '" placeholder="' . esc_html__(
					'Start date',
					'commonsbooking'
				) . '" value="' . esc_attr( $from ) . '" />
            <input type="text" name="' . $endDateInputName . '" placeholder="' . esc_html__(
				     'End date',
				     'commonsbooking'
			     ) . '" value="' . esc_attr( $to ) . '" />
     
            <script>
            jQuery( function($) {
                var from = $(\'input[name=' . $startDateInputName . ']\'),
                    to = $(\'input[name=' . $endDateInputName . ']\');
     
                $(\'input[name=' . $startDateInputName . '], input[name=' . $endDateInputName . ']\' ).datepicker( 
                    {
                        dateFormat : "yy-mm-dd"
                    }
                );
                from.on( \'change\', function() {
                    to.datepicker( \'option\', \'minDate\', from.val() );
                }); 
                to.on( \'change\', function() {
                    from.datepicker( \'option\', \'maxDate\', to.val() );
                }); 
            });
            </script>';
		}
	}

}