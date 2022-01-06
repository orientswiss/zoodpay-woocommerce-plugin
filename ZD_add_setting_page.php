<?php

class WC_Zoodpay_Gateway extends WC_Payment_Gateway

{

    public function __construct()

    {

        $this->id = 'zoodpay';

        $this->icon = apply_filters('woocommerce_payment_gateway_icon', plugin_dir_url( __FILE__ ) .'assest/img/pg.png');

        $this->has_fields = true;

        $this->method_title = __('Zoodpay Payment', 'zoodpay');

        $this->method_description = __('Zoodpay payment gateway', 'zoodpay');

        $this->options = array();

        $this->service = array();


        $this->supports = array(

            'products',

            'refunds'

        );

        // Method with all the options fields


        $this->init_form_fields();

        // Load the settings.


        $this->init_settings();

        $this->title = $this->get_option('title');

        $this->description = $this->get_option('description');

        $this->enabled = $this->get_option('enabled');


        $this->environment =  $this->get_option('environment');

        $this->merchant_key = $this->get_option('merchant_key');


        $this->merchant_secret_key = $this->get_option('merchant_secret_key');

        $this->terms_condition = $this->get_option('terms_condition');


        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(

            $this,

            'process_admin_options'

        ));

        add_action('woocommerce_checkout_create_order', array(

            $this,

            'Zoodpay_save_order_payment_type_meta_data'

        ) , 10, 2);

        add_filter('woocommerce_get_order_item_totals', array(

            $this,

            'Zoodpay_display_transaction_type_order_item_totals'

        ) , 10, 3);

        add_action('woocommerce_admin_order_data_after_billing_address', array(

            $this,

            'Zoodpay_display_payment_type_order_edit_pages'

        ) , 10, 1);

