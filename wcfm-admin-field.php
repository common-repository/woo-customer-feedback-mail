<?php
/*
 * Admin Submenu Field
 */
//ob_start();
if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class Wcfm_Customer_Order_List_Table extends WP_List_Table {
    
  
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'wcfm-mailsend',     //singular name of the listed records
            'plural'    => 'wcfm-mailsend',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
    }
    
    /* 
     * Get order with email,status and date 
     */
    
    function wcfm_order_data(){
        
          $terms = array();  
         $orderdata = array(); 
         if ($_REQUEST['orderstatus'] !=''){
          
             $terms = array($_REQUEST['orderstatus']);
         }else{
           
            $terms =   array('pending' , 'failed' , 'processing' , 'completed', 'on-hold' , 'cancelled' , 'refunded');
         }
         
           $args = array(
                        'post_type'   => 'shop_order',
                        'post_status' => 'publish',
                        'tax_query'   => array( array(
                                'taxonomy' => 'shop_order_status',
                                'field'           => 'slug',
                                'terms'         => $terms
                        ) )
                     ) ;
             $loop = new WP_Query( $args  );
            //echo '<pre>'; print_r($loop);
            while ( $loop->have_posts() ) : $loop->the_post();
                $order_id = $loop->post->ID; 
                $order = new WC_Order($order_id);
              
                $orderdata[] = array('useremail' => $order->billing_email,
                                    'userstatus' => $order->status,
                                    'userdate' => get_the_time('Y/d/m'));
            endwhile;  
            return $orderdata;
        
      
    }


  
    function column_default($item, $column_name){
        switch($column_name){
            case 'userstatus':
            case 'userdate':
            case 'useremail':       
                return $item[$column_name];
                
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }


  
    function column_title($item){
        
        //Build row actions
        $actions = array(
           // 'edit'      => sprintf('<a href="?page=%s&action=%s&movie=%s">Edit</a>',$_REQUEST['page'],'edit',$item['ID']),
           // 'delete'    => sprintf('<a href="?page=%s&action=%s&movie=%s">Delete</a>',$_REQUEST['page'],'delete',$item['ID']),
        );
        
        //Return the useremail contents
        return sprintf('%1$s ',
            $item['useremail'],
           
           $this->row_actions($actions)
        );
    }


   
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'], 
            /*$2%s*/ $item['useremail']              
        );
    }


   
    function wcfm_get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
            'useremail'     => 'Email',
            'userstatus'    => 'Status',
            'userdate'  => 'Date'
        );
        return $columns;
    }


    function wcfm_sortable_columns() {
        $sortable_columns = array(
            'useremail'     => array('useremail',false),     //true means it's already sorted
            'userstatus'    => array('userstatus',false),
            'userdate'  => array('userdate',false)
        );
        return $sortable_columns;
    }


    
    function get_bulk_actions() {
        $actions = array(
            'sendmail'    => 'SendMail'
        );
       
        return $actions;
    }

            


    function wcfm_bulk_action() {
        
         $useremailarry = array();
        //Detect when a bulk action is being triggered...
        if( 'sendmail'=== $this->current_action() ) {
          
            if(empty($_REQUEST['wcfm-mailsend'])){
                
               
                echo "<a href='".WCFM_ADMINPAGE_URL."'>Return List Table</a>"; 
                wp_die('Please Select Email..');
                
            }
            
         
            foreach($_REQUEST['wcfm-mailsend'] as $uemail){
              
                
             $useremailarry[]  = $uemail;  
              
              
            
             }
              $emailwithcoma =  implode(',', $useremailarry);
              
              $to = $emailwithcoma;
              $subject = 'User Status Mail';
              $body =   wpautop(get_option('wcfm-customer-email-body'));
              $headers = array('Content-Type: text/html; charset=UTF-8',
                               'From:'.get_option( 'blogname' ).' <'.get_option( 'admin_email' ).'>',
                               'Reply-To: <'.get_option( 'admin_email' ).'>');
              wp_mail( $to, $subject, $body, $headers );
             echo '<div id="" class=" notice notice-success"><p style="word-wrap: break-word;">Mail hase been send!..<br>You have send mail from: '.$emailwithcoma.'</p></div>';
          
        }
            
        
    }


    function wcfm_prepare_items() {
        global $wpdb; //This is used only if making any database queries

        $per_page = 10;
        
        
       
        $columns = $this->wcfm_get_columns();
        $hidden = array();
        $sortable = $this->wcfm_sortable_columns(); 
        
        
       
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        
       
        $this->wcfm_bulk_action();
        
        
        $data = $this->wcfm_order_data();
                
        
        
        function usort_reorder($a,$b){
            $orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'useremail'; //If no sort, default to useremail
            $order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
            $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
            return ($order==='asc') ? $result : -$result; //Send final sort direction to usort
        }
        usort($data, 'usort_reorder');
        
      
        
        $current_page = $this->get_pagenum();
        
       
        $total_items = count($data);
        
        
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        
        
        $this->items = $data;
        
        
        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
        ) );
    }


}





