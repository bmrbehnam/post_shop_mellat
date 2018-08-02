<?php
/*
Plugin Name: پرداخت بانک ملت - فروش پست ها
Version: 1.0
Description:  درگاه پرداخت بانک ملت برای افزونه فروش پست ها post shop
Plugin URI: http://behnam-rasouli.ir/p/post-shop/
Author: بهنام رسولی
Author URI: http://behnam-rasouli.ir/
License: GPL3
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ps_load_mellat_payment() {
	function ps_add_mellat_payment( $list ) {
		$list['mellat'] = array(
			'name'       => 'بانک ملت',
			'class_name' => 'ps_mellat',
			'settings'   => array(
				'terminal_id' => array( 'name' => 'terminal_id' ),
				'username'    => array( 'name' => 'نام کاربری' ),
				'password'    => array( 'name' => 'رمز عبور' ),
			)
		);

		return $list;
	}

	function ps_load_mellat_class() {
		return include_once plugin_dir_path( __FILE__ ) . '/ps_mellat.php';
	}

	if ( class_exists( 'ps_payment_gateway' ) && ! class_exists( 'ps_mellat' ) ) {
		add_filter( 'ps_payment_list', 'ps_add_mellat_payment' );
		add_action( 'ps_load_payment_class', 'ps_load_mellat_class' );
	}
}

add_action( 'plugins_loaded', 'ps_load_mellat_payment', 0 );

add_action( 'admin_notices', 'ps_mellat_check_requirement' );

function ps_mellat_check_requirement() {
	if ( current_user_can( 'activate_plugins' ) ) {
		if ( ! class_exists( 'ps_payment_gateway' ) ) {
			echo '<div class="notice notice-warning is-dismissible">';
			echo 'برای استفاده از این درگاه پرداخت نیاز به افزونه فروش پست ها است،لطفا این پلاگین رو خریداری کنید و نصب فعال کنید.';
			echo '<br><a href="http://behnam-rasouli.ir/p/post-shop?source=pay_plugin">اطلاعات بیشتر ...</a>';
			echo '</div>';
		} elseif ( version_compare( PS_VERSION, '5.5.0', '<' ) ) {
			echo '<div class="notice notice-warning is-dismissible">';
			echo 'برای استفاده از این پلاگین ورژن افزونه فروش پست ها باید حداقل 5.5 باشد!';
			echo '<br><a href="http://behnam-rasouli.ir/p/post-shop?source=pay_plugin">اطلاعات بیشتر ...</a>';
			echo '</div>';
		}
	}
}