        add_filter('woocommerce_settings_api_sanitized_fields_' . $this->id, array(

            $this,

            'sanitize_settings'

        ));

    }

    public function init_form_fields()

    {

        $this->form_fields = array(

            'enabled' => array(

                'title' => __('Enable/Disable', 'zoodpay'),

                'label' => __('Enable Zoodpay Payments', 'zoodpay'),

                'type' => 'checkbox',

                'description' => '',

                'default' => 'no'

            ) ,

            'title' => array(

                'title' => __('Title *', 'zoodpay'),

                'type' => 'text',

                'description' => __('This controls the title which the user sees during checkout.', 'zoodpay'),

                'default' => __('ZoodPay', 'zoodpay'),

                'desc_tip' => true,

            ) ,

            'description' => array(

                'title' => __('Description', 'zoodpay'),

                'type' => 'textarea',

                'description' => __('This controls the description which the user sees during checkout.', 'zoodpay'),

                'default' => '',

            ) ,

            'environment' => array(

                'title' => __('API URL', 'zoodpay'),

                'type' => 'url',

                'description' => __('Place the payment gateway API URL.', 'zoodpay'),

                'desc_tip' => true,

            ) ,

            'zoodpay_merchant_key' => array(

                'title' => __('Merchant Key *', 'zoodpay'),

                'type' => 'text',

            ) ,

            'zoodpay_merchant_secret_key' => array(

                'title' => __('Merchant Secret Key * ', 'zoodpay'),

                'type' => 'password'

            ) ,

            'zoodpay_salt' => array(

                'title' => __('Salt Key *', 'zoodpay'),

                'type' => 'password',

            ) ,

            'terms_condition' => array(

                'title' => __('T&C URL', 'zoodpay'),

                'type' => 'url',

                'description' => __('Place the T&C URL.', 'zoodpay'),

                'desc_tip' => true,

            ) ,


            'zoodpay_market' => array(

                'title' => __('Market Code', 'zoodpay'),

                'type' => 'select',

                'options' => array(

                    'KZ' => 'KZ',

                    'UZ' => 'UZ',

                    'IQ' => 'IQ',

                    'JO' => 'JO',

                    'KSA' => 'KSA',

                    'KW' => 'KW',

                ) ,

                'default' => 'KZ',

            ) ,

            'zoodpay_language' => array(

                'title' => __('Language Code', 'zoodpay'),

                'type' => 'select',

                'options' => array(

                    'en' => 'en',

                    'kk' => 'kk',

                    'uz' => 'uz',

                    'ar' => 'ar',

                    'ru' => 'ru',

                    'ku' => 'ku',

                ) ,

                'default' => 'en',


            ),
            'get_config' => array(


                'type'        => 'get_config'


            )

        );

    }

    public function generate_get_ipn_html() {
        ob_start();
        printf('<tr valign="top">
            <th scope="row" class="titledesc"><label for="woocommerce_zoodpay_zoodpay_language">'. __('Refund URL', 'zoodpay').'  </label></th>
            <td class="forminp" id="">
                <div class="wc_input_table_wrapper">'. home_url('/').'?zoodpay_action=refund</div>               
               </td>
            </tr>');

        printf('<tr valign="top">
            <th scope="row" class="titledesc"><label for="woocommerce_zoodpay_zoodpay_language">'. __('IPN URL', 'zoodpay'). '</label></th>
            <td class="forminp" id="">
                <div class="wc_input_table_wrapper">'. home_url('/').'?zoodpay_action=ipn</div>               
               </td>
            </tr>');
        return ob_get_clean();
    }

    public function generate_get_config_html() {
        ob_start();
        printf('<tr valign="top">
            <th scope="row" class="titledesc"></th>
            <td class="forminp" id="zoodpay_button">
                <div class="wc_input_table_wrapper">');
        printf('<button type="button" id="cofigButton" class="button-primary">'. __('Get Configuration', 'zoodpay'). '</button>
                 <img style="display:none;" id="ajax-loader" src="'.esc_url(plugin_dir_url( __FILE__ )).'assest/img/ajax-loader.gif">
                 <img style="display:none;" id="ajax-success" src="'.esc_url(plugin_dir_url( __FILE__ )).'assest/img/success.png"></div>               
                <input type="hidden" id="sec" value="'.wp_create_nonce('chek').'" >
                </td>
            </tr>');

        printf('<tr valign="top">
            <th scope="row" class="titledesc"></th>
            <td class="forminp" >
                <div class="wc_input_table_wrapper">');
        printf('<button type="button" id="healtcheck" class="button-primary">' . __('API healthcheck', 'zoodpay').'</button>
                 <img style="display:none;" id="ajax-loader-health" src="'.esc_url(plugin_dir_url( __FILE__ )).'assest/img/ajax-loader.gif">
                 <img style="display:none;" id="helth-success" src="'.esc_url(plugin_dir_url( __FILE__ )).'assest/img/success.png"></div>               
                <input type="hidden" id="healt" value="'.wp_create_nonce('healtchek').'" >
                </td>
            </tr>');?>

        <script type="text/javascript">
            jQuery(function($) {
                jQuery('#cofigButton').on( 'click',function(){
                    $("#ajax-success").fadeOut();
                    if($("#woocommerce_zoodpay_zoodpay_merchant_key").val() == ''){
                        alert("<?php esc_html_e('Please Enter Zoodpay Marchent Key','zoodpay'); ?>");


                    }else{
                        var marchentKey = $("#woocommerce_zoodpay_zoodpay_merchant_key").val();


                    }
                    if($("#woocommerce_zoodpay_zoodpay_merchant_secret_key").val() == ''){
                        alert("<?php esc_html_e('Please Enter Zoodpay Secret Key','zoodpay'); ?>");


                    }else{
                        var marchent_SS_Key = atob($("#woocommerce_zoodpay_zoodpay_merchant_secret_key").val());


                    }
                    if($("#woocommerce_zoodpay_zoodpay_salt").val() == ''){
                        alert("<?php esc_html_e('Please Enter Zoodpay Salt Key','zoodpay'); ?>");


                    }else{
                        var salt_Key = atob($("#woocommerce_zoodpay_zoodpay_salt").val());


                    }

                    var market_code = $("#woocommerce_zoodpay_zoodpay_market").val();
                    var secu = $("#sec").val();

                    if(marchentKey && marchent_SS_Key && salt_Key != ''){

                        $.ajax({
                            type : 'POST',
                            url  : '<?php echo esc_url_raw(admin_url('admin-ajax.php')); ?>',
                            data : {
                                action : 'get_configration',
                                marchentKey : marchentKey,
                                SS_Key : marchent_SS_Key,
                                salt_Key : salt_Key,
                                market_code : market_code,
                                security : secu
                            },
                            beforeSend: function() {
                                $("#ajax-loader").fadeIn();
                            },
                            success:function(response){
                                $("#ajax-loader").fadeOut();

                                if(response == "success"){
                                    $("#ajax-success").fadeIn();
                                    $("#ajax-success").delay(5000).fadeOut();
                                }else{
                                    var obj = JSON.parse(response);
                                    alert(obj.message);
                                }



                            }


                        })
                    }

                });

                $("#healtcheck").click(function(){
                    $("#helth-success").fadeOut();
                    if($("#woocommerce_zoodpay_zoodpay_merchant_key").val() == ''){
                        alert("<?php esc_html_e('Please Enter Zoodpay Marchent Key','zoodpay'); ?>");


                    }else{
                        var marchentKey = $("#woocommerce_zoodpay_zoodpay_merchant_key").val();


                    }
                    if($("#woocommerce_zoodpay_zoodpay_merchant_secret_key").val() == ''){
                        alert("<?php esc_html_e('Please Enter Zoodpay Secret Key','zoodpay'); ?>");


                    }else{
                        var marchent_SS_Key = atob($("#woocommerce_zoodpay_zoodpay_merchant_secret_key").val());


                    }


                    var sec = $("#healt").val();

                    if(marchentKey && marchent_SS_Key != ''){
                        $.ajax({
                            type : 'POST',
                            url  : '<?php echo esc_url_raw(admin_url('admin-ajax.php')); ?>',
                            data : {
                                action : 'API_healtcheck',
                                marchentKey : marchentKey,
                                SS_Key : marchent_SS_Key,
                                security : sec
                            },
                            beforeSend: function() {
                                $("#ajax-loader-health").fadeIn();
                            },
                            success:function(response){
                                $("#ajax-loader-health").fadeOut();

                                if(response == "success"){
                                    $("#helth-success").fadeIn();
                                    $("#helth-success").delay(5000).fadeOut();
                                }else{
                                    var obj = JSON.parse(response);
                                    alert(obj.message);
                                }



                            }


                        })
                    }
                })
            });
        </script>

        <?php
        return ob_get_clean();
    }

    public function sanitize_settings($settings)

    {

        if (isset($settings) && isset($settings['zoodpay_merchant_key']))

        {

            $settings['zoodpay_merchant_key'] = $settings['zoodpay_merchant_key'];

        }

        if (isset($settings) && isset($settings['zoodpay_merchant_secret_key']))

        {

            $settings['zoodpay_merchant_secret_key'] = $settings['zoodpay_merchant_secret_key'];

        }

        if (isset($settings) && isset($settings['zoodpay_salt']))

        {

            $settings['zoodpay_salt'] = $settings['zoodpay_salt'];

        }

        if (isset($settings) && isset($settings['environment']))

        {

            $settings['environment'] = $settings['environment'];

        }

        if (isset($settings) && isset($settings['terms_condition']))

        {

            $settings['terms_condition'] = $settings['terms_condition'];

        }
        return $settings;

    }

    /**
     * Validate the API key
     * @see validate_settings_fields()
     */

    public function validate_title_field($key)

    {

        $value = sanitize_text_field($_POST[$this->plugin_id . $this->id . '_' . $key]);

        $title = $this->get_option('title');

        if (isset($value) && $value == "")

        {

            WC_Admin_Settings::add_error(esc_html__('The title is required', 'zoodpay'));

            $this->errors[] = $key;

            $value = $title;

        }

        return $value;

    }

    public function validate_terms_condition_field($key)

    {

        $value = sanitize_text_field($_POST[$this->plugin_id . $this->id . '_' . $key]);

        $T_C = $this->get_option('terms_condition');

        if (isset($value) && $value == "")

        {

            WC_Admin_Settings::add_error(esc_html__('The T&C URL is required', 'zoodpay'));

            $this->errors[] = $key;

            $value = $T_C;

        }

        return $value;

    }

    public function validate_environment_field($key)

    {

        $value = sanitize_text_field($_POST[$this->plugin_id . $this->id . '_' . $key]);

        $APIURL = $this->get_option('environment');

        if (isset($value) && $value == "")

        {

            WC_Admin_Settings::add_error(esc_html__('The API URL is required', 'zoodpay'));

            $this->errors[] = $key;

            $value = $APIURL;

        }

        return $value;

    }

    public function validate_zoodpay_merchant_key_field($key)

    {

        $value = sanitize_text_field($_POST[$this->plugin_id . $this->id . '_' . $key]);

        $marchentKey = $this->get_option('zoodpay_merchant_key');

        if (isset($value) && strlen($value) == '')

        {

            WC_Admin_Settings::add_error(esc_html__('Looks like you made a mistake with the Zoodpay Merchant Key field.', 'zoodpay'));

            $this->errors[] = $key;

            $value = $marchentKey;

        }

        return $value;

    }

    public function validate_zoodpay_merchant_secret_key_field($key)

    {

        $marchent_Secret_Key = base64_decode($this->get_option('zoodpay_merchant_secret_key'));
        $oldS = $this->get_option('zoodpay_merchant_secret_key');
        $sskey = sanitize_text_field($_POST[$this->plugin_id . $this->id . '_' . $key]);
        if($sskey != $oldS){

            $value = base64_encode(sanitize_text_field($_POST[$this->plugin_id . $this->id . '_' . $key]));

        }else{
            $value = sanitize_text_field($_POST[$this->plugin_id . $this->id . '_' . $key]);
        }


        if (isset($value) && strlen($value) == '')

        {

            WC_Admin_Settings::add_error(esc_html__('Looks like you made a mistake with the Zoodpay Merchant Secret Key field.', 'zoodpay'));

            $this->errors[] = $key;

            $value = base64_encode($marchent_Secret_Key);

        }

        return $value;

    }

    public function validate_zoodpay_salt_field($key)

    {

        $saltKey = base64_decode($this->get_option('zoodpay_salt'));
        $old = $this->get_option('zoodpay_salt');
        $salt = sanitize_text_field($_POST[$this->plugin_id . $this->id . '_' . $key]);
        if($salt != $old){

            $value = base64_encode(sanitize_text_field($_POST[$this->plugin_id . $this->id . '_' . $key]));
        }else{
            $value = sanitize_text_field($_POST[$this->plugin_id . $this->id . '_' . $key]);
        }


        if (isset($value) && strlen($value) == '')

        {

            WC_Admin_Settings::add_error(esc_html__('Looks like you made a mistake with the Zoodpay Salt Key field.', 'zoodpay'));

            $this->errors[] = $key;

            $value = base64_encode($saltKey);

        }

        return $value;

    }

    /**
     * Display errors by overriding the display_errors() method
     * @see display_errors()
     */



    public function payment_fields()

    {

        global $woocommerce;
        $status = "false";
        $order_total = $woocommerce->cart->total;WC()->cart->total;


        if ($description = $this->get_description())

        {

            echo wpautop(wptexturize($description) );

        }

        echo '<style>#transaction_type_field label.radio { display:inline-block; margin:0 .8em 0 .4em}</style>';
        $fetchConfigResponse = (json_decode(get_option('_Zoodpay_configuration' , true),true))['configuration'];
        reset($this->options);

        for($i = 0, $iMax = count( $fetchConfigResponse ); $i < $iMax; $i++) {
            $desc = '';
            if (($order_total >= $fetchConfigResponse[$i]['min_limit']) && ($order_total <= $fetchConfigResponse[$i]['max_limit'])) {
                $serviceName=  $fetchConfigResponse[$i]['service_name'];
                $serviceCode=  $fetchConfigResponse[$i]['service_code'];

                if($fetchConfigResponse[$i]['description'] != ''){
                    $desc = '<a class="t_c" data="'.$fetchConfigResponse[$i]['service_code'].'" href="javascript:void(0);">'. __( "Terms and Condition","zoodpay").'</a>';
                    echo '<input type="hidden" class="t_c" id="'.$fetchConfigResponse[$i]['service_code'].'" value="'.$fetchConfigResponse[$i]['description'].'" >';

                    $this->service = array_merge($this->service,array(


                            'desc_'.$serviceCode		=> $desc

                        )
                    );

                }else{
                    $desc = '';
                }

                if (isset($fetchConfigResponse[$i]['instalments']) ) {

                    $monthlyPayment= (round($order_total/$fetchConfigResponse[$i]['instalments'],2)) .' '.  get_woocommerce_currency_symbol();

                    $opt[$i]['text']= $fetchConfigResponse[$i]['instalments'].__(" $serviceName of $monthlyPayment ", 'zoodpay');
                    $opt[$i]['service_code'] = $serviceCode ;
                    $service_text =__(" $serviceName ","zoodpay") .__("of ","zoodpay") .__("$monthlyPayment ","zoodpay") ;
                    $this->options = array_merge($this->options,array(

                            "$serviceCode" => $service_text ,


                        )
                    );


                    $status = "true";


                }
                else{


                    $opt[$i]['text']    =   __( $serviceName. "($serviceCode) ", 'zoodpay');
                    $opt[$i]['service_code'] = $serviceCode ;
                    $service_text = __(" $serviceName  ", 'zoodpay');
                    $this->options = array_merge($this->options,array(

                            "$serviceCode" => $service_text ,


                        )
                    );

                    $status = "true";



                }





            }


        }


        $i = 0;


        foreach($this->options as $k => $option){

            //	printf($this->service['desc_'.$k]) ;
            printf('<p>  <input type="radio" class="input-radio" value="'.$k.'" name="transaction_type" id="transaction_type_'.$k.'">' );
            printf("\n");
            printf('<label for="transaction_type_'.$k.'" class="radio ">'.esc_html__($option).'</label>') ;
            printf($this->service['desc_'.$k].'</p>');
            printf("\n");


        }


    }




    public function Zoodpay_save_order_payment_type_meta_data($order, $data)

    {

        if (sanitize_text_field($data['payment_method']) === $this->id && sanitize_text_field(isset($_POST['transaction_type'])))

            $order->update_meta_data('_transaction_type', sanitize_text_field($_POST['transaction_type']));

    }

    public function Zoodpay_display_payment_type_order_edit_pages($order)

    {

        if ($this->id === $order->get_payment_method() && $order->get_meta('_transaction_type'))

        {

            echo wp_kses_post(  '<p><strong>' . __('Transaction type') . ':</strong> ' . $order->get_meta('_transaction_type')  . '</p>' );

        }

    }

    public function Zoodpay_display_transaction_type_order_item_totals($total_rows, $order, $tax_display)

    {

        if (is_a($order, 'WC_Order') && $order->get_meta('_transaction_type'))

        {

            $new_rows = []; // Initializing


            $options = $this->options;

            // Loop through order total lines


            foreach ($total_rows as $total_key => $total_values)

            {

                $new_rows[$total_key] = $total_values;

                if ($total_key === 'payment_method')

                {

                    $new_rows['payment_type'] = [

                        'label' => __("Transaction type", 'zoodpay') . ':',

                        'value' => $options[$order->get_meta('_transaction_type') ],

                    ];

                }

            }

            $total_rows = $new_rows;

        }

        return $total_rows;

    }

    public function process_payment($order_id)

    {

        global $woocommerce;

        $customer_order = new WC_Order($order_id);
        $shipping =  $customer_order->get_shipping_method();
        if($customer_order->transaction_type == ''){

            wc_add_notice(__('Select zoodpay payment method', 'zoodpay') , 'error');

            return ;
            exit;
        }

        $order_id = trim($customer_order->get_order_number());

        $currency = get_woocommerce_currency();

        $total = $customer_order->get_total();

        $marchentKey = $this->get_option('zoodpay_merchant_key');

        $marchent_Secret_Key = base64_decode($this->get_option('zoodpay_merchant_secret_key'));

        $saltKey = base64_decode($this->get_option('zoodpay_salt'));

        $APIURL = $this->get_option('environment');

        $language_code = $this->get_option('zoodpay_language');

        if (!empty($language_code))

        {

            $language_code = $this->get_option('zoodpay_language');

        }

        else

        {

            $language_code = strtolower($customer_order->billing_country);

        }




        $marketCode = get_option('_Zoodpay_Market_code_', true);

        if (!empty($marketCode))

        {

            $marketCode = get_option('_Zoodpay_Market_code_', true);

        }

        else

        {

            $marketCode = $customer_order->billing_country;

        }


        $sign = $marchentKey . '|' . $order_id . '|' . floatval($total) . '|' . $currency . '|' . $marketCode . '|' . htmlspecialchars_decode($saltKey);

        $signature = hash('sha512', $sign);

        $i = 0;

        foreach ($customer_order->get_items() as $item_id => $item_data)

        {

            $product = $item_data->get_product();

            $terms = get_the_terms($item_data['product_id'], 'product_cat');

            foreach ($terms as $term)

            {

                $product_cat[$item_data['product_id']]["categories"][] = array(

                    $term->name

                );

            }

            $product_detail[$item_data['product_id']] = array(

                "currency_code" => $currency,

                "discount_amount" => $item_data->get_subtotal_tax() ,

                "name" => $item_data->get_name() ,

                "price" => '' . $product->get_price() ,

                "quantity" => $item_data->get_quantity() ,

                "sku" => $product->get_sku() ,

                "tax_amount" => $item_data->get_subtotal_tax()

            );

        }

        foreach ($product_detail as $key => $val)

        {

            if (array_key_exists($key, $product_cat))

            {

                $new_Array[$i] = array_merge($product_cat[$key], $product_detail[$key]);

            }

            else

            {

                $new_Array[$i] = $product_cat[$key]["categories"][] = $product_detail[$key];

            }

            $i++;

        }

        if ($customer_order->get_total_shipping())

        {

            $shipping_price = $customer_order->get_total_shipping();

        }

        else

        {

            $shipping_price = '0.00';

        }

        if(isset($customer_order->shipping_address_1) && $customer_order->shipping_address_1 != ''){
            $shiping_add_1 = $customer_order->shipping_address_1;
        }else{
            $shiping_add_1 = $customer_order->billing_address_1;
        }

        if(isset($customer_order->shipping_city) && $customer_order->shipping_city != ''){
            $shiping_city = $customer_order->shipping_city;
        }else{
            $shiping_city = $customer_order->billing_city;
        }

        if(isset($customer_order->shipping_country) && $customer_order->shipping_country != ''){
            $shiping_count = $customer_order->shipping_country;
        }else{
            $shiping_count = $customer_order->billing_country;
        }

        if(isset($customer_order->shipping_phone) && $customer_order->shipping_phone != '' ){
            $shiping_ph = $customer_order->shipping_phone;
        }else{
            $shiping_ph = $customer_order->billing_phone;
        }

        if(isset($customer_order->shipping_first_name) && $customer_order->shipping_first_name != '' ){
            $shiping_fname = $customer_order->shipping_first_name;
        }else{
            $shiping_fname = $customer_order->billing_first_name;
        }

        if(isset($customer_order->shipping_state) && $customer_order->shipping_state != '' ){
            $shiping_stat = $customer_order->shipping_state;
        }else{
            $shiping_stat = $customer_order->billing_state;
        }

        if(isset($customer_order->shipping_postcode) && $customer_order->shipping_postcode != ''){
            $shiping_code = $customer_order->shipping_postcode;
        }else{
            $shiping_code = $customer_order->billing_postcode;
        }

        if(isset($customer_order->shipping_address_2) && $customer_order->shipping_address_2 != ''){
            $shipping_address_2 = $customer_order->shipping_address_2;
        }else{
            $shipping_address_2 = $customer_order->billing_address_2;
        }

        $payment = json_encode(array("billing" => array(
                "address_line1" => $customer_order->billing_address_1,
                "address_line2" => $customer_order->billing_address_2,
                "city" => $customer_order->billing_city,
                "country_code" => $customer_order->billing_country,
                "name" => $customer_order->billing_first_name,
                "phone_number" =>$this->clearSpecialChar( $customer_order->billing_phone),
                "state" => $customer_order->billing_state,
                "zipcode" => $customer_order->billing_postcode
            ),
                "customer" => array(
                    "customer_dob" => date('Y-m-d', strtotime($customer_order->billing_birth_date)) ,
                    "customer_email" => $customer_order->billing_email,
                    "customer_phone" => $this->clearSpecialChar($customer_order->billing_phone),
                    "first_name" => $customer_order->billing_first_name,
                    "last_name" => $customer_order->billing_last_name
                ),
                "items" => $new_Array,
                "order" => array(
                    "amount" => $total,
                    "currency" => $currency,
                    "discount_amount" => $customer_order->get_total_discount() ,
                    "lang" => $language_code,
                    "market_code" => $marketCode,
                    "merchant_reference_no" => $order_id,
                    "service_code" => $customer_order->transaction_type,
                    "shipping_amount" => $shipping_price,
                    "signature" => $signature,
                    "tax_amount" => $customer_order->get_total_tax()
                ),
                "shipping" => array(
                    "address_line1" => $shiping_add_1,
                    "address_line2" => $shipping_address_2,
                    "city" => $shiping_city,
                    "country_code" => $shiping_count,
                    "name" => $shiping_fname,
                    "phone_number" =>$this->clearSpecialChar($shiping_ph),
                    "state" => $shiping_stat,
                    "zipcode" => $shiping_code
                ),
                "shipping_service" => array(
                    "name" => $shipping,
                    "priority" => "",
                    "shipped_at" => "",
                    "tracking" => ""
                )
            )
        );

        $args = array(
            'method'      => 'POST',
            'sslverify'   => false,
            'headers'     => array(
                'Accept'            => 'application/json',
                'Content-Length'    => strlen($payment),
                'Authorization'     => 'Basic ' .base64_encode($marchentKey . ':' . $marchent_Secret_Key),
                'Content-Type'      => 'application/json',
            ),
            'body'        => $payment,
        );

        $response = wp_remote_retrieve_body(wp_remote_post( $APIURL."transactions", $args ));

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) != 200 ) {
            error_log( print_r( $response, true ) );
        }


        $return_data = json_decode($response);

        if (is_wp_error($response))

        {

            throw new Exception(__('There is issue for connecting payment gateway. Sorry for the inconvenience.', 'zoodpay'));

        }

        if (!empty($return_data->message))

        {


            $message = $return_data->message."<br>";
            if (isset($return_data->details)) {
                foreach ($return_data->details as $iValue ) {
                    $message .= $iValue->field . ": " . $iValue->error . "<br>";
                }
            }

            wc_add_notice(__($message, 'zoodpay') , 'error');

            return array(

                'result' => 'failure',

                'redirect' => WC()->cart->get_checkout_url()

            );

            $customer_order->add_order_note('Error: ' . $return_data->message);

            exit;

        }

        if ($return_data->transaction_id)

        {
            update_post_meta($order_id, '_transaction_id', $return_data->transaction_id, true);

            $woocommerce

                ->cart

                ->empty_cart();

            return array(

                'result' => 'success',

                'redirect' => esc_url_raw($return_data->payment_url)

            );

        }

    }

    public function process_refund($order_id, $amount = null, $reason = '')

    {

        global $woocommerce;
        global $wpdb;

        $marchentKey = $this->get_option('zoodpay_merchant_key');

        $marchent_Secret_Key = base64_decode($this->get_option('zoodpay_merchant_secret_key'));

        $APIURL = $this->get_option('environment');

        $refund_order = new WC_Order($order_id);

        $uniq_Id = wp_generate_uuid4();

        $order_data = $refund_order->get_data(); // The Order data
        $order_total_final = $order_data['total'];

        /*if($order_total_final == $amount){
        $refund_order->update_status('wc-zoodpay-refund', '');
        }*/

        $get_results = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'posts WHERE post_type = "shop_order_refund" AND post_parent = "'.$order_id.'" ORDER BY ID DESC LIMIT 0,1' );

        /* $get_results_all = $wpdb->get_results('SELECT * FROM '.$wpdb->prefix.'posts WHERE post_type = "shop_order_refund" AND post_parent = "'.$order_id.'" ORDER BY ID DESC' );

        foreach($get_results_all as $get_result){

        $_refund_amount = get_post_meta($get_result->ID, '_order_total', true);

        $_refund_amount_tot +=$_refund_amount;





    }

    $balance_after_refund= $order_total_final + intval($_refund_amount_tot);
    if($balance_after_refund==0){
        $refund_order->update_status('wc-zoodpay-refund', '');
        }*/

        if (!empty($reason) && strlen($reason) > 3)

        {

            $reason = $reason;

        }

        else

        {

            $reason = __("Refund Request", 'zoodpay');

        }

        $trn_id = get_post_meta($refund_order->get_id(), '_transaction_id', true);
        update_post_meta($get_results[0]->ID, '_refund_status' , "Initiated");
        update_post_meta($get_results[0]->ID, '_request_id' , $uniq_Id);

        $refund = json_encode(array(

            "merchant_refund_reference" => "" . $refund_order->get_id() . "",

            "reason" => $reason,

            "refund_amount" => intval($amount),

            "transaction_id" => $trn_id,

            "request_id" => $uniq_Id

        ));

        $args = array(
            'method'      => 'POST',
            'sslverify'   => false,
            'headers'     => array(
                'Accept'            => 'application/json',
                'Content-Length'    => strlen($refund),
                'Authorization'     => 'Basic ' .base64_encode($marchentKey . ':' . $marchent_Secret_Key),
                'Content-Type'      => 'application/json',
            ),
            'body'        => $refund,
        );

        $refund_response = wp_remote_retrieve_body(wp_remote_post( $APIURL.'refunds', $args ));

        if ( is_wp_error( $refund_response ) || wp_remote_retrieve_response_code( $refund_response ) != 200 ) {
            error_log( print_r( $refund_response, true ) );
        }


        $refund_data = json_decode($refund_response);

        if ($refund_data->refund_id != '')

        {
            if($refund_data->refund->status == "Initiated"){

                /* $refund_order->update_status('wc-refunded', 'order_note'); */
                update_post_meta($refund_order->get_id(), '_refund_id', $refund_data->refund_id);
            }else{
                update_post_meta($refund_order->get_id(), '_refund_id', $refund_data->refund_id);
            }


            /* $refund_order->update_status('refunded', 'order_note'); */
            return true;
        }

        return false;

    }



    public function validate_fields()

    {

        /* if( empty( $_POST[ 'billing_first_name' ]) ) {


           wc_add_notice(  'First name is required!', 'error' );


           return false;


         }


          return true; */



    }

    public function clearSpecialChar($string) {
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.

        return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
    }

}