/** ************************ REGISTER THE SubMenu PAGE ****************************
 ********************************************************************************/
 
class wcfm_admin_submenu_page {
    
    
    
      public function __construct(){
          
          add_action('admin_menu', array($this,'wcfm_admin_menu'));
          add_action('admin_menu', array($this,'wcfm_register_user_manage_settings'));
      }  
    
      /*
       *  Create Submenu Under Woocomerce
       */
    
       public function wcfm_admin_menu(){

            add_submenu_page('woocommerce', 'Customer FeedBack Send',
                            'Customer FeedBack Send', 'manage_options', 
                            'wcfm-customer-feedback-mail',array($this,'wcfm_list_page'));

        } 


        /*
         * Register Setting Field
         */
        
       public function wcfm_register_user_manage_settings() {
	//register our settings
	register_setting( 'wcfm-customer-email-body-plugin-settings', 'wcfm-customer-email-body' );
	
        }       
        
      
        

      
       public function wcfm_list_page(){

               
            //Create an instance of our package class...
            $testListTable = new Wcfm_Customer_Order_List_Table();
            //Fetch, prepare, sort, and filter our data...
            $testListTable->wcfm_prepare_items();

            ?>
            <div class="wrap">

                <div id="icon-users" class="icon32"><br/></div>
                <h2>User Order List</h2>

                
                <form id="user-order-show-filter" method="post" action="" style="float: right; width: 77%;">
                    <div style="margin: 0px 0px -30px 5px;">
                    <select id="orderstatus" name="orderstatus" >
                        <option  value="" >All Order</option>
                        <option  value="completed" <?php if ($_REQUEST['orderstatus'] == 'completed') { ?> selected="selected"<?php } ?>>Completed</option>
                        <option  value="processing" <?php if ($_REQUEST['orderstatus'] == 'processing') { ?> selected="selected"<?php } ?>>Processing</option>
                        <option  value="pending" <?php if ($_REQUEST['orderstatus'] == 'pending') { ?> selected="selected"<?php } ?>>Pending</option>
                        <option  value="failed" <?php if ($_REQUEST['orderstatus'] == 'failed') { ?> selected="selected"<?php } ?>>Failed</option>
                        <option  value="on-hold" <?php if ($_REQUEST['orderstatus'] == 'on-hold') { ?> selected="selected"<?php } ?>>On-Hold</option>
                        <option  value="cancelled" <?php if ($_REQUEST['orderstatus'] == 'cancelled') { ?> selected="selected"<?php } ?>>Cancelled</option>
                        <option  value="refunded" <?php if ($_REQUEST['orderstatus'] == 'refunded') { ?> selected="selected"<?php } ?>>Refunded</option>
                    </select>
                    <input id="" name="showresult" class="button" type="submit" value="Show Result">
                    </div>
                </form>
                
                <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
                <form id="user-filter" method="post">
                    <!-- For plugins, we also need to ensure that the form posts back to our current page -->
                    <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
                    <!-- Now we can render the completed list table -->
                    <?php $testListTable->display() ?>
                </form>
                
                
            </div>
           
            
              <div class="wrap">
                    <h2>Mail Body</h2>

                    <form method="post" action="options.php">
                        <?php settings_fields( 'wcfm-customer-email-body-plugin-settings' ); ?>
                        <?php do_settings_sections( 'wcfm-customer-email-body-plugin-settings' ); ?>
                        <table class="form-table">
                            <tr valign="top">
                            <th scope="row">Customer Status</th>
                            <td>
                                
                                    <?php
                                $args = array(
                                    'textarea_rows' => 15,
                                    'teeny' => true,
                                    'quicktags' => true,
                                    'textarea_name' => 'wcfm-customer-email-body',
                                     );

                                wp_editor( get_option('wcfm-customer-email-body') , 'editor', $args );
                                    ?>
                            </td>
                            </tr>


                        </table>

                        <?php submit_button(); ?>

                    </form>
                </div>
                <?php
        }
}

if(is_admin()){
    
$initsubmenu = new wcfm_admin_submenu_page();    
}