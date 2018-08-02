<?php

class ps_mellat extends ps_payment_gateway {
	public $terminal_id;
	public $username;
	public $password;

	/**
	 * ps_mellat constructor.
	 *
	 */
	public function __construct() {
		self::load_nusoap();
	}

	public function send( $callback, $price, $username, $email, $order_id ) {
		$mellat = new ps_main_mellat( $this->terminal_id, $this->username, $this->password );
		$status = $mellat->request( $price, $order_id, $callback );

		if ( $status != - 6 ) {
			$this->insert_payment( $username, $price, $order_id, $email );
			$mellat->go2bank( $status );
		} else {
			echo $this->danger_alert( 'خطا در متصل شدن به درگاه ! ' . $status );
		}
	}

	public function verify( $price, $post_id, $order_id, $course_id = 0 ) {
		if ( isset( $_POST['RefId'] ) && isset( $_POST['ResCode'] ) && isset( $_POST['SaleOrderId'] ) && isset( $_POST['SaleReferenceId'] ) ) {

			$mellat = new ps_main_mellat( $this->terminal_id, $this->username, $this->password );
			$status = $mellat->verify( $price, $order_id );

			if ( count( $status ) == 0 ) {
				$this->success_payment( $_POST['RefId'], $order_id, $price, $post_id, $course_id );
			} else {
				echo $this->danger_alert( 'خطا در پردازش عملیات پرداخت ، نتیجه پرداخت : ' . count( $status ) );
			}
			$this->end_payment();
		}
	}
}

class ps_main_mellat {

	private $terminalID;
	private $username;
	private $password;

	const namespace_url = 'http://interfaces.core.sw.bps.com/';
	const webservice = 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl'; // correct

	public function __construct( $terminal_id, $username, $password ) {
		$this->terminalID = $terminal_id;
		$this->username   = $username;
		$this->password   = $password;
	}

	public function request( $price = null, $order_id = null, $callback = null ) {
		$parameters = array(
			'terminalId'     => $this->terminalID,
			'userName'       => $this->username,
			'userPassword'   => $this->password,
			'orderId'        => $order_id,
			'amount'         => $price * 10,
			'localDate'      => date( "Ymd" ),
			'localTime'      => date( "His" ),
			'additionalData' => '',
			'callBackUrl'    => $callback,
			'payerId'        => '0',
		);

		try {
			$client = new nusoap_client( self::webservice );
			$err    = $client->getError();
			if ( $err ) {
				return - 6;
			}
		} catch ( Exception $e ) {
			return - 6;
		}

		$result = $client->call( 'bpPayRequest', $parameters, self::namespace_url );
		$res    = explode( ',', $result );

		if ( ! isset( $res[0] ) or $res[0] != "0" or ! isset( $res[1] ) ) {
			return - 6;
		} else {
			return strip_tags( $res[1] );
		}

		return - 6;
	}

	public $SaleReferenceId = '';

	public function verify( $price = null, $order_id = null, $au = null ) {
		$RefId                 = @$_POST['RefId']; // notNeed
		$ResCode               = @$_POST['ResCode'];
		$SaleOrderId           = @$_POST['SaleOrderId']; // this code sended with us to request method order id
		$SaleReferenceId       = @$_POST['SaleReferenceId'];
		$this->SaleReferenceId = $SaleReferenceId;

		$errors = array();

		//check
		if ( $SaleOrderId != $order_id ) {
			$errors[] = 'order is not equal with saleorder id--sale:' . $SaleOrderId . ':::::order_id:' . $order_id;

			return $errors;
		}

		// if ResCode ==0 trans is success
		if ( $ResCode != 0 ) {
			$errors[] = 'rescode is not 0---';

			return $errors;
		}

		try {
			$client = new nusoap_client( self::webservice );
			$err    = $client->getError();
			if ( $err ) {
				$errors[] = 'error on get error soap clinet:';
				$errors[] = $err;

				return $errors;
			}
		} catch ( Exception $e ) {
			$errors[] = 'error on catch soap--';

			return $errors;
		}

		$parameters = array(
			'terminalId'      => $this->terminalID,
			'userName'        => $this->username,
			'userPassword'    => $this->password,
			'orderId'         => $SaleOrderId,
			'saleOrderId'     => $SaleOrderId,
			'saleReferenceId' => $SaleReferenceId
		);

		unset( $result );
		$result = $client->call( 'bpVerifyRequest', $parameters, self::namespace_url );

		// Check for a fault
		if ( $client->fault ) {
			$errors[] = 'fault:';
			$errors[] = $client->fault;

			return $errors;
		}

		$err = $client->getError();
		if ( $err ) {
			$errors[] = 'err get error:';
			$errors[] = $err;

			return $errors;
		}

		if ( isset( $result ) and $result == 0 ) {
			//settel
			unset( $result );
			$result = $client->call( 'bpSettleRequest', $parameters, self::namespace_url );
			if ( $client->fault ) {
				$errors[] = 'fault error in afrer seettle request:';
				$errors[] = $client->fault;

				return $errors;
			}

			$err = $client->getError();
			if ( $err ) {
				$errors[] = 'get error end:';
				$errors[] = $err;

				return $errors;
			}

			if ( isset( $result ) and $result == 0 ) {
				return $errors;
			}
		}

		$errors[] = 'end error--result:' . $result;

		return $errors;
	}

	public function go2bank( $id = '' ) {
		?>
        <div class="ps-alert ps-alert-info">در حال اتصال به درگاه ...</div>
        <form name="myform" action="https://bpm.shaparak.ir/pgwchannel/startpay.mellat" method="POST">
            <input type="hidden" name="RefId" value="<?php echo $id ?>"/>
        </form>
        <script language="javascript">document.myform.submit()</script>
		<?php
	}

}
