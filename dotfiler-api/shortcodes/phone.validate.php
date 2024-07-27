<?php
function phone_validate_func($atts) {

    ob_start();
    ?>

    <div class="validate-phone-wrapper">

        <form id="phone-validate-form" class="phone-validate-form" action="" method="post">
            <input type="text" name="phone" id="phone" placeholder="Enter phone number" required>
            <button type="submit">Check</button>
        </form>    

        <div id="phone-validate-result" class="phone-validate-result"></div>

    </div>

    <script>
        jQuery(document).ready(function($) {
            jQuery('#phone-validate-form').submit(function(e) {

                e.preventDefault();
                var phone = $('#phone').val();

                jQuery('#phone-validate-result').html('<p class="validation-process">Loading...</p>');

                jQuery.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'phone_validate',
                        phone: phone
                    },
                    success: function(response) {
                        jQuery('#phone-validate-result').html(response);
                    }
                });

                return false;
                
            });
        });
    </script>    

    <style>

        .validate-phone-wrapper {
            margin-top: 20px;
            max-width: 400px;
        }

        .phone-validate-form {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .phone-validate-form input {
            padding: 8px;
            margin-right: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            height: auto;
        }

        .phone-validate-form button {
            padding: 7px 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            background: #0073aa;
            color: #fff;
            cursor: pointer;
            font-size: 17px;
        }

        .phone-validation-list {
            list-style: none;
            padding: 0;
        }

        .phone-validation-list li {
            margin-bottom: 0;
        }

        .validation-error {
            color: red;
            text-align: center;
            font-weight: bold;
        }

        .validation-process {
            text-align: center;
            color: green;
            font-weight: bold;
        }

    </style>   

    <?php
    $html = ob_get_clean();
    return $html;
}
add_shortcode('phone-validate', 'phone_validate_func');





