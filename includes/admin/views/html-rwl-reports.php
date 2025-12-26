<div class="wrap">
    <h1>گزارشات گردونه شانس</h1>
    
    <?php
    global $wpdb;
    $table_name = $wpdb->prefix . 'rwl_logs';
    
    $per_page = 20;
    $page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
    $offset = ( $page - 1 ) * $per_page;
    
    $total_items = $wpdb->get_var( "SELECT COUNT(id) FROM $table_name" );
    $total_pages = ceil( $total_items / $per_page );
    
    $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d, %d", $offset, $per_page ) );
    ?>

    <div class="tablenav top">
        <div class="tablenav-pages">
            <span class="displaying-num"><?php echo $total_items; ?> مورد</span>
            <?php
            $page_links = paginate_links( array(
                'base' => add_query_arg( 'paged', '%#%' ),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $total_pages,
                'current' => $page
            ) );
            
            if ( $page_links ) {
                echo '<span class="pagination-links">' . $page_links . '</span>';
            }
            ?>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>شماره موبایل</th>
                <th>آیتم برنده شده</th>
                <th>کد تخفیف</th>
                <th>آی‌پی کاربر</th>
                <th>تاریخ و ساعت</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ( $results ) {
                foreach ( $results as $row ) {
                    echo '<tr>';
                    echo '<td>' . esc_html( $row->id ) . '</td>';
                    echo '<td>' . esc_html( $row->mobile ) . '</td>';
                    echo '<td>' . esc_html( $row->won_item ) . '</td>';
                    echo '<td><code>' . esc_html( $row->won_code ) . '</code></td>';
                    echo '<td>' . esc_html( $row->user_ip ) . '</td>';
                    echo '<td>' . esc_html( $row->created_at ) . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="6">هیچ رکوردی یافت نشد.</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>